<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Impede que uma alteração em usuários ou perfis deixe o sistema sem ninguém
 * capaz de administrá-lo (nenhum usuário ativo com as permissões essenciais).
 */
class ProtecaoAcessoAdministrativo
{
    private const PERMISSOES_ESSENCIAIS = ['usuarios.gerenciar', 'perfis.gerenciar'];

    /**
     * Executa a alteração numa transação e a desfaz se alguma permissão essencial
     * que tinha usuário ativo antes ficar sem nenhum depois.
     */
    public function executar(Closure $alteracao): mixed
    {
        return DB::transaction(function () use ($alteracao) {
            $antes = $this->permissoesComUsuarioAtivo();
            $resultado = $alteracao();
            $perdidas = array_diff($antes, $this->permissoesComUsuarioAtivo());

            if ($perdidas !== []) {
                throw ValidationException::withMessages([
                    'protecao' => 'Alteração não permitida: o sistema ficaria sem nenhum usuário ativo com a permissão "'
                        .implode('", "', $perdidas).'".',
                ]);
            }

            return $resultado;
        });
    }

    private function permissoesComUsuarioAtivo(): array
    {
        return array_values(array_filter(
            self::PERMISSOES_ESSENCIAIS,
            fn (string $chave) => User::query()
                ->where('ativo', true)
                ->whereHas('perfil.permissoes', fn ($q) => $q->where('chave', $chave))
                ->exists(),
        ));
    }
}
