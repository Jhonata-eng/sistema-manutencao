<?php

namespace App\Policies;

use App\Enums\StatusManutencao;
use App\Models\Ativo;
use App\Models\Manutencao;
use App\Models\User;

/**
 * Permissão do perfil + escopo por cidade do ativo + status que cada ação exige.
 * As telas usam os mesmos métodos (@can) para mostrar ou esconder os botões.
 */
class ManutencaoPolicy
{
    public function view(User $usuario, Manutencao $manutencao): bool
    {
        return $usuario->can('manutencoes.visualizar') && $this->naCidade($usuario, $manutencao);
    }

    public function registrarExecucao(User $usuario, Manutencao $manutencao): bool
    {
        return $usuario->can('manutencoes.registrar_execucao')
            && $this->naCidade($usuario, $manutencao)
            && $manutencao->status === StatusManutencao::Prevista;
    }

    public function cancelarReagendar(User $usuario, Manutencao $manutencao): bool
    {
        return $usuario->can('manutencoes.cancelar_reagendar')
            && $this->naCidade($usuario, $manutencao)
            && $manutencao->status === StatusManutencao::Prevista;
    }

    public function corrigirValor(User $usuario, Manutencao $manutencao): bool
    {
        return $usuario->can('manutencoes.corrigir_valor')
            && $this->naCidade($usuario, $manutencao)
            && $manutencao->status === StatusManutencao::Realizada;
    }

    /** Anexos (notas, recibos, laudos, fotos) são da execução: só em manutenção realizada. */
    public function enviarAnexo(User $usuario, Manutencao $manutencao): bool
    {
        return $usuario->can('anexos.enviar')
            && $this->naCidade($usuario, $manutencao)
            && $manutencao->status === StatusManutencao::Realizada;
    }

    public function lancarNaoProgramada(User $usuario, Ativo $ativo): bool
    {
        return $usuario->can('manutencoes.lancar_nao_programada')
            && $usuario->podeAcessarCidade($ativo->cidade_id)
            && ! $ativo->trashed();
    }

    private function naCidade(User $usuario, Manutencao $manutencao): bool
    {
        return $usuario->podeAcessarCidade($manutencao->ativo->cidade_id);
    }
}
