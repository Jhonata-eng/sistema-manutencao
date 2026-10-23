<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsuarioService
{
    public function __construct(private ProtecaoAcessoAdministrativo $protecao) {}

    /** Cria (instância nova) ou atualiza um usuário, com as cidades vinculadas. */
    public function salvar(User $usuario, array $dados): User
    {
        return $this->protecao->executar(function () use ($usuario, $dados) {
            $usuario->fill(Arr::only($dados, ['name', 'email', 'password', 'perfil_id', 'ativo']))->save();
            $usuario->sincronizarAuditando('cidades', $dados['cidades'] ?? [], 'cidades_alteradas', fn (Cidade $c) => $c->nome_uf);

            return $usuario;
        });
    }

    /** Troca feita pelo próprio usuário; a sessão atual continua válida. */
    public function alterarSenha(User $usuario, string $senha): void
    {
        $this->gravarSenha($usuario, $senha, 'senha_alterada');
    }

    /** Redefinição feita por um administrador: derruba as sessões e o "lembrar-me" do usuário. */
    public function redefinirSenha(User $usuario, string $senha): void
    {
        $this->gravarSenha($usuario, $senha, 'senha_redefinida');
        DB::table('sessions')->where('user_id', $usuario->id)->delete();
    }

    private function gravarSenha(User $usuario, string $senha, string $evento): void
    {
        $usuario->password = $senha;
        $usuario->setRememberToken(Str::random(60));
        $usuario->save();

        Auditoria::registrar($evento, $usuario);
    }
}
