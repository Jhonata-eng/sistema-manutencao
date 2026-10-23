<?php

namespace App\Http\Requests;

use App\Models\Cidade;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarCidadeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['uf' => strtoupper((string) $this->input('uf'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => [
                'required', 'string', 'max:100',
                Rule::unique('cidades')->where('uf', $this->input('uf'))->ignore($this->route('cidade')),
            ],
            'uf' => ['required', Rule::in(Cidade::UFS)],
            'ativo' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['nome.unique' => 'Já existe uma cidade com este nome nesta UF.'];
    }
}
