<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Nova senha. Quando o usuário troca a própria senha, exige também a senha atual.
 */
class SenhaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'senha_atual' => $this->routeIs('minha-senha.*') ? ['required', 'current_password'] : ['exclude'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
