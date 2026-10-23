<?php

use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\User;

const PERMISSOES_ATIVOS = ['ativos.visualizar', 'ativos.criar', 'ativos.editar', 'ativos.excluir'];

beforeEach(function () {
    $this->campinas = Cidade::factory()->create(['nome' => 'Campinas', 'uf' => 'SP']);
    $this->sorocaba = Cidade::factory()->create(['nome' => 'Sorocaba', 'uf' => 'SP']);
    $this->ativoCampinas = Ativo::factory()->create(['cidade_id' => $this->campinas->id, 'patrimonio' => 'CPS-001']);
    $this->ativoSorocaba = Ativo::factory()->create(['cidade_id' => $this->sorocaba->id, 'patrimonio' => 'SOR-001']);
});

it('lista só os ativos das cidades vinculadas ao usuário', function () {
    $this->actingAs(usuarioCom(PERMISSOES_ATIVOS, $this->campinas))
        ->get(route('ativos.index'))
        ->assertSee('CPS-001')
        ->assertDontSee('SOR-001')
        ->assertDontSee('Sorocaba/SP');
});

it('mostra tudo para quem tem dados.ver_todas_cidades', function () {
    $this->actingAs(usuarioCom([...PERMISSOES_ATIVOS, 'dados.ver_todas_cidades']))
        ->get(route('ativos.index'))
        ->assertSee('CPS-001')
        ->assertSee('SOR-001');
});

it('bloqueia ativo de outra cidade acessado pela URL', function (string $metodo, string $rota) {
    $this->actingAs(usuarioCom(PERMISSOES_ATIVOS, $this->campinas))
        ->call($metodo, route($rota, $this->ativoSorocaba))
        ->assertForbidden();

    expect(Ativo::find($this->ativoSorocaba->id))->not->toBeNull();
})->with([
    'detalhes' => ['GET', 'ativos.show'],
    'formulário de edição' => ['GET', 'ativos.edit'],
    'gravar edição' => ['PUT', 'ativos.update'],
    'excluir' => ['DELETE', 'ativos.destroy'],
    'foto' => ['GET', 'ativos.foto'],
]);

it('não deixa mover nem cadastrar ativo em cidade não vinculada', function () {
    $this->actingAs(usuarioCom(PERMISSOES_ATIVOS, $this->campinas));
    $dados = [
        'patrimonio' => 'NOVO-1', 'tipo_equipamento_id' => $this->ativoCampinas->tipo_equipamento_id, 'status' => 'em_operacao',
        'marca' => 'X', 'modelo' => 'Y', 'cidade_id' => $this->sorocaba->id, 'localizacao' => 'POP',
    ];

    $this->post(route('ativos.store'), $dados)->assertSessionHasErrors('cidade_id');
    $this->put(route('ativos.update', $this->ativoCampinas), [...$dados, 'patrimonio' => 'CPS-001'])->assertSessionHasErrors('cidade_id');

    expect($this->ativoCampinas->fresh()->cidade_id)->toBe($this->campinas->id);
});

it('oferece no formulário só as cidades vinculadas', function () {
    $this->actingAs(usuarioCom(PERMISSOES_ATIVOS, $this->campinas))
        ->get(route('ativos.create'))
        ->assertSee('Campinas/SP')
        ->assertDontSee('Sorocaba/SP');
});

it('usuário sem nenhuma cidade não vê ativos', function () {
    $this->actingAs(User::factory()->comPermissoes(...PERMISSOES_ATIVOS)->create())
        ->get(route('ativos.index'))
        ->assertDontSee('CPS-001')
        ->assertDontSee('SOR-001');
});
