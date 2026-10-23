<?php

use App\Models\User;

$rotas = ['cadastros.cidades.index', 'cadastros.tipos.index', 'cadastros.responsaveis.index', 'cadastros.fornecedores.index'];

it('exige cadastros.gerenciar', function (string $rota) {
    $this->actingAs(User::factory()->comPermissoes('ativos.visualizar')->create())
        ->get(route($rota))
        ->assertForbidden();
})->with($rotas);

it('abre as listagens para quem gerencia cadastros', function (string $rota) {
    $this->actingAs(User::factory()->comPermissoes('cadastros.gerenciar')->create())
        ->get(route($rota))
        ->assertOk();
})->with($rotas);

it('esconde o menu de cadastros de quem não pode usá-lo', function () {
    $this->actingAs(User::factory()->comPermissoes('ativos.visualizar')->create())
        ->get(route('inicio'))
        ->assertSee(route('ativos.index'))
        ->assertDontSee(route('cadastros.cidades.index'));
});
