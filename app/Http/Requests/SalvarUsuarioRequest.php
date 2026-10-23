<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SalvarUsuarioRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($usuario)],
            'perfil_id' => ['required', 'integer', Rule::exists('perfis', 'id')],
            'cidades' => ['array'],
            'cidades.*' => ['integer', Rule::exists('cidades', 'id')],
            // Na edição a senha só muda pela tela "Redefinir senha".
            'password' => [$usuario ? 'prohibited' : 'required', 'confirmed', Password::defaults()],
            'ativo' => ['required', 'boolean', function (string $atributo, mixed $valor, Closure $falhar) use ($usuario) {
                if (! $valor && $usuario?->is($this->user())) {
                    $falhar('Você não pode inativar o próprio usuário.');
                }
            }],
        ];
    }
}
