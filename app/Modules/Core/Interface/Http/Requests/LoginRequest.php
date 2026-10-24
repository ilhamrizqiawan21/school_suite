<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Requests;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'identity' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identity = (string) $this->input('identity');
        $password = (string) $this->input('password');
        $remember = (bool) $this->boolean('remember');

        // Determine if identity is an email address or username
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Check if user exists and is active
        $user = User::where($field, $identity)->first();

        if ($user && ! $user->is_active) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'identity' => 'Akun ini telah dinonaktifkan. Hubungi administrator sekolah.',
            ]);
        }

        if (! Auth::attempt([$field => $identity, 'password' => $password], $remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'identity' => 'Identitas atau kata sandi yang Anda masukkan tidak cocok.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identity' => sprintf('Terlalu banyak percobaan login. Silakan coba lagi dalam %d detik.', $seconds),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('identity')).'|'.$this->ip());
    }
}
