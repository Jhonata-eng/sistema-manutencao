<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Comportamento comum dos cadastros auxiliares (cidades, responsáveis, tipos, fornecedores):
 * filtro da listagem e regra de "ativo": inativos não aparecem para novos vínculos,
 * mas continuam válidos nos registros que já os usam.
 */
trait CadastroAuxiliar
{
    /** Colunas pesquisadas pelo filtro "busca" da listagem. */
    abstract protected function colunasBusca(): array;

    /** Permite adaptar o termo a uma coluna (ex.: tirar a máscara do CPF/CNPJ). */
    protected function termoBusca(string $coluna, string $busca): string
    {
        return $busca;
    }

    #[Scope]
    protected function filtrar(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['busca'] ?? null, fn (Builder $q, string $busca) => $q->where(function (Builder $q) use ($busca) {
                foreach ($this->colunasBusca() as $coluna) {
                    $q->orWhereLike($coluna, '%'.$this->termoBusca($coluna, $busca).'%');
                }
            }))
            ->when($filtros['status'] ?? null, fn (Builder $q, string $status) => $q->where('ativo', $status === 'ativo'));
    }

    /** Opções de um select: os ativos e, se houver, o já vinculado ao registro em edição. */
    #[Scope]
    protected function selecionaveis(Builder $query, ?int $atual = null): void
    {
        $query->where(fn (Builder $q) => $q->where('ativo', true)->orWhere('id', $atual));
    }

    /** Regra de validação equivalente a selecionaveis(). */
    public static function regraSelecionavel(?int $atual = null): Exists
    {
        return Rule::exists((new static)->getTable(), 'id')
            ->where(fn ($q) => $q->where('ativo', true)->orWhere('id', $atual));
    }
}
