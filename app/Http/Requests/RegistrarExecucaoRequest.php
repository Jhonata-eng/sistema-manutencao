<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaDinheiro;
use App\Http\Requests\Concerns\ValidaAnexo;
use App\Http\Requests\Concerns\ValidaExecucao;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegistrarExecucaoRequest extends FormRequest
{
    use NormalizaDinheiro, ValidaAnexo, ValidaExecucao;

    public function authorize(): bool
    {
        return $this->user()->can('registrarExecucao', $this->route('manutencao'));
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarDinheiro('valor_real', 'anexo_valor_nota');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [...$this->regrasExecucao($this->route('manutencao')->fornecedor_id), ...$this->regrasAnexo(false)];
    }
}
