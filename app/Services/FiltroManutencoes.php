<?php

namespace App\Services;

use App\Enums\StatusManutencao;
use App\Models\Ativo;
use App\Models\Manutencao;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * A consulta única de manutenções: escopo de cidades do usuário + filtros. Usada pela visão
 * unificada, pelo calendário, pela exportação, pelos totais do rodapé e pelo painel, para que
 * o clique num gráfico abra exatamente o que a barra soma.
 */
class FiltroManutencoes
{
    /** Posição da manutenção no tempo: a data de execução, se realizada; senão a data prevista. */
    public const DATA_EFETIVA = 'COALESCE(manutencoes.data_execucao, manutencoes.data_prevista)';

    /** Qual data o período (de/até) usa: a efetiva (padrão), só a prevista ou só a de execução. */
    public const BASES_DE_DATA = [
        'efetiva' => 'Data efetiva',
        'prevista' => 'Data prevista',
        'execucao' => 'Data de execução',
    ];

    /** Chaves aceitas; "status" aceita também "atrasada" (prevista com data passada). */
    public const CHAVES = [
        'de', 'ate', 'data', 'status', 'cidade', 'tipo_equipamento', 'responsavel', 'ativo', 'ativo_id',
        'fornecedor', 'tipo', 'sem_comprovante',
    ];

    /** @param  array<string, mixed>  $filtros */
    public function consulta(User $usuario, array $filtros): Builder
    {
        $filtrosDoAtivo = array_filter([
            'cidade' => $filtros['cidade'] ?? null,
            'tipo' => $filtros['tipo_equipamento'] ?? null,
            'responsavel' => $filtros['responsavel'] ?? null,
        ]);

        $data = match ($filtros['data'] ?? 'efetiva') {
            'prevista' => 'manutencoes.data_prevista',
            'execucao' => 'manutencoes.data_execucao',
            default => self::DATA_EFETIVA,
        };

        return Manutencao::query()
            ->visivelPara($usuario)
            ->when($filtros['de'] ?? null, fn (Builder $q, string $de) => $q->whereRaw("{$data} >= ?", [$de]))
            ->when($filtros['ate'] ?? null, fn (Builder $q, string $ate) => $q->whereRaw("{$data} <= ?", [$ate]))
            ->when($filtros['status'] ?? null, fn (Builder $q, string $status) => $status === 'atrasada'
                ? $q->atrasadas()
                : $q->where('manutencoes.status', $status))
            ->when($filtrosDoAtivo, fn (Builder $q) => $q->whereIn(
                'manutencoes.ativo_id',
                Ativo::withTrashed()->filtrar($filtrosDoAtivo)->select('id'),
            ))
            ->when($filtros['ativo'] ?? null, fn (Builder $q, string $patrimonio) => $q->whereIn(
                'manutencoes.ativo_id',
                Ativo::withTrashed()->whereLike('patrimonio', "%{$patrimonio}%")->select('id'),
            ))
            ->when($filtros['ativo_id'] ?? null, fn (Builder $q, $id) => $q->where('manutencoes.ativo_id', $id))
            ->when($filtros['fornecedor'] ?? null, fn (Builder $q, $fornecedor) => $q->where('manutencoes.fornecedor_id', $fornecedor))
            ->when($filtros['tipo'] ?? null, fn (Builder $q, string $tipo) => $q->where('manutencoes.tipo', $tipo))
            ->when($filtros['sem_comprovante'] ?? false, fn (Builder $q) => $q->semComprovanteAnexado());
    }

    /**
     * Totais do filtro, somados no SQL (strings decimais): previsto sem as canceladas (regra 5),
     * realizado das realizadas e diferença = realizado − previsto (positivo = gastou acima do previsto).
     *
     * @return array{previsto: string, realizado: string, diferenca: string}
     */
    public function totais(Builder $consulta): array
    {
        $cancelada = StatusManutencao::Cancelada->value;
        $realizada = StatusManutencao::Realizada->value;
        $previsto = "COALESCE(SUM(CASE WHEN manutencoes.status <> '{$cancelada}' THEN manutencoes.valor_previsto END), 0)";
        $realizado = "COALESCE(SUM(CASE WHEN manutencoes.status = '{$realizada}' THEN manutencoes.valor_real END), 0)";

        $linha = (clone $consulta)->reorder()->toBase()
            ->selectRaw("{$previsto} as previsto, {$realizado} as realizado, {$realizado} - {$previsto} as diferenca")
            ->first();

        return ['previsto' => (string) $linha->previsto, 'realizado' => (string) $linha->realizado, 'diferenca' => (string) $linha->diferenca];
    }
}
