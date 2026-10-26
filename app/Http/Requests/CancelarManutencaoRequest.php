<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CancelarManutencaoRequest extends FormRequest
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
        return ['motivo_cancelamento' => ['required', 'string', 'max:1000']];
    }
}
