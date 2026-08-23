<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Component;

new class extends Component {
    // Razorpay Fields
    public string $razorpayKeyId = '';
    public string $razorpayKeySecret = '';
    public string $razorpayWebhookSecret = '';
    public bool $showRazorpaySecret = false;
    public bool $showRazorpayWebhookSecret = false;

    // Mailgun Fields
    public string $mailgunDomain = '';
    public string $mailgunEndpoint = 'api.mailgun.net';
    public string $mailgunSecret = '';
    public string $mailFromAddress = '';
    public string $mailFromName = '';
    public bool $showMailgunSecret = false;
    public string $testEmailRecipient = '';
    public bool $showTestEmailModal = false;

    // WhatsApp Fields
    public string $whatsappAccessToken = '';
    public string $whatsappPhoneNumberId = '';
    public string $whatsappBusinessAccountId = '';
    public string $whatsappOtpTemplate = '';
    public bool $whatsappOtpEnabled = false;
    public bool $showWhatsappToken = false;
    public string $testWhatsappRecipient = '';
    public bool $showTestWhatsappModal = false;

    // Toast
    public string $toastMessage = '';
    public string $toastType = 'success';

    public function mount(): void
    {
        // Razorpay
        $this->razorpayKeyId = (string) Setting::get('razorpay_key_id', config('services.razorpay.key_id', ''));
        $this->razorpayKeySecret = (string) Setting::get('razorpay_key_secret', config('services.razorpay.key_secret', ''));
        $this->razorpayWebhookSecret = (string) Setting::get('razorpay_webhook_secret', config('services.razorpay.webhook_secret', ''));

        // Mailgun
        $this->mailgunDomain = (string) Setting::get('mailgun_domain', config('services.mailgun.domain', ''));
        $this->mailgunEndpoint = (string) Setting::get('mailgun_endpoint', config('services.mailgun.endpoint', 'api.mailgun.net'));
        $this->mailgunSecret = (string) Setting::get('mailgun_secret', config('services.mailgun.secret', ''));
        $this->mailFromAddress = (string) Setting::get('mail_from_address', config('mail.from.address', ''));
        $this->mailFromName = (string) Setting::get('mail_from_name', config('mail.from.name', ''));
        $this->testEmailRecipient = auth()->user()->email ?? '';

        // WhatsApp
        $this->whatsappAccessToken = (string) Setting::get('whatsapp_access_token', config('services.whatsapp.access_token', ''));
        $this->whatsappPhoneNumberId = (string) Setting::get('whatsapp_phone_number_id', config('services.whatsapp.phone_number_id', ''));
        $this->whatsappBusinessAccountId = (string) Setting::get('whatsapp_business_account_id', config('services.whatsapp.business_account_id', ''));
        $this->whatsappOtpTemplate = (string) Setting::get('whatsapp_otp_template', config('services.whatsapp.otp_template', ''));
        $this->whatsappOtpEnabled = (bool) Setting::get('whatsapp_otp_enabled', config('services.whatsapp.otp_enabled', false));
        $this->testWhatsappRecipient = auth()->user()->mobile ?? '';
    }

    public function saveRazorpay(): void
    {
        $this->validate([
            'razorpayKeyId' => 'nullable|string|max:255',
            'razorpayKeySecret' => 'nullable|string|max:500',
            'razorpayWebhookSecret' => 'nullable|string|max:500',
        ]);

        Setting::set('razorpay_key_id', trim($this->razorpayKeyId), 'razorpay', false);
        Setting::set('razorpay_key_secret', trim($this->razorpayKeySecret), 'razorpay', true);
        Setting::set('razorpay_webhook_secret', trim($this->razorpayWebhookSecret), 'razorpay', true);

        // Update runtime config
        config(['services.razorpay.key_id' => trim($this->razorpayKeyId)]);
        config(['services.razorpay.key_secret' => trim($this->razorpayKeySecret)]);
        config(['services.razorpay.webhook_secret' => trim($this->razorpayWebhookSecret)]);

        $this->toast('Razorpay payment gateway settings saved successfully!');
    }

    public function testRazorpay(): void
    {
        if (empty($this->razorpayKeyId) || empty($this->razorpayKeySecret)) {
            $this->toast('Please enter both Razorpay Key ID and Secret first.', 'warning');
            return;
        }

        try {
            $response = Http::withBasicAuth(trim($this->razorpayKeyId), trim($this->razorpayKeySecret))
                ->get('https://api.razorpay.com/v1/payments', ['count' => 1]);

            if ($response->successful()) {
                $this->toast('Razorpay connection verified successfully! API credentials are valid.', 'success');
            } else {
                $msg = $response->json('error.description') ?? 'Authentication failed with Razorpay API.';
                $this->toast('Razorpay Test Failed: ' . $msg, 'error');
            }
        } catch (\Throwable $e) {
            $this->toast('Connection Error: ' . $e->getMessage(), 'error');
        }
    }

    public function saveMailgun(): void
    {
        $this->validate([
            'mailgunDomain' => 'nullable|string|max:255',
            'mailgunEndpoint' => 'nullable|string|max:255',
            'mailgunSecret' => 'nullable|string|max:500',
            'mailFromAddress' => 'nullable|email|max:255',
            'mailFromName' => 'nullable|string|max:255',
        ]);

        $domain = trim($this->mailgunDomain);
        $secret = trim($this->mailgunSecret);
        $endpoint = trim($this->mailgunEndpoint) ?: 'api.mailgun.net';
        $fromAddress = trim($this->mailFromAddress);
        $fromName = trim($this->mailFromName);

        Setting::set('mailgun_domain', $domain, 'mailgun', false);
        Setting::set('mailgun_endpoint', $endpoint, 'mailgun', false);
        Setting::set('mailgun_secret', $secret, 'mailgun', true);
        Setting::set('mail_from_address', $fromAddress, 'mailgun', false);
        Setting::set('mail_from_name', $fromName, 'mailgun', false);

        // Update runtime config
        config(['services.mailgun.domain' => $domain]);
        config(['services.mailgun.endpoint' => $endpoint]);
        config(['services.mailgun.secret' => $secret]);
        config(['services.mailgun.scheme' => 'https']);
        config(['mail.mailers.mailgun.transport' => 'mailgun']);

        if (!empty($domain) && !empty($secret)) {
            config(['mail.default' => 'mailgun']);
        }
        if (!empty($fromAddress)) {
            config(['mail.from.address' => $fromAddress]);
        }
        if (!empty($fromName)) {
            config(['mail.from.name' => $fromName]);
        }

        $this->toast('Mailgun email gateway settings saved and activated successfully!');
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'testEmailRecipient' => 'required|email',
        ]);

        if (empty($this->mailgunDomain) || empty($this->mailgunSecret)) {
            $this->toast('Please configure and save Mailgun Domain and API Secret Key first.', 'warning');
            return;
        }

        try {
            $domain = trim($this->mailgunDomain);
            $secret = trim($this->mailgunSecret);
            $endpoint = trim($this->mailgunEndpoint) ?: 'api.mailgun.net';
            $fromAddress = trim($this->mailFromAddress) ?: 'no-reply@' . $domain;
            $fromName = trim($this->mailFromName) ?: config('app.name', 'iCard Studio');

            $response = Http::withBasicAuth('api', $secret)
                ->asForm()
                ->post("https://{$endpoint}/v3/{$domain}/messages", [
                    'from' => "{$fromName} <{$fromAddress}>",
                    'to' => $this->testEmailRecipient,
                    'subject' => 'Test Email from ' . config('app.name', 'iCard Studio'),
                    'text' => "Hello!\n\nThis is a test email confirming that your Mailgun email gateway configuration is working properly.\n\nSent at: " . now()->toDayDateTimeString(),
                ]);

            if ($response->successful()) {
                $this->showTestEmailModal = false;
                $this->toast("Test email sent successfully to {$this->testEmailRecipient}!", 'success');
            } else {
                $status = $response->status();
                $msg = $response->json('message') ?? $response->body() ?? 'Failed to send test email through Mailgun.';
                
                if ($status === 401) {
                    $this->toast("Mailgun 401 Forbidden: Invalid API Key or Region Mismatch. Please check if your domain was created under EU Region (use api.eu.mailgun.net) or US Region (use api.mailgun.net), and ensure you are using the Private Sending API Key.", 'error');
                } elseif ($status === 404) {
                    $this->toast("Mailgun 404 Not Found: Domain '{$domain}' was not found in Mailgun under endpoint {$endpoint}.", 'error');
                } else {
                    $this->toast("Mailgun Error (HTTP {$status}): " . $msg, 'error');
                }
            }
        } catch (\Throwable $e) {
            $this->toast('Email Error: ' . $e->getMessage(), 'error');
        }
    }

    public function saveWhatsapp(): void
    {
        $this->validate([
            'whatsappAccessToken' => 'nullable|string|max:1000',
            'whatsappPhoneNumberId' => 'nullable|string|max:255',
            'whatsappBusinessAccountId' => 'nullable|string|max:255',
            'whatsappOtpTemplate' => 'nullable|string|max:255',
            'whatsappOtpEnabled' => 'boolean',
        ]);

        Setting::set('whatsapp_access_token', trim($this->whatsappAccessToken), 'whatsapp', true);
        Setting::set('whatsapp_phone_number_id', trim($this->whatsappPhoneNumberId), 'whatsapp', false);
        Setting::set('whatsapp_business_account_id', trim($this->whatsappBusinessAccountId), 'whatsapp', false);
        Setting::set('whatsapp_otp_template', trim($this->whatsappOtpTemplate), 'whatsapp', false);
        Setting::set('whatsapp_otp_enabled', (int)$this->whatsappOtpEnabled, 'whatsapp', false);

        // Update runtime config
        config(['services.whatsapp.access_token' => trim($this->whatsappAccessToken)]);
        config(['services.whatsapp.phone_number_id' => trim($this->whatsappPhoneNumberId)]);
        config(['services.whatsapp.business_account_id' => trim($this->whatsappBusinessAccountId)]);
        config(['services.whatsapp.otp_template' => trim($this->whatsappOtpTemplate)]);
        config(['services.whatsapp.otp_enabled' => $this->whatsappOtpEnabled]);

        $this->toast('WhatsApp Meta Cloud API settings saved successfully!');
    }

    public function sendTestWhatsapp(): void
    {
        $this->validate([
            'testWhatsappRecipient' => 'required|regex:/^[6-9]\d{9}$/',
        ], [
            'testWhatsappRecipient.regex' => 'Please enter a valid 10-digit Indian mobile number.',
        ]);

        if (empty($this->whatsappAccessToken) || empty($this->whatsappPhoneNumberId)) {
            $this->toast('Please configure and save WhatsApp Access Token and Phone Number ID first.', 'warning');
            return;
        }

        try {
            $otpService = new \App\Services\WhatsAppOtpService();
            $result = $otpService->sendOtp($this->testWhatsappRecipient);

            if ($result['success']) {
                $this->showTestWhatsappModal = false;
                $this->toast("Test WhatsApp OTP message successfully dispatched to +91 {$this->testWhatsappRecipient}!", 'success');
            } else {
                $this->toast('WhatsApp Error: ' . ($result['message'] ?? 'Unknown error'), 'error');
            }
        } catch (\Throwable $e) {
            $this->toast('WhatsApp Error: ' . $e->getMessage(), 'error');
        }
    }

    private function toast(string $msg, string $type = 'success'): void
    {
        $this->toastMessage = $msg;
        $this->toastType = $type;
    }
}; ?>

<div class="space-y-8 pb-12">
    <!-- Toast Notification Banner -->
    @if($toastMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
             class="p-4 rounded-2xl flex items-center justify-between shadow-2xl transition-all 
             {{ $toastType === 'success' ? 'bg-emerald-600 text-white' : ($toastType === 'warning' ? 'bg-amber-500 text-white' : 'bg-rose-600 text-white') }}">
            <div class="flex items-center gap-3">
                @if($toastType === 'success')
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @elseif($toastType === 'warning')
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                @else
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @endif
                <span class="font-bold text-sm">{{ $toastMessage }}</span>
            </div>
            <button @click="show = false" class="text-white/80 hover:text-white text-sm font-bold ml-4">✕</button>
        </div>
    @endif

    <!-- Command Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">{{ __('System Integrations & Settings') }}</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Configure third-party gateways for billing, transactional emails, and WhatsApp OTP verification.') }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/60 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ __('Database Encryption Active') }}
            </span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 1. RAZORPAY PAYMENT & PAYOUT GATEWAY -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100 dark:border-gray-700">
            <div>
                <h2 class="text-xs font-black uppercase tracking-widest text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <span class="text-indigo-600 dark:text-indigo-400">💳</span>
                    {{ __('RAZORPAY PAYMENT & PAYOUT GATEWAY') }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Configure your Razorpay API credentials and RazorpayX account details for client payments and automated wallet top-ups.') }}
                </p>
            </div>
            <button wire:click="testRazorpay" wire:loading.attr="disabled" type="button" 
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-xl transition shadow-sm self-start sm:self-auto cursor-pointer shrink-0">
                <span wire:loading.remove wire:target="testRazorpay">⚡ {{ __('Test Connection') }}</span>
                <span wire:loading wire:target="testRazorpay">{{ __('Testing...') }}</span>
            </button>
        </div>

        <form wire:submit="saveRazorpay" class="mt-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Razorpay Key ID -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Razorpay Key ID') }}
                    </label>
                    <input type="text" wire:model="razorpayKeyId" placeholder="rzp_test_..." 
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    @error('razorpayKeyId') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Razorpay Key Secret -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                            {{ __('Razorpay Key Secret') }}
                        </label>
                        <button type="button" wire:click="$toggle('showRazorpaySecret')" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ $showRazorpaySecret ? __('Hide') : __('Reveal') }}
                        </button>
                    </div>
                    <div class="relative">
                        <input type="{{ $showRazorpaySecret ? 'text' : 'password' }}" wire:model="razorpayKeySecret" placeholder="••••••••••••••••••••••••" 
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    </div>
                    @error('razorpayKeySecret') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Razorpay Webhook Secret -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                        {{ __('Razorpay Webhook Secret (Optional)') }}
                    </label>
                    <button type="button" wire:click="$toggle('showRazorpayWebhookSecret')" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ $showRazorpayWebhookSecret ? __('Hide') : __('Reveal') }}
                    </button>
                </div>
                <input type="{{ $showRazorpayWebhookSecret ? 'text' : 'password' }}" wire:model="razorpayWebhookSecret" placeholder="Webhook secret from Razorpay Dashboard..." 
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                    {{ __('Webhook URL:') }} <code class="font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-1.5 py-0.5 rounded">{{ url('/razorpay/webhook') }}</code>
                </p>
                @error('razorpayWebhookSecret') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveRazorpay" 
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-lg shadow-indigo-600/20 cursor-pointer flex items-center gap-2">
                    <span wire:loading.remove wire:target="saveRazorpay">{{ __('Save Razorpay Settings') }}</span>
                    <span wire:loading wire:target="saveRazorpay">{{ __('Saving...') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. MAILGUN EMAIL GATEWAY -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100 dark:border-gray-700">
            <div>
                <h2 class="text-xs font-black uppercase tracking-widest text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <span class="text-indigo-600 dark:text-indigo-400">📧</span>
                    {{ __('MAILGUN EMAIL GATEWAY') }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Configure your Mailgun credentials to handle transactional mail delivery, invitation dispatches, and system alerts.') }}
                </p>
            </div>
            <button wire:click="$toggle('showTestEmailModal')" type="button" 
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-xl transition shadow-sm self-start sm:self-auto cursor-pointer shrink-0">
                ✉️ {{ __('Send Test Email') }}
            </button>
        </div>

        <form wire:submit="saveMailgun" class="mt-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Mailgun Domain -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Mailgun Domain') }}
                    </label>
                    <input type="text" wire:model="mailgunDomain" placeholder="e.g. infoleena.com or mg.yourdomain.com" 
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    @error('mailgunDomain') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Mailgun Endpoint -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Mailgun Endpoint / Region') }}
                    </label>
                    <select wire:model="mailgunEndpoint" 
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <option value="api.mailgun.net">api.mailgun.net (US / North America Region — Default)</option>
                        <option value="api.eu.mailgun.net">api.eu.mailgun.net (EU / Europe Region)</option>
                    </select>
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                        {{ __('Select the region matching where your domain is hosted in Mailgun.') }}
                    </p>
                    @error('mailgunEndpoint') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Mailgun API Secret Key -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                        {{ __('Mailgun API Secret / Sending Key') }}
                    </label>
                    <button type="button" wire:click="$toggle('showMailgunSecret')" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ $showMailgunSecret ? __('Hide') : __('Reveal') }}
                    </button>
                </div>
                <input type="{{ $showMailgunSecret ? 'text' : 'password' }}" wire:model="mailgunSecret" placeholder="key-xxxxxxxx... or 32-char alphanumeric sending key" 
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                    {{ __('Use the Private Sending API Key from Mailgun Dashboard (Domain Settings > Sending API Keys or Account Settings > API Security). Do not use the Public Validation or Webhook Key.') }}
                </p>
                @error('mailgunSecret') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Mail From Address & Name -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Default From Email Address') }}
                    </label>
                    <input type="email" wire:model="mailFromAddress" placeholder="e.g. notifications@infoleena.com" 
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    @error('mailFromAddress') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Default From Sender Name') }}
                    </label>
                    <input type="text" wire:model="mailFromName" placeholder="e.g. iCard Studio Portal" 
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    @error('mailFromName') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveMailgun" 
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-lg shadow-indigo-600/20 cursor-pointer flex items-center gap-2">
                    <span wire:loading.remove wire:target="saveMailgun">{{ __('Save Mailgun Settings') }}</span>
                    <span wire:loading wire:target="saveMailgun">{{ __('Saving...') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. WHATSAPP OTP INTEGRATION (META CLOUD API) -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100 dark:border-gray-700">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <span class="text-emerald-500">💬</span>
                        {{ __('WHATSAPP OTP INTEGRATION (META CLOUD API)') }}
                    </h2>
                    @if($whatsappOtpEnabled)
                        <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-md text-[9px] font-extrabold uppercase tracking-wider">
                            {{ __('Active on Registration') }}
                        </span>
                    @else
                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 rounded-md text-[9px] font-extrabold uppercase tracking-wider">
                            {{ __('Disabled (Direct Registration Active)') }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Configure your Meta WhatsApp Cloud API credentials to enable WhatsApp OTP verification for mobile registration and password reset.') }}
                </p>
            </div>
            <button wire:click="$toggle('showTestWhatsappModal')" type="button" 
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-xl transition shadow-sm self-start sm:self-auto cursor-pointer shrink-0">
                📲 {{ __('Send Test OTP') }}
            </button>
        </div>

        <form wire:submit="saveWhatsapp" class="mt-6 space-y-6">
            <!-- Master Toggle: Enable WhatsApp OTP for Registration -->
            <div class="p-4 rounded-2xl border flex items-center justify-between gap-4 transition {{ $whatsappOtpEnabled ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/60' : 'bg-gray-50 dark:bg-gray-900/40 border-gray-200 dark:border-gray-700' }}">
                <div>
                    <span class="font-bold text-xs text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        {{ __('Enforce WhatsApp OTP on User Registration') }}
                    </span>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('When enabled, new users registering with a mobile number must verify via a 6-digit WhatsApp OTP. If disabled, standard registration continues without OTP.') }}
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" wire:model="whatsappOtpEnabled" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-checked:bg-emerald-600 transition-colors"></div>
                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                </label>
            </div>

            <!-- WhatsApp API Access Token -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                        {{ __('WhatsApp API Access Token') }}
                    </label>
                    <button type="button" wire:click="$toggle('showWhatsappToken')" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ $showWhatsappToken ? __('Hide') : __('Reveal') }}
                    </button>
                </div>
                <input type="{{ $showWhatsappToken ? 'text' : 'password' }}" wire:model="whatsappAccessToken" placeholder="EAA..." 
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                @error('whatsappAccessToken') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Phone Number ID -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Phone Number ID') }}
                    </label>
                    <input type="text" wire:model="whatsappPhoneNumberId" placeholder="1235536072981098" 
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    @error('whatsappPhoneNumberId') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- WhatsApp Business Account ID -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('WhatsApp Business Account ID') }}
                    </label>
                    <input type="text" wire:model="whatsappBusinessAccountId" placeholder="2934433673575027" 
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    @error('whatsappBusinessAccountId') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- OTP Template Name -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('OTP Template Name') }}
                    </label>
                    <input type="text" wire:model="whatsappOtpTemplate" placeholder="otp_verification" 
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                        {{ __('Meta template name (e.g. otp_verification) with dynamic parameter {1}') }}
                    </p>
                    @error('whatsappOtpTemplate') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Meta Webhook Configuration for WhatsApp Dashboard -->
            <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800/60 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-indigo-900 dark:text-indigo-200 flex items-center gap-2">
                        <span>⚡</span> {{ __('Meta Webhook Configuration Details (For Meta Developer Dashboard)') }}
                    </span>
                    <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold">Copy into Meta Step 2</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Callback URL</span>
                        <div class="font-mono font-black text-indigo-600 dark:text-indigo-400 select-all mt-0.5 break-all">
                            {{ url('/whatsapp/webhook') }}
                        </div>
                    </div>
                    <div class="p-3 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Verify Token</span>
                        <div class="font-mono font-black text-emerald-600 dark:text-emerald-400 select-all mt-0.5">
                            icard_meta_verify_token_2026
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveWhatsapp" 
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-lg shadow-indigo-600/20 cursor-pointer flex items-center gap-2">
                    <span wire:loading.remove wire:target="saveWhatsapp">{{ __('Save WhatsApp Settings') }}</span>
                    <span wire:loading wire:target="saveWhatsapp">{{ __('Saving...') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TEST EMAIL MODAL -->
    <!-- ========================================================================= -->
    @if($showTestEmailModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/80 backdrop-blur-sm transition-opacity" wire:click="$set('showTestEmailModal', false)"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-700 relative z-10">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <span>✉️</span> {{ __('Send Test Email via Mailgun') }}
                        </h3>
                        <button wire:click="$set('showTestEmailModal', false)" class="text-gray-400 hover:text-gray-500">✕</button>
                    </div>
                    <form wire:submit="sendTestEmail" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Recipient Email Address') }}
                            </label>
                            <input type="email" wire:model="testEmailRecipient" placeholder="youremail@domain.com" required 
                                   class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" />
                            @error('testEmailRecipient') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="$set('showTestEmailModal', false)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="sendTestEmail" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow">
                                <span wire:loading.remove wire:target="sendTestEmail">{{ __('Send Test Email') }}</span>
                                <span wire:loading wire:target="sendTestEmail">{{ __('Sending...') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- TEST WHATSAPP OTP MODAL -->
    <!-- ========================================================================= -->
    @if($showTestWhatsappModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/80 backdrop-blur-sm transition-opacity" wire:click="$set('showTestWhatsappModal', false)"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-700 relative z-10">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <span>💬</span> {{ __('Send Test WhatsApp OTP') }}
                        </h3>
                        <button wire:click="$set('showTestWhatsappModal', false)" class="text-gray-400 hover:text-gray-500">✕</button>
                    </div>
                    <form wire:submit="sendTestWhatsapp" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Recipient 10-digit Mobile Number') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-2.5 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-600 dark:text-gray-300">+91</span>
                                <input type="text" wire:model="testWhatsappRecipient" placeholder="9876543210" maxlength="10" required 
                                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500" />
                            </div>
                            @error('testWhatsappRecipient') <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="$set('showTestWhatsappModal', false)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="sendTestWhatsapp" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow">
                                <span wire:loading.remove wire:target="sendTestWhatsapp">{{ __('Send WhatsApp Test') }}</span>
                                <span wire:loading wire:target="sendTestWhatsapp">{{ __('Sending...') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
