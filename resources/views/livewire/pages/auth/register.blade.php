<?php

use App\Models\User;
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

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'mobile' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

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
                placeholder="e.g. Sandeep Rathod" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Email Address</label>
            <input wire:model="email" id="email" type="email" name="email" required autocomplete="username" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-2.5 text-sm transition duration-250" 
                placeholder="e.g. sandeep@gmail.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Mobile Number -->
        <div>
            <label for="mobile" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Mobile Number</label>
            <input wire:model="mobile" id="mobile" type="text" name="mobile" required autocomplete="mobile" 
                class="block w-full rounded-xl border border-slate-800 bg-slate-950/70 text-white placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 shadow-inner px-4 py-2.5 text-sm transition duration-250" 
                placeholder="e.g. 9664588677" />
            <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
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
                class="w-full flex justify-center items-center px-6 py-3 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/10 focus:outline-none focus:ring-2 focus:ring-amber-500">
                Register
            </button>
        </div>

        <!-- Login Link -->
        <div class="text-center pt-3 border-t border-slate-900 text-xs text-slate-500">
            Already registered? 
            <a href="{{ route('login') }}" class="text-amber-455 hover:text-amber-400 font-bold transition" wire:navigate>Sign in here</a>
        </div>
    </form>
</div>
