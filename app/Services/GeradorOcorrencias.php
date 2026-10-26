<?php

namespace App\Services;

use App\Enums\StatusAtivo;
use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Models\Auditoria;
use App\Models\Manutencao;
use App\Models\PlanoManutencao;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Cria, recalcula e cancela as ocorrências "previstas" dos planos.
 *
 * A janela de geração vai de hoje (ou do início do plano, se for depois) até hoje + horizonte
 * (config manutencao.horizonte_meses, fim exclusivo). Cada ocorrência guarda a data de calendário
 * que a originou (data_referencia); como (plano_id, data_referencia) é único, gerar de novo nunca
 * duplica nem recria uma ocorrência reagendada ou cancelada.
 *
 * Ocorrências criadas e apagadas aqui não geram um registro de auditoria cada: a auditoria fica
 * no plano/ativo que provocou a mudança.
 */
class GeradorOcorrencias
{
    /** Gera as ocorrências que faltam na janela. Retorna quantas foram criadas. */
    public function gerar(PlanoManutencao $plano): int
    {
        if (! $plano->ativo) {
            return 0;
        }

        $hoje = CarbonImmutable::today();
        $de = $plano->data_inicio->max($hoje);
        $ate = $hoje->addMonthsNoOverflow(config('manutencao.horizonte_meses'));

        $existentes = $plano->manutencoes()
            ->where('data_referencia', '>=', $de->toDateString())
            ->pluck('data_referencia')
            ->map(fn (CarbonImmutable $data) => $data->toDateString())
            ->all();

        $agora = now();
        $novas = [];
        foreach (Recorrencia::datas($plano->data_inicio, $plano->periodicidade_meses, $plano->data_termino, $de, $ate) as $data) {
            if (! in_array($data->toDateString(), $existentes, true)) {
                $novas[] = [
                    'ativo_id' => $plano->ativo_id,
                    'plano_id' => $plano->id,
                    'tipo' => TipoManutencao::Programada->value,
                    'status' => StatusManutencao::Prevista->value,
                    'data_referencia' => $data->toDateString(),
                    'data_prevista' => $data->toDateString(),
                    'valor_previsto' => $plano->valor_estimado,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
            }
        }

        // insertOrIgnore: se o comando diário e uma edição rodarem juntos, a chave única evita duplicar.
        return $novas === [] ? 0 : Manutencao::insertOrIgnore($novas);
    }

    /**
     * Depois de mudar periodicidade ou datas: apaga as pendentes futuras do calendário antigo e gera
     * o novo a partir de hoje. Atrasadas, realizadas, canceladas e pendentes reagendadas a partir de
     * uma data de referência passada não mudam.
     */
    public function recalcular(PlanoManutencao $plano): void
    {
        $hoje = today()->toDateString();

        $removidas = $plano->manutencoes()
            ->pendentesFuturas()
            ->where('data_referencia', '>=', $hoje)
            ->delete();
        $geradas = $this->gerar($plano);

        Auditoria::registrar('ocorrencias_recalculadas', $plano, ['pendentes_futuras' => $removidas], ['pendentes_futuras' => $geradas]);
    }

    /** Novo valor estimado vale para as pendentes futuras. */
    public function atualizarValorPrevisto(PlanoManutencao $plano): void
    {
        $plano->manutencoes()->pendentesFuturas()->update(['valor_previsto' => $plano->valor_estimado]);
    }

    /**
     * Cancelamento automático (plano inativado, ativo fora de operação ou excluído): cancela todas as
     * pendentes da consulta, inclusive atrasadas, e registra um único evento de auditoria no registro
     * que motivou o cancelamento. A data de calendário é liberada (data_referencia = null) para que o
     * plano, se reativado, volte a gerar essas datas; o cancelamento manual de uma ocorrência mantém
     * a data ocupada, para o comando diário não recriá-la.
     *
     * @param  Builder<Manutencao>  $consulta
     */
    public function cancelarPendentes(Builder $consulta, string $motivo, ?User $autor, Model $origem): int
    {
        $pendentes = (clone $consulta)->pendentes()->orderBy('data_prevista')->pluck('data_prevista', 'id');

        if ($pendentes->isEmpty()) {
            return 0;
        }

        Manutencao::whereKey($pendentes->keys())->update([
            'status' => StatusManutencao::Cancelada,
            'data_referencia' => null,
            'motivo_cancelamento' => $motivo,
            'cancelado_por' => $autor?->id,
            'cancelado_em' => now(),
        ]);

        Auditoria::registrar('ocorrencias_canceladas', $origem, [], [
            'quantidade' => $pendentes->count(),
            'datas_previstas' => $pendentes->map(fn (CarbonImmutable $data) => $data->format('d/m/Y'))->values()->all(),
        ], $motivo);

        return $pendentes->count();
    }

    /** Comando diário: completa a janela de todos os planos ativos de ativos em operação. */
    public function estenderHorizonte(): int
    {
        $geradas = 0;

        PlanoManutencao::query()
            ->where('ativo', true)
            ->whereHas('equipamento', fn (Builder $q) => $q->withoutTrashed()->where('status', StatusAtivo::EmOperacao))
            ->chunkById(200, function ($planos) use (&$geradas) {
                foreach ($planos as $plano) {
                    $geradas += $this->gerar($plano);
                }
            });

        return $geradas;
    }
}
