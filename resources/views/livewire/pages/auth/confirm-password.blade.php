<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h3 class="text-2xl font-black text-white">Confirm Password</h3>
        <p class="text-xs text-slate-400 mt-1">This is a secure area. Please confirm your password before continuing.</p>
    </div>

    <form wire:submit="confirmPassword" class="space-y-5">
        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Password</label>
            <input wire:model="password" id="password" type="password" name="password" required autocomplete="current-password" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-3 text-sm transition duration-250" 
                placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="pt-2 flex justify-end">
            <button type="submit" 
                class="px-6 py-3 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/10 focus:outline-none focus:ring-2 focus:ring-amber-500">
                Confirm
            </button>
        </div>
    </form>
</div>
