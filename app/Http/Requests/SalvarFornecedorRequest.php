<?php

namespace App\Http\Requests;

use App\Rules\CpfCnpj;
use App\Support\Documento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarFornecedorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['documento' => Documento::normalizar($this->input('documento'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'documento' => ['nullable', new CpfCnpj, Rule::unique('fornecedores')->ignore($this->route('fornecedor'))],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'ativo' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['documento.unique' => 'Já existe um fornecedor com este CPF/CNPJ.'];
    }
}
