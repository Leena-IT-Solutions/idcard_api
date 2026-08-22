<?php

use App\Models\User;
use App\Services\WhatsAppOtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $mobile = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $createSchoolAccount = false;

    // WhatsApp OTP state
    public string $otp = '';
    public bool $otpSent = false;
    public string $otpMessage = '';
    public bool $isOtpVerified = false;

    public function getIsWhatsAppOtpRequiredProperty(): bool
    {
        return (new WhatsAppOtpService())->isConfigured();
    }

    public function sendWhatsAppOtp(): void
    {
        $this->validate([
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
        ], [
            'mobile.regex' => 'The mobile number must be a 10-digit number starting with 6-9.',
        ]);

        $otpService = new WhatsAppOtpService();
        $result = $otpService->sendOtp($this->mobile);

        if ($result['success']) {
            $this->otpSent = true;
            $this->otpMessage = 'WhatsApp OTP sent to +91 ' . $this->mobile . '. Enter the 6-digit code below.';
        } else {
            $this->addError('mobile', $result['message'] ?? 'Failed to dispatch WhatsApp OTP.');
        }
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ];

        if ($this->isWhatsAppOtpRequired) {
            $rules['otp'] = ['required', 'string', 'size:6'];
        }

        $validated = $this->validate($rules, [
            'mobile.regex' => 'The mobile number must be a 10-digit number starting with 6-9.',
            'otp.required' => 'Please enter the 6-digit WhatsApp OTP code.',
            'otp.size' => 'The WhatsApp OTP must be 6 digits.',
        ]);

        // Verify OTP if enabled
        if ($this->isWhatsAppOtpRequired) {
            $otpService = new WhatsAppOtpService();
            if (!$otpService->verifyOtp($this->mobile, $this->otp)) {
                $this->addError('otp', 'Invalid or expired WhatsApp OTP code. Please try again.');
                return;
            }
        }

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'password' => Hash::make($this->password),
        ]);

        if ($this->createSchoolAccount) {
            $user->assignRole('school_admin');
        } else {
            $user->assignRole('parent');
        }

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h3 class="text-2xl font-black text-white">Create Account</h3>
        <p class="text-xs text-slate-400 mt-1">Get started with your iCard Maker portal</p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <!-- Name -->
        <div>
            <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Full Name</label>
            <input wire:model="name" id="name" type="text" name="name" required autofocus autocomplete="name" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-2.5 text-sm transition duration-250" 
                placeholder="e.g. John Doe" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Email Address</label>
            <input wire:model="email" id="email" type="email" name="email" required autocomplete="username" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-2.5 text-sm transition duration-250" 
                placeholder="e.g. johndoe@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Mobile Number -->
        <div>
            <label for="mobile" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Mobile Number</label>
            <div class="flex gap-2">
                <input wire:model="mobile" id="mobile" type="text" name="mobile" required autocomplete="mobile" 
                    maxlength="10"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);"
                    class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-2.5 text-sm transition duration-250 font-mono" 
                    placeholder="e.g. 9876543210" />
                
                @if($this->isWhatsAppOtpRequired)
                    <button type="button" wire:click="sendWhatsAppOtp" wire:loading.attr="disabled" wire:target="sendWhatsAppOtp"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition shrink-0 flex items-center gap-1 shadow cursor-pointer">
                        <span wire:loading.remove wire:target="sendWhatsAppOtp">
                            {{ $otpSent ? __('Resend OTP') : __('Get WhatsApp OTP') }}
                        </span>
                        <span wire:loading wire:target="sendWhatsAppOtp">{{ __('Sending...') }}</span>
                    </button>
                @endif
            </div>
            <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
        </div>

        <!-- WhatsApp OTP Input (Appears only when OTP is enforced) -->
        @if($this->isWhatsAppOtpRequired)
            @if($otpMessage)
                <div class="p-3 bg-emerald-950/40 border border-emerald-800 rounded-xl text-xs text-emerald-300 flex items-center gap-2">
                    <span>💬</span>
                    <span>{{ $otpMessage }}</span>
                </div>
            @endif

            <div>
                <label for="otp" class="block text-xs font-bold text-emerald-400 uppercase tracking-widest mb-1.5">
                    WhatsApp 6-Digit Verification Code
                </label>
                <input wire:model="otp" id="otp" type="text" name="otp" required maxlength="6"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);"
                    class="block w-full rounded-xl border border-emerald-700 bg-slate-950/70 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-emerald-500 shadow-inner px-4 py-2.5 text-sm transition duration-250 font-mono tracking-widest" 
                    placeholder="123456" />
                <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            </div>
        @endif

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Password</label>
            <input wire:model="password" id="password" type="password" name="password" required autocomplete="new-password" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-2.5 text-sm transition duration-250" 
                placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Confirm Password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-2.5 text-sm transition duration-250" 
                placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Create School Account Option -->
        <div class="pt-1">
            <label for="createSchoolAccount" class="inline-flex items-center cursor-pointer select-none">
                <input wire:model="createSchoolAccount" id="createSchoolAccount" type="checkbox" name="createSchoolAccount"
                    class="rounded border-slate-800 bg-slate-950 text-amber-500 shadow-sm focus:ring-amber-500/20 focus:ring-offset-0 focus:ring-2 h-4 w-4 transition duration-200">
                <span class="ms-2 text-xs text-slate-400 font-medium">Create a school administrator account</span>
            </label>
        </div>

        <!-- Submit -->
        <div class="pt-2">
            <button type="submit" 
                class="w-full flex justify-center items-center px-6 py-3 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/10 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer">
                Register
            </button>
        </div>

        <!-- Login Link -->
        <div class="text-center pt-3 border-t border-slate-900 text-xs text-slate-500">
            Already registered? 
            <a href="{{ route('login') }}" class="text-amber-400 hover:text-amber-300 font-bold transition" wire:navigate>Sign in here</a>
        </div>
    </form>
</div>
