<?php

namespace App\Policies;

use App\Models\Ativo;
use App\Models\PlanoManutencao;
use App\Models\User;

/** Permissão do perfil + escopo por cidade do ativo do plano. */
class PlanoManutencaoPolicy
{
    public function view(User $usuario, PlanoManutencao $plano): bool
    {
        return $usuario->can('planos.visualizar') && $usuario->podeAcessarCidade($plano->equipamento->cidade_id);
    }

    public function create(User $usuario, Ativo $ativo): bool
    {
        return $usuario->can('planos.criar') && $usuario->podeAcessarCidade($ativo->cidade_id);
    }

    public function update(User $usuario, PlanoManutencao $plano): bool
    {
        return $usuario->can('planos.editar') && $usuario->podeAcessarCidade($plano->equipamento->cidade_id);
    }

    public function delete(User $usuario, PlanoManutencao $plano): bool
    {
        return $usuario->can('planos.excluir') && $usuario->podeAcessarCidade($plano->equipamento->cidade_id);
    }
}
