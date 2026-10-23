<?php

use App\Models\Fornecedor;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->comPermissoes('cadastros.gerenciar')->create());
});

it('aceita CPF/CNPJ com máscara e grava só os caracteres', function (string $digitado, string $gravado) {
    $this->post(route('cadastros.fornecedores.store'), ['nome' => 'Refrigeração Alfa', 'documento' => $digitado, 'ativo' => '1'])
        ->assertSessionHasNoErrors();

    expect(Fornecedor::where('nome', 'Refrigeração Alfa')->value('documento'))->toBe($gravado);
})->with([
    'CNPJ numérico' => ['11.222.333/0001-81', '11222333000181'],
    'CNPJ alfanumérico' => ['12.abc.345/01de-35', '12ABC34501DE35'],
    'CPF' => ['529.982.247-25', '52998224725'],
]);

it('recusa CPF/CNPJ inválido e repetido', function () {
    Fornecedor::factory()->create(['documento' => '11222333000181']);

    $this->post(route('cadastros.fornecedores.store'), ['nome' => 'X', 'documento' => '11.222.333/0001-82', 'ativo' => '1'])
        ->assertSessionHasErrors('documento');
    $this->post(route('cadastros.fornecedores.store'), ['nome' => 'X', 'documento' => '11.222.333/0001-81', 'ativo' => '1'])
        ->assertSessionHasErrors('documento');
});

it('permite fornecedor sem documento', function () {
    $this->post(route('cadastros.fornecedores.store'), ['nome' => 'Prestador informal', 'documento' => '', 'ativo' => '1'])
        ->assertSessionHasNoErrors();

    expect(Fornecedor::where('nome', 'Prestador informal')->value('documento'))->toBeNull();
});

it('exibe o documento formatado na listagem', function () {
    Fornecedor::factory()->create(['documento' => '11222333000181']);

    $this->get(route('cadastros.fornecedores.index'))->assertSee('11.222.333/0001-81');
});

it('busca pelo CPF/CNPJ digitado com ou sem máscara', function () {
    Fornecedor::factory()->create(['nome' => 'Alfa', 'documento' => '11222333000181']);
    Fornecedor::factory()->create(['nome' => 'Beta', 'documento' => '52998224725']);

    $this->get(route('cadastros.fornecedores.index', ['busca' => '11.222.333/0001']))->assertSee('Alfa')->assertDontSee('Beta');
    $this->get(route('cadastros.fornecedores.index', ['busca' => '529982']))->assertSee('Beta')->assertDontSee('Alfa');
});

it('exclui fornecedor sem vínculos', function () {
    $fornecedor = Fornecedor::factory()->create();

    $this->delete(route('cadastros.fornecedores.destroy', $fornecedor))->assertSessionHasNoErrors();

    expect(Fornecedor::find($fornecedor->id))->toBeNull();
});
