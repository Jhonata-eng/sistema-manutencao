<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaDinheiro;
use App\Http\Requests\Concerns\ValidaAnexo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EnviarAnexoRequest extends FormRequest
{
    use NormalizaDinheiro, ValidaAnexo;

    public function authorize(): bool
    {
        return $this->user()->can('enviarAnexo', $this->route('manutencao'));
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarDinheiro('anexo_valor_nota');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->regrasAnexo(true);
    }
}
