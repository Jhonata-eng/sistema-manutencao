<?php

use App\Models\Ativo;
use App\Models\TipoEquipamento;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->comPermissoes('cadastros.gerenciar')->create());
});

it('traz os tipos da especificação no seeder', function () {
    expect(TipoEquipamento::pluck('nome')->all())
        ->toContain('Ar-condicionado', 'Gerador', 'Bebedouro industrial', 'Nobreak');
});

it('cadastra, valida nome único e edita', function () {
    $this->post(route('cadastros.tipos.store'), ['nome' => 'Retificador', 'ativo' => '1'])->assertSessionHasNoErrors();
    $this->post(route('cadastros.tipos.store'), ['nome' => 'Retificador', 'ativo' => '1'])->assertSessionHasErrors('nome');

    $tipo = TipoEquipamento::where('nome', 'Retificador')->first();
    $this->put(route('cadastros.tipos.update', $tipo), ['nome' => 'Retificador CC', 'ativo' => '0'])->assertSessionHasNoErrors();

    expect($tipo->fresh())->nome->toBe('Retificador CC')->ativo->toBeFalse();
});

it('não exclui tipo em uso', function () {
    $tipo = Ativo::factory()->create()->tipo;

    $this->delete(route('cadastros.tipos.destroy', $tipo))->assertSessionHasErrors('protecao');

    expect(TipoEquipamento::find($tipo->id))->not->toBeNull();
});
