<?php

namespace App\Policies;

use App\Models\Anexo;
use App\Models\User;

class AnexoPolicy
{
    /** Download: quem pode ver a manutenção (permissão + cidade). */
    public function view(User $usuario, Anexo $anexo): bool
    {
        return $usuario->can('view', $anexo->manutencao);
    }

    public function delete(User $usuario, Anexo $anexo): bool
    {
        return $usuario->can('anexos.excluir') && $usuario->can('view', $anexo->manutencao);
    }
}
