<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarResponsavelRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'setor' => ['nullable', 'string', 'max:60'],
            'user_id' => [
                'nullable', 'integer', Rule::exists('users', 'id'),
                Rule::unique('responsaveis')->ignore($this->route('responsavel')),
            ],
            'ativo' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['user_id.unique' => 'Este usuário já está vinculado a outro responsável.'];
    }
}
