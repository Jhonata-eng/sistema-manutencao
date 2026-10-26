<?php

use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\PlanoManutencao;

const PERMISSOES_PLANOS = ['ativos.visualizar', 'planos.visualizar', 'planos.criar', 'planos.editar', 'planos.excluir'];

beforeEach(function () {
    $this->campinas = Cidade::factory()->create();
    $this->sorocaba = Cidade::factory()->create();
    $this->planoSorocaba = PlanoManutencao::factory()->create([
        'ativo_id' => Ativo::factory()->create(['cidade_id' => $this->sorocaba->id])->id,
    ]);
});

it('bloqueia plano de outra cidade acessado pela URL', function (string $metodo, string $rota) {
    $this->actingAs(usuarioCom(PERMISSOES_PLANOS, $this->campinas))
        ->call($metodo, route($rota, $this->planoSorocaba), dadosPlano())
        ->assertForbidden();
})->with([
    'detalhes' => ['GET', 'planos.show'],
    'formulário de edição' => ['GET', 'planos.edit'],
    'gravar edição' => ['PUT', 'planos.update'],
    'excluir' => ['DELETE', 'planos.destroy'],
    'atualizar valor médio' => ['POST', 'planos.atualizar-valor-medio'],
]);

it('bloqueia criar plano em ativo de outra cidade', function () {
    $ativoSorocaba = $this->planoSorocaba->equipamento;
    $this->actingAs(usuarioCom(PERMISSOES_PLANOS, $this->campinas));

    $this->get(route('ativos.planos.create', $ativoSorocaba))->assertForbidden();
    $this->post(route('ativos.planos.store', $ativoSorocaba), dadosPlano())->assertForbidden();
});

it('quem só visualiza planos não vê botões nem acessa as ações', function () {
    $this->actingAs(usuarioCom(['ativos.visualizar', 'planos.visualizar'], $this->sorocaba));

    $this->get(route('planos.show', $this->planoSorocaba))->assertOk()->assertDontSee(route('planos.edit', $this->planoSorocaba));
    $this->get(route('ativos.show', $this->planoSorocaba->ativo_id))
        ->assertSee('Planos de manutenção')
        ->assertDontSee(route('ativos.planos.create', $this->planoSorocaba->ativo_id));
    $this->get(route('planos.edit', $this->planoSorocaba))->assertForbidden();
    $this->post(route('ativos.planos.store', $this->planoSorocaba->ativo_id), dadosPlano())->assertForbidden();
});

it('esconde os planos na tela do ativo de quem não tem planos.visualizar', function () {
    $this->actingAs(usuarioCom(['ativos.visualizar'], $this->sorocaba))
        ->get(route('ativos.show', $this->planoSorocaba->ativo_id))
        ->assertOk()
        ->assertDontSee('Planos de manutenção');
});
