<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="mb-6">
        <h3 class="text-2xl font-black text-white">Reset Password</h3>
        <p class="text-xs text-slate-400 mt-1">Enter your email to receive a password reset link</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Email Address</label>
            <input wire:model="email" id="email" type="email" name="email" required autofocus 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-3 text-sm transition duration-250" 
                placeholder="Enter your email address" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-2 flex items-center justify-between">
            <a class="text-xs font-semibold text-slate-400 hover:text-white transition" href="{{ route('login') }}" wire:navigate>
                &larr; Back to login
            </a>
            
            <button type="submit" 
                class="inline-flex items-center justify-center px-5 py-3 text-xs font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/10 focus:outline-none focus:ring-2 focus:ring-amber-500">
                Send Link
            </button>
        </div>
    </form>
</div>
