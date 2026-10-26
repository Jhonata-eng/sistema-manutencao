<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaDinheiro;
use App\Http\Requests\Concerns\ValidaAnexo;
use App\Http\Requests\Concerns\ValidaExecucao;
use App\Models\Ativo;
use App\Models\Manutencao;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LancarNaoProgramadaRequest extends FormRequest
{
    use NormalizaDinheiro, ValidaAnexo, ValidaExecucao;

    protected function prepareForValidation(): void
    {
        $this->normalizarDinheiro('valor_real', 'anexo_valor_nota');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ativo_id' => ['bail', 'required', 'integer', Rule::exists('ativos', 'id')->withoutTrashed(),
                function (string $atributo, mixed $valor, Closure $falhar) {
                    if (! $this->user()->can('lancarNaoProgramada', [Manutencao::class, $this->ativo()])) {
                        $falhar('Você não pode lançar manutenção para este ativo.');
                    }
                },
            ],
            ...$this->regrasExecucao(),
            ...$this->regrasAnexo(false),
        ];
    }

    public function ativo(): Ativo
    {
        return Ativo::findOrFail($this->integer('ativo_id'));
    }
}
