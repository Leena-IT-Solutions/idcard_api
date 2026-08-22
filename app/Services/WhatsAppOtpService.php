<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppOtpService
{
    /**
     * Determine whether WhatsApp OTP verification is active and fully configured.
     */
    public function isConfigured(): bool
    {
        $enabled = (bool) Setting::get('whatsapp_otp_enabled', config('services.whatsapp.otp_enabled', false));
        $token = Setting::get('whatsapp_access_token') ?: config('services.whatsapp.access_token');
        $phoneId = Setting::get('whatsapp_phone_number_id') ?: config('services.whatsapp.phone_number_id');

        return $enabled && !empty($token) && !empty($phoneId);
    }

    /**
     * Clean and format Indian mobile number to E.164 without leading plus.
     */
    public function formatMobileNumber(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile);

        if (strlen($digits) === 10) {
            return '91' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91' . substr($digits, 1);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Generate 6-digit OTP, cache it, and dispatch WhatsApp message via Meta Cloud API.
     */
    public function sendOtp(string $mobileNumber): array
    {
        $token = Setting::get('whatsapp_access_token') ?: config('services.whatsapp.access_token');
        $phoneId = Setting::get('whatsapp_phone_number_id') ?: config('services.whatsapp.phone_number_id');
        $templateName = Setting::get('whatsapp_otp_template') ?: config('services.whatsapp.otp_template', 'otp_verification');

        if (empty($token) || empty($phoneId)) {
            return [
                'success' => false,
                'message' => 'WhatsApp Cloud API is not configured with Token and Phone Number ID.',
            ];
        }

        $formattedMobile = $this->formatMobileNumber($mobileNumber);
        $otp = (string) random_int(100000, 999999);

        // Store OTP in cache for 5 minutes
        Cache::put("whatsapp_otp:{$formattedMobile}", $otp, now()->addMinutes(5));

        try {
            $endpoint = "https://graph.facebook.com/v20.0/{$phoneId}/messages";

            // If a template name is provided, send using standard Meta Authentication/Utility template payload
            if (!empty($templateName)) {
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $formattedMobile,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => [
                            'code' => 'en',
                        ],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => $otp,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'button',
                                'sub_type' => 'url',
                                'index' => '0',
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => $otp,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            } else {
                // Fallback direct text message
                $appName = config('app.name', 'iCard Studio');
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $formattedMobile,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => "Your {$appName} verification OTP is: *{$otp}*. Valid for 5 minutes. Please do not share this code.",
                    ],
                ];
            }

            $response = Http::withToken($token)
                ->timeout(15)
                ->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp OTP dispatched to {$formattedMobile}");
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully to your WhatsApp number.',
                    'expires_in_seconds' => 300,
                ];
            }

            // If template button sub_type caused an error (e.g. template without URL button), retry with body-only component
            if (!empty($templateName) && isset($payload['template']['components'][1])) {
                $fallbackPayload = $payload;
                $fallbackPayload['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otp,
                            ],
                        ],
                    ],
                ];

                $retryResponse = Http::withToken($token)
                    ->timeout(15)
                    ->post($endpoint, $fallbackPayload);

                if ($retryResponse->successful()) {
                    return [
                        'success' => true,
                        'message' => 'OTP sent successfully to your WhatsApp number.',
                        'expires_in_seconds' => 300,
                    ];
                }
            }

            $errData = $response->json('error') ?? [];
            $errorMessage = $errData['message'] ?? 'Failed to send WhatsApp message via Meta API.';
            Log::error('WhatsApp Cloud API Error', ['response' => $response->body()]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp Dispatch Exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Network error while sending WhatsApp message: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify OTP code against cached value.
     */
    public function verifyOtp(string $mobileNumber, string $otp): bool
    {
        $formattedMobile = $this->formatMobileNumber($mobileNumber);
        $cachedOtp = Cache::get("whatsapp_otp:{$formattedMobile}");

        if (!empty($cachedOtp) && (string)$cachedOtp === trim($otp)) {
            Cache::forget("whatsapp_otp:{$formattedMobile}");
            return true;
        }

        return false;
    }
}
