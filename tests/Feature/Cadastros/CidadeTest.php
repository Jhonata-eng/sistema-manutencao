<?php

use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->comPermissoes('cadastros.gerenciar')->create());
});

it('cadastra cidade e audita', function () {
    $this->post(route('cadastros.cidades.store'), ['nome' => 'Campinas', 'uf' => 'sp', 'ativo' => '1'])
        ->assertRedirect(route('cadastros.cidades.index'));

    $cidade = Cidade::where('nome', 'Campinas')->first();
    expect($cidade->uf)->toBe('SP')
        ->and(Auditoria::where('evento', 'criado')->where('auditavel_type', 'cidade')->where('auditavel_id', $cidade->id)->exists())->toBeTrue();
});

it('não repete nome na mesma UF, mas aceita em UF diferente', function () {
    Cidade::factory()->create(['nome' => 'Bom Jesus', 'uf' => 'PI']);

    $this->post(route('cadastros.cidades.store'), ['nome' => 'Bom Jesus', 'uf' => 'PI', 'ativo' => '1'])
        ->assertSessionHasErrors('nome');
    $this->post(route('cadastros.cidades.store'), ['nome' => 'Bom Jesus', 'uf' => 'RS', 'ativo' => '1'])
        ->assertSessionHasNoErrors();
});

it('recusa UF inexistente', function () {
    $this->post(route('cadastros.cidades.store'), ['nome' => 'Xyz', 'uf' => 'XX', 'ativo' => '1'])
        ->assertSessionHasErrors('uf');
});

it('inativa a cidade pela edição', function () {
    $cidade = Cidade::factory()->create();

    $this->put(route('cadastros.cidades.update', $cidade), ['nome' => $cidade->nome, 'uf' => $cidade->uf, 'ativo' => '0'])
        ->assertRedirect(route('cadastros.cidades.index'));

    expect($cidade->fresh()->ativo)->toBeFalse();
});

it('exclui cidade sem vínculos', function () {
    $cidade = Cidade::factory()->create();

    $this->delete(route('cadastros.cidades.destroy', $cidade))->assertSessionHasNoErrors();

    expect(Cidade::find($cidade->id))->toBeNull();
});

it('não exclui cidade com ativo, mesmo excluído logicamente, nem com usuário vinculado', function () {
    $comAtivo = Ativo::factory()->create()->cidade;
    Ativo::where('cidade_id', $comAtivo->id)->first()->delete();
    $comUsuario = Cidade::factory()->create();
    User::factory()->create()->cidades()->attach($comUsuario);

    $this->delete(route('cadastros.cidades.destroy', $comAtivo))->assertSessionHasErrors('protecao');
    $this->delete(route('cadastros.cidades.destroy', $comUsuario))->assertSessionHasErrors('protecao');

    expect(Cidade::whereKey([$comAtivo->id, $comUsuario->id])->count())->toBe(2);
});
