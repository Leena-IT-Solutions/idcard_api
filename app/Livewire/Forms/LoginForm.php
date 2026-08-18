<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string')]
    public string $login = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginField = str_contains($this->login, '@') ? 'email' : 'mobile';

        if ($loginField === 'mobile') {
            $validator = \Illuminate\Support\Facades\Validator::make(
                ['mobile' => $this->login],
                ['mobile' => ['required', 'regex:/^[6-9]\d{9}$/']],
                ['mobile.regex' => 'The mobile number must be a 10-digit number starting with 6-9.']
            );
            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'form.login' => $validator->errors()->first('mobile'),
                ]);
            }
        } else {
            $validator = \Illuminate\Support\Facades\Validator::make(
                ['email' => $this->login],
                ['email' => ['required', 'email']],
                ['email.email' => 'The email must be a valid email address.']
            );
            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'form.login' => $validator->errors()->first('email'),
                ]);
            }
        }

        if (! Auth::attempt([$loginField => $this->login, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->login).'|'.request()->ip());
    }
}
