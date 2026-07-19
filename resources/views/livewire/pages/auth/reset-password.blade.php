<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h3 class="text-2xl font-black text-white">Create New Password</h3>
        <p class="text-xs text-slate-400 mt-1">Set a new password for your account</p>
    </div>

    <form wire:submit="resetPassword" class="space-y-4">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Email Address</label>
            <input wire:model="email" id="email" type="email" name="email" required autofocus autocomplete="username" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-2.5 text-sm transition duration-250" 
                placeholder="Enter your email address" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

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

        <div class="pt-2 flex justify-end">
            <button type="submit" 
                class="w-full flex justify-center items-center px-6 py-3 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/10 focus:outline-none focus:ring-2 focus:ring-amber-500">
                Reset Password
            </button>
        </div>
    </form>
</div>
