<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReagendarManutencaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancelarReagendar', $this->route('manutencao'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nova_data' => [
                'required', 'date', 'after_or_equal:today',
                'not_in:'.$this->route('manutencao')->data_prevista->toDateString(),
            ],
            'motivo_reagendamento' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nova_data.after_or_equal' => 'A nova data deve ser hoje ou uma data futura.',
            'nova_data.not_in' => 'A nova data é igual à data prevista atual.',
        ];
    }
}
