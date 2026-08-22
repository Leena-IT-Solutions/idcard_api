<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            // 1. Mailgun Gateway & Mail Config
            $mailgunDomain = Setting::get('mailgun_domain');
            $mailgunSecret = Setting::get('mailgun_secret');
            $mailgunEndpoint = Setting::get('mailgun_endpoint');
            $mailFromAddress = Setting::get('mail_from_address');
            $mailFromName = Setting::get('mail_from_name');

            if (!empty($mailgunDomain)) {
                Config::set('services.mailgun.domain', $mailgunDomain);
            }
            if (!empty($mailgunSecret)) {
                Config::set('services.mailgun.secret', $mailgunSecret);
            }
            if (!empty($mailgunEndpoint)) {
                Config::set('services.mailgun.endpoint', $mailgunEndpoint);
            }
            if (!empty($mailFromAddress)) {
                Config::set('mail.from.address', $mailFromAddress);
            }
            if (!empty($mailFromName)) {
                Config::set('mail.from.name', $mailFromName);
            }

            // 2. Razorpay Gateway Config
            $razorpayKeyId = Setting::get('razorpay_key_id');
            $razorpayKeySecret = Setting::get('razorpay_key_secret');
            $razorpayWebhookSecret = Setting::get('razorpay_webhook_secret');

            if (!empty($razorpayKeyId)) {
                Config::set('services.razorpay.key_id', $razorpayKeyId);
            }
            if (!empty($razorpayKeySecret)) {
                Config::set('services.razorpay.key_secret', $razorpayKeySecret);
            }
            if (!empty($razorpayWebhookSecret)) {
                Config::set('services.razorpay.webhook_secret', $razorpayWebhookSecret);
            }

            // 3. WhatsApp Meta Cloud API Config
            $whatsappToken = Setting::get('whatsapp_access_token');
            $whatsappPhoneId = Setting::get('whatsapp_phone_number_id');
            $whatsappBusinessId = Setting::get('whatsapp_business_account_id');
            $whatsappTemplate = Setting::get('whatsapp_otp_template');
            $whatsappEnabled = Setting::get('whatsapp_otp_enabled');

            if (!empty($whatsappToken)) {
                Config::set('services.whatsapp.access_token', $whatsappToken);
            }
            if (!empty($whatsappPhoneId)) {
                Config::set('services.whatsapp.phone_number_id', $whatsappPhoneId);
            }
            if (!empty($whatsappBusinessId)) {
                Config::set('services.whatsapp.business_account_id', $whatsappBusinessId);
            }
            if (!empty($whatsappTemplate)) {
                Config::set('services.whatsapp.otp_template', $whatsappTemplate);
            }
            Config::set('services.whatsapp.otp_enabled', (bool)$whatsappEnabled);

        } catch (\Throwable $e) {
            // Silently ignore if DB is not reachable during early bootstrap / migrations
        }
    }
}
