<?php

namespace App\Http\Controllers;

use App\Models\CreditOrder;
use App\Models\CreditPlan;
use App\Models\School;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Razorpay\Api\Utility;

class RazorpayController extends Controller
{
    /**
     * Get initialized Razorpay API instance with DB-backed or env credentials.
     */
    private function getRazorpayApi(): ?Api
    {
        $keyId = Setting::get('razorpay_key_id') ?: config('services.razorpay.key_id');
        $keySecret = Setting::get('razorpay_key_secret') ?: config('services.razorpay.key_secret');

        if (empty($keyId) || empty($keySecret)) {
            return null;
        }

        return new Api($keyId, $keySecret);
    }

    /**
     * Create Razorpay Order & internal CreditOrder.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'requested_cards' => 'required|integer|min:1|max:50000',
            'notes' => 'nullable|string|max:500',
        ]);

        $api = $this->getRazorpayApi();
        if (!$api) {
            return response()->json([
                'success' => false,
                'message' => 'Razorpay payment gateway is not configured yet. Please contact the administrator.',
            ], 422);
        }

        $school = School::findOrFail($request->school_id);
        $user = auth()->user();

        // Calculate pricing slab
        $calc = CreditPlan::calculateForQuantity((int)$request->requested_cards);
        $totalAmount = (float)$calc['total_amount'];
        $amountInPaise = (int) round($totalAmount * 100);

        // 1. Create internal CreditOrder record
        $order = CreditOrder::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'ordered_credits' => $calc['quantity'],
            'bonus_credits' => $calc['bonus_credits'],
            'total_credited' => $calc['total_credits'],
            'price_per_credit' => $calc['rate'],
            'subtotal' => $calc['subtotal'],
            'gst_amount' => $calc['gst'],
            'total_amount' => $totalAmount,
            'payment_method' => 'razorpay',
            'payment_reference' => 'RZP-' . strtoupper(uniqid()),
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        try {
            // 2. Create Razorpay Order via API
            $rzpOrder = $api->order->create([
                'receipt' => 'REC_' . str_pad((string)$order->id, 8, '0', STR_PAD_LEFT),
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'notes' => [
                    'credit_order_id' => (string) $order->id,
                    'school_id' => (string) $school->id,
                    'school_name' => $school->name,
                    'credits' => (string) $calc['total_credits'],
                ],
            ]);

            $order->update([
                'razorpay_order_id' => $rzpOrder->id,
            ]);

            $keyId = Setting::get('razorpay_key_id') ?: config('services.razorpay.key_id');

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'razorpay_order_id' => $rzpOrder->id,
                'key_id' => $keyId,
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'name' => config('app.name', 'iCard Studio'),
                'description' => "{$calc['total_credits']} Credits Top-up ({$school->name})",
                'prefill' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact' => $user->mobile,
                ],
                'notes' => [
                    'school_name' => $school->name,
                    'total_credits' => $calc['total_credits'],
                ],
            ]);
        } catch (\Throwable $e) {
            $order->update(['status' => 'rejected', 'notes' => 'Razorpay Order Creation Failed: ' . $e->getMessage()]);
            Log::error('Razorpay Order Creation Error', ['error' => $e->getMessage(), 'order_id' => $order->id]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to initiate Razorpay checkout: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify payment signature & credit wallet immediately.
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'credit_order_id' => 'required|exists:credit_orders,id',
        ]);

        $order = CreditOrder::findOrFail($request->credit_order_id);
        $user = auth()->user();

        // If already approved, return success (idempotent)
        if ($order->status === 'approved') {
            return response()->json([
                'success' => true,
                'message' => 'Payment already verified and wallet credited!',
                'credits_balance' => $order->school->credits_balance,
            ]);
        }

        $api = $this->getRazorpayApi();
        if (!$api) {
            return response()->json([
                'success' => false,
                'message' => 'Razorpay API credentials not configured.',
            ], 422);
        }

        $keySecret = Setting::get('razorpay_key_secret') ?: config('services.razorpay.key_secret');

        try {
            // Verify HMAC SHA256 Signature using Razorpay Api Utility or direct hash_hmac
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];

            // Method 1: Razorpay SDK Utility Instance
            try {
                $api->utility->verifyPaymentSignature($attributes);
            } catch (\Throwable $sdkErr) {
                // Method 2: Direct RFC 2104 HMAC-SHA256 verification
                $payload = $request->razorpay_order_id . '|' . $request->razorpay_payment_id;
                $expectedSignature = hash_hmac('sha256', $payload, (string)$keySecret);
                if (!hash_equals($expectedSignature, (string)$request->razorpay_signature)) {
                    throw new SignatureVerificationError('Invalid Razorpay signature passed');
                }
            }

            // Signature verified successfully -> Approve & Credit
            $order->update([
                'status' => 'approved',
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
                'approved_by' => $user?->id,
                'approved_at' => now(),
            ]);

            // Add credits to School Wallet
            $school = $order->school;
            $school->addCredits(
                (int) $order->total_credited,
                'recharge',
                "Online Recharge (Razorpay #{$request->razorpay_payment_id}) — Order #{$order->id}",
                $order,
                $user
            );

            return response()->json([
                'success' => true,
                'message' => "Payment successful! {$order->total_credited} credits added to {$school->name} wallet.",
                'credits_balance' => $school->credits_balance,
                'total_credited' => $order->total_credited,
            ]);
        } catch (SignatureVerificationError $e) {
            $order->update(['status' => 'rejected', 'notes' => 'Signature verification failed: ' . $e->getMessage()]);
            Log::error('Razorpay Signature Verification Failed', ['error' => $e->getMessage(), 'order_id' => $order->id]);

            return response()->json([
                'success' => false,
                'message' => 'Payment signature verification failed. Please contact support.',
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Razorpay Verification Error', ['error' => $e->getMessage(), 'order_id' => $order->id]);

            return response()->json([
                'success' => false,
                'message' => 'Error verifying payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook Handler for automated asynchronous settlement.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $webhookSecret = Setting::get('razorpay_webhook_secret') ?: config('services.razorpay.webhook_secret');

        if (!empty($webhookSecret) && !empty($signature)) {
            try {
                $api = $this->getRazorpayApi();
                if ($api) {
                    $api->utility->verifyWebhookSignature($payload, $signature, $webhookSecret);
                } else {
                    $expected = hash_hmac('sha256', $payload, (string)$webhookSecret);
                    if (!hash_equals($expected, (string)$signature)) {
                        throw new \Exception('Invalid signature');
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Razorpay Webhook Signature Mismatch', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? '';

        if (in_array($event, ['payment.captured', 'order.paid'])) {
            $payment = $data['payload']['payment']['entity'] ?? [];
            $rzpOrderId = $payment['order_id'] ?? null;
            $rzpPaymentId = $payment['id'] ?? null;

            if ($rzpOrderId) {
                $order = CreditOrder::where('razorpay_order_id', $rzpOrderId)->where('status', 'pending')->first();
                if ($order) {
                    $order->update([
                        'status' => 'approved',
                        'razorpay_payment_id' => $rzpPaymentId,
                        'approved_at' => now(),
                    ]);

                    $order->school->addCredits(
                        (int) $order->total_credited,
                        'recharge',
                        "Webhook Settlement (Razorpay #{$rzpPaymentId}) — Order #{$order->id}",
                        $order
                    );

                    Log::info("Razorpay Webhook: Order #{$order->id} automatically approved and credited.");
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
