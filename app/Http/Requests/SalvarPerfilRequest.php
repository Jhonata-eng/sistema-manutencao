<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarPerfilRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:60', Rule::unique('perfis')->ignore($this->route('perfil'))],
            'descricao' => ['nullable', 'string', 'max:255'],
            'permissoes' => ['array'],
            'permissoes.*' => ['integer', Rule::exists('permissoes', 'id')],
        ];
    }
}
