<?php

namespace App\Policies;

use App\Models\Ativo;
use App\Models\User;

/**
 * Permissão do perfil + escopo por cidade: ativo de cidade não vinculada ao usuário
 * responde 403 mesmo que o ID seja digitado na URL.
 */
class AtivoPolicy
{
    public function viewAny(User $usuario): bool
    {
        return $usuario->can('ativos.visualizar');
    }

    public function view(User $usuario, Ativo $ativo): bool
    {
        return $usuario->can('ativos.visualizar') && $usuario->podeAcessarCidade($ativo->cidade_id);
    }

    public function create(User $usuario): bool
    {
        return $usuario->can('ativos.criar');
    }

    public function update(User $usuario, Ativo $ativo): bool
    {
        return $usuario->can('ativos.editar') && $usuario->podeAcessarCidade($ativo->cidade_id);
    }

    public function delete(User $usuario, Ativo $ativo): bool
    {
        return $usuario->can('ativos.excluir') && $usuario->podeAcessarCidade($ativo->cidade_id);
    }
}
