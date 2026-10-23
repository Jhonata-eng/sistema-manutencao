<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('permissoes')]
#[Fillable(['chave', 'descricao', 'grupo'])]
class Permissao extends Model
{
    /**
     * Catálogo fixo de permissões: chave => [grupo, descrição].
     *
     * É a fonte única: o PermissoesSeeder sincroniza a tabela a partir dele e o
     * AppServiceProvider registra um Gate por chave (usado em @can, can: e authorize()).
     */
    public const CATALOGO = [
        'dashboard.visualizar' => ['Dashboard', 'Visualizar o dashboard'],

        'ativos.visualizar' => ['Ativos', 'Visualizar ativos'],
        'ativos.criar' => ['Ativos', 'Cadastrar ativos'],
        'ativos.editar' => ['Ativos', 'Editar ativos'],
        'ativos.excluir' => ['Ativos', 'Excluir ativos'],

        'planos.visualizar' => ['Planos de manutenção', 'Visualizar planos'],
        'planos.criar' => ['Planos de manutenção', 'Cadastrar planos'],
        'planos.editar' => ['Planos de manutenção', 'Editar planos'],
        'planos.excluir' => ['Planos de manutenção', 'Excluir planos'],

        'manutencoes.visualizar' => ['Manutenções', 'Visualizar manutenções'],
        'manutencoes.registrar_execucao' => ['Manutenções', 'Registrar execução'],
        'manutencoes.lancar_nao_programada' => ['Manutenções', 'Lançar manutenção não programada'],
        'manutencoes.cancelar_reagendar' => ['Manutenções', 'Cancelar ou reagendar ocorrências'],
        'manutencoes.corrigir_valor' => ['Manutenções', 'Corrigir valor após o registro'],

        'anexos.enviar' => ['Anexos', 'Enviar anexos'],
        'anexos.excluir' => ['Anexos', 'Excluir anexos'],

        'cadastros.gerenciar' => ['Cadastros', 'Gerenciar cidades, responsáveis, tipos de equipamento e fornecedores'],

        'relatorios.exportar' => ['Relatórios', 'Exportar relatórios'],

        'usuarios.gerenciar' => ['Administração', 'Gerenciar usuários'],
        'perfis.gerenciar' => ['Administração', 'Gerenciar perfis e permissões'],
        'auditoria.visualizar' => ['Administração', 'Consultar a auditoria'],

        'dados.ver_todas_cidades' => ['Escopo de dados', 'Ver e operar dados de todas as cidades'],
    ];

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_permissao');
    }
}
