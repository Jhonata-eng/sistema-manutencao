<?php

namespace App\Http\Requests;

use App\Models\Perfil;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SalvarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->route('usuario');

        return ! $usuario || $this->user()->can('update', $usuario);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($usuario)],
            'perfil_id' => ['bail', 'required', 'integer', Rule::exists('perfis', 'id'), function (string $atributo, mixed $valor, Closure $falhar) {
                if (! $this->user()->podeConcederPerfil(Perfil::with('permissoes')->find($valor))) {
                    $falhar('Você não pode atribuir um perfil com permissões que você não tem.');
                }
            }],
            'cidades' => ['array'],
            'cidades.*' => ['bail', 'integer', Rule::exists('cidades', 'id'), function (string $atributo, mixed $valor, Closure $falhar) {
                if (! $this->user()->podeAcessarCidade((int) $valor)) {
                    $falhar('Você só pode vincular cidades às quais tem acesso.');
                }
            }],
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
