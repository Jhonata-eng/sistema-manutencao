<?php

namespace App\Services;

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\CorrecaoValor;
use App\Models\Manutencao;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Ações sobre uma manutenção. As permissões e o status exigido por cada ação ficam na
 * ManutencaoPolicy; aqui ficam a gravação e a auditoria.
 */
class ManutencaoService
{
    /** Campos informados no registro de execução e no lançamento de não programada. */
    private const CAMPOS_EXECUCAO = ['data_execucao', 'valor_real', 'fornecedor_id', 'descricao', 'observacoes'];

    public function __construct(private AnexoService $anexos) {}

    /** Prevista → realizada. O anexo é opcional (a nota pode chegar depois). */
    public function registrarExecucao(Manutencao $manutencao, array $dados, ?array $anexo, User $autor): Manutencao
    {
        return DB::transaction(function () use ($manutencao, $dados, $anexo, $autor) {
            $manutencao->fill(Arr::only($dados, self::CAMPOS_EXECUCAO));
            $manutencao->status = StatusManutencao::Realizada;
            $manutencao->registrado_por = $autor->id;
            $manutencao->save();

            Auditoria::registrar('executada', $manutencao, ['status' => StatusManutencao::Prevista->value], $this->resumoExecucao($manutencao));
            $this->anexarSeEnviado($manutencao, $anexo, $autor);

            return $manutencao;
        });
    }

    /** Manutenção corretiva: nasce realizada e entra só no realizado (sem valor previsto). */
    public function lancarNaoProgramada(Ativo $ativo, array $dados, ?array $anexo, User $autor): Manutencao
    {
        return DB::transaction(function () use ($ativo, $dados, $anexo, $autor) {
            $manutencao = new Manutencao(Arr::only($dados, self::CAMPOS_EXECUCAO));
            $manutencao->tipo = TipoManutencao::NaoProgramada;
            $manutencao->status = StatusManutencao::Realizada;
            $manutencao->ativo_id = $ativo->id;
            $manutencao->registrado_por = $autor->id;
            $manutencao->save();

            Auditoria::registrar('nao_programada_lancada', $manutencao, [], $this->resumoExecucao($manutencao));
            $this->anexarSeEnviado($manutencao, $anexo, $autor);

            return $manutencao;
        });
    }

    /** Cancelamento manual: mantém data_referencia, então o comando diário não recria a ocorrência. */
    public function cancelar(Manutencao $manutencao, string $motivo, User $autor): void
    {
        $manutencao->forceFill([
            'status' => StatusManutencao::Cancelada,
            'motivo_cancelamento' => $motivo,
            'cancelado_por' => $autor->id,
            'cancelado_em' => now(),
        ])->save();

        Auditoria::registrar('cancelada', $manutencao, ['status' => StatusManutencao::Prevista->value], ['status' => StatusManutencao::Cancelada->value], $motivo);
    }

    /** Muda só a data prevista; a data de referência do calendário continua a mesma. */
    public function reagendar(Manutencao $manutencao, string $novaData, string $motivo): void
    {
        $anterior = $manutencao->data_prevista->toDateString();
        $manutencao->data_prevista = $novaData;
        $manutencao->save();

        Auditoria::registrar('reagendada', $manutencao, ['data_prevista' => $anterior], ['data_prevista' => $novaData], $motivo);
    }

    /**
     * Corrige o valor real de uma realizada, com motivo e comprovante (um anexo já existente
     * da manutenção ou um arquivo novo), gravando o histórico.
     */
    public function corrigirValor(Manutencao $manutencao, string $valorNovo, string $motivo, ?int $comprovanteId, ?array $novoComprovante, User $autor): CorrecaoValor
    {
        return DB::transaction(function () use ($manutencao, $valorNovo, $motivo, $comprovanteId, $novoComprovante, $autor) {
            $comprovante = $novoComprovante
                ? $this->anexos->armazenar($manutencao, $novoComprovante, $autor)
                : $manutencao->anexos()->findOrFail($comprovanteId);

            $correcao = $manutencao->correcoesValor()->create([
                'valor_anterior' => $manutencao->valor_real,
                'valor_novo' => $valorNovo,
                'motivo' => $motivo,
                'anexo_id' => $comprovante->id,
                'user_id' => $autor->id,
            ]);

            $manutencao->valor_real = $valorNovo;
            $manutencao->save();

            Auditoria::registrar('valor_corrigido', $manutencao,
                ['valor_real' => $correcao->valor_anterior],
                ['valor_real' => $correcao->valor_novo, 'comprovante' => $comprovante->descricao()],
                $motivo,
            );

            return $correcao;
        });
    }

    private function anexarSeEnviado(Manutencao $manutencao, ?array $anexo, User $autor): void
    {
        if ($anexo) {
            $this->anexos->armazenar($manutencao, $anexo, $autor);
        }
    }

    private function resumoExecucao(Manutencao $manutencao): array
    {
        return [
            'status' => $manutencao->status->value,
            'data_execucao' => $manutencao->data_execucao->toDateString(),
            'valor_real' => $manutencao->valor_real,
            'fornecedor' => $manutencao->fornecedor()->value('nome'),
            'descricao' => $manutencao->descricao,
        ];
    }
}
