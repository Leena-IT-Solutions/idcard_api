<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-8">
        <h3 class="text-2xl font-black text-white">Welcome Back</h3>
        <p class="text-xs text-slate-400 mt-1">Sign in to your iCard Maker administrator account</p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <!-- Email Address or Mobile -->
        <div>
            <label for="login" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Email or Mobile</label>
            <input wire:model="form.login" id="login" type="text" name="login" required autofocus autocomplete="username" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-3 text-sm transition duration-250" 
                placeholder="Enter your email or phone number" />
            <x-input-error :messages="$errors->get('form.login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex justify-between items-center mb-1.5">
                <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-amber-400 hover:text-amber-350 transition" href="{{ route('password.request') }}" wire:navigate>
                        Forgot?
                    </a>
                @endif
            </div>
            <input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-3 text-sm transition duration-250" 
                placeholder="••••••••" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember" class="inline-flex items-center cursor-pointer select-none">
                <input wire:model="form.remember" id="remember" type="checkbox" 
                    class="rounded border-slate-800 bg-slate-950 text-amber-500 shadow-sm focus:ring-amber-500/20 focus:ring-offset-0 focus:ring-2 h-4 w-4 transition duration-200">
                <span class="ms-2 text-xs text-slate-400 font-medium">Keep me signed in</span>
            </label>
        </div>

        <!-- Submit -->
        <div class="pt-2">
            <button type="submit" 
                class="w-full flex justify-center items-center px-6 py-3.5 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/10 focus:outline-none focus:ring-2 focus:ring-amber-500">
                Sign In
            </button>
        </div>
        
        <!-- Registration Link -->
        <div class="text-center pt-4 border-t border-slate-900 text-xs text-slate-500">
            Don't have an account? 
            <a href="{{ route('register') }}" class="text-amber-455 hover:text-amber-400 font-bold transition" wire:navigate>Create one here</a>
        </div>
    </form>
</div>
