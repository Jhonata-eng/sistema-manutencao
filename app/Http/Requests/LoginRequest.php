<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Autentica com limite de tentativas por e-mail + IP. Usuário inativo recebe
     * a mesma mensagem de credenciais inválidas, para não revelar quais e-mails existem.
     *
     * @throws ValidationException
     */
    public function autenticar(): void
    {
        $chave = Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
        $limite = config('manutencao.login_max_tentativas');

        if (RateLimiter::tooManyAttempts($chave, $limite)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($chave)]),
            ]);
        }

        $credenciais = [...$this->only('email', 'password'), 'ativo' => true];

        if (! Auth::attempt($credenciais, $this->boolean('lembrar'))) {
            RateLimiter::hit($chave, 60);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($chave);
    }
}
