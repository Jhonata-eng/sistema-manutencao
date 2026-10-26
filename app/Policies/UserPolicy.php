<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Editar, inativar ou redefinir a senha: usuarios.gerenciar + não ter menos acesso que o alvo. */
    public function update(User $usuario, User $alvo): bool
    {
        return $usuario->can('usuarios.gerenciar') && $usuario->podeAdministrar($alvo);
    }
}
