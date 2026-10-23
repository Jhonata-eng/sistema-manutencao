<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarTipoEquipamentoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:60', Rule::unique('tipos_equipamento')->ignore($this->route('tipo'))],
            'ativo' => ['required', 'boolean'],
        ];
    }
}
