<?php

namespace App\Services;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PerfilService
{
    public function __construct(private ProtecaoAcessoAdministrativo $protecao) {}

    /** Cria (instância nova) ou atualiza um perfil com suas permissões. */
    public function salvar(Perfil $perfil, array $dados): Perfil
    {
        return $this->protecao->executar(function () use ($perfil, $dados) {
            $perfil->fill(Arr::only($dados, ['nome', 'descricao']))->save();
            $perfil->sincronizarAuditando('permissoes', $dados['permissoes'] ?? [], 'permissoes_alteradas', fn (Permissao $p) => $p->chave);

            return $perfil;
        });
    }

    public function excluir(Perfil $perfil): void
    {
        if ($perfil->usuarios()->exists()) {
            throw ValidationException::withMessages([
                'protecao' => 'Este perfil não pode ser excluído porque há usuários vinculados a ele.',
            ]);
        }

        $perfil->delete();
    }
}
