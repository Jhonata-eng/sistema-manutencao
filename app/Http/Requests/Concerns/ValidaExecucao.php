<?php

namespace App\Http\Requests\Concerns;

use App\Models\Fornecedor;

/** Campos de execução comuns ao registro de execução e à manutenção não programada. */
trait ValidaExecucao
{
    protected function regrasExecucao(?int $fornecedorAtual = null): array
    {
        return [
            'data_execucao' => ['required', 'date', 'before_or_equal:today'],
            'valor_real' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'fornecedor_id' => ['nullable', 'integer', Fornecedor::regraSelecionavel($fornecedorAtual)],
            'descricao' => ['required', 'string', 'max:2000'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function dadosExecucao(): array
    {
        return $this->safe()->only(['data_execucao', 'valor_real', 'fornecedor_id', 'descricao', 'observacoes']);
    }
}
