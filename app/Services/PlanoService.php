<?php

namespace App\Services;

use App\Enums\StatusManutencao;
use App\Models\PlanoManutencao;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanoService
{
    public function __construct(private GeradorOcorrencias $ocorrencias) {}

    /**
     * Cria (instância nova, com ativo_id definido) ou atualiza um plano e ajusta as ocorrências:
     * plano novo ou reativado gera a janela; inativado cancela as pendentes; valor novo vale para as
     * pendentes futuras; periodicidade ou datas novas recalculam as pendentes futuras.
     */
    public function salvar(PlanoManutencao $plano, array $dados, User $autor): PlanoManutencao
    {
        return DB::transaction(function () use ($plano, $dados, $autor) {
            $plano->fill($dados);
            $novo = ! $plano->exists;
            $mudouCalendario = $plano->isDirty(['periodicidade_meses', 'data_inicio', 'data_termino']);
            $mudouValor = $plano->isDirty('valor_estimado');
            $mudouSituacao = $plano->isDirty('ativo');
            $plano->save();

            if ($novo) {
                $this->ocorrencias->gerar($plano);
            } elseif (! $plano->ativo) {
                if ($mudouSituacao) {
                    $this->ocorrencias->cancelarPendentes($plano->manutencoes()->getQuery(), 'Plano inativado.', $autor, $plano);
                }
            } else {
                if ($mudouValor) {
                    $this->ocorrencias->atualizarValorPrevisto($plano);
                }
                if ($mudouCalendario) {
                    $this->ocorrencias->recalcular($plano);
                } elseif ($mudouSituacao) {
                    $this->ocorrencias->gerar($plano);
                }
            }

            return $plano;
        });
    }

    /** Só exclui plano sem histórico (nenhuma ocorrência realizada ou cancelada); as pendentes vão junto. */
    public function excluir(PlanoManutencao $plano): void
    {
        $temHistorico = $plano->manutencoes()
            ->whereIn('status', [StatusManutencao::Realizada, StatusManutencao::Cancelada])
            ->exists();

        if ($temHistorico) {
            throw ValidationException::withMessages([
                'protecao' => 'Este plano já tem manutenções realizadas ou canceladas e não pode ser excluído. Inative-o para encerrar as próximas.',
            ]);
        }

        DB::transaction(function () use ($plano) {
            $plano->manutencoes()->delete();
            $plano->delete();
        });
    }

    /** Valor estimado passa a ser a média real das execuções; vale para as pendentes futuras. */
    public function atualizarValorMedio(PlanoManutencao $plano, User $autor): string
    {
        $media = $plano->mediaReal();

        if ($media === null) {
            throw ValidationException::withMessages([
                'protecao' => 'O plano ainda não tem manutenções realizadas para calcular a média.',
            ]);
        }

        $this->salvar($plano, ['valor_estimado' => $media], $autor);

        return $media;
    }
}
