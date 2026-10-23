<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Database\Seeder;

/**
 * Perfis padrão. As permissões só são atribuídas quando o perfil é criado,
 * para não desfazer ajustes feitos depois pela tela de perfis.
 */
class PerfisSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::perfisPadrao() as $nome => [$descricao, $chaves]) {
            $perfil = Perfil::firstOrCreate(['nome' => $nome], ['descricao' => $descricao]);

            if ($perfil->wasRecentlyCreated) {
                $perfil->permissoes()->sync(Permissao::whereIn('chave', $chaves)->pluck('id'));
            }
        }
    }

    /** @return array<string, array{string, list<string>}> */
    public static function perfisPadrao(): array
    {
        $todas = array_keys(Permissao::CATALOGO);
        $visualizacao = ['dashboard.visualizar', 'ativos.visualizar', 'planos.visualizar', 'manutencoes.visualizar'];

        return [
            'Administrador' => ['Acesso total ao sistema.', $todas],
            'Gestor' => [
                'Opera e acompanha tudo nas suas cidades, sem administrar usuários e perfis.',
                array_values(array_diff($todas, ['usuarios.gerenciar', 'perfis.gerenciar', 'dados.ver_todas_cidades'])),
            ],
            'Técnico' => [
                'Registra execuções e manutenções não programadas nas suas cidades.',
                [...$visualizacao, 'manutencoes.registrar_execucao', 'manutencoes.lancar_nao_programada', 'anexos.enviar'],
            ],
            'Visualizador' => ['Somente consulta, nas suas cidades.', $visualizacao],
        ];
    }
}
