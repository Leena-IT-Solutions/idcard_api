<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify Webhook challenge from Meta (GET request).
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = Setting::get('whatsapp_verify_token') 
            ?: config('services.whatsapp.verify_token', 'icard_meta_verify_token_2026');

        if ($mode === 'subscribe' && $token === $expectedToken) {
            Log::info('WhatsApp Webhook Verified Successfully with Meta');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp Webhook Verification Failed: Invalid Token', [
            'received' => $token,
            'expected' => $expectedToken,
            'mode' => $mode
        ]);

        return response('Forbidden: Invalid verification token', 403);
    }

    /**
     * Handle incoming webhook notifications from Meta (POST request).
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('WhatsApp Webhook Event Received', ['payload' => $payload]);

        return response()->json(['status' => 'EVENT_RECEIVED'], 200);
    }
}
