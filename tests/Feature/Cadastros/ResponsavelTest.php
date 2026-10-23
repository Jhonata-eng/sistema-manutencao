<?php

use App\Models\Ativo;
use App\Models\Responsavel;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->comPermissoes('cadastros.gerenciar')->create());
});

it('cadastra responsável vinculado a um usuário do sistema', function () {
    $usuario = User::factory()->create();

    $this->post(route('cadastros.responsaveis.store'), [
        'nome' => 'Carlos Lima',
        'email' => 'carlos@exemplo.com.br',
        'telefone' => '(19) 99999-0000',
        'setor' => 'Infraestrutura',
        'user_id' => $usuario->id,
        'ativo' => '1',
    ])->assertRedirect(route('cadastros.responsaveis.index'));

    expect(Responsavel::where('nome', 'Carlos Lima')->first()->usuario->is($usuario))->toBeTrue();
});

it('não vincula o mesmo usuário a dois responsáveis', function () {
    $usuario = User::factory()->create();
    Responsavel::factory()->create(['user_id' => $usuario->id]);

    $this->post(route('cadastros.responsaveis.store'), ['nome' => 'Outro', 'user_id' => $usuario->id, 'ativo' => '1'])
        ->assertSessionHasErrors('user_id');
});

it('não exclui responsável em uso', function () {
    $responsavel = Responsavel::factory()->create();
    Ativo::factory()->create(['responsavel_id' => $responsavel->id]);

    $this->delete(route('cadastros.responsaveis.destroy', $responsavel))->assertSessionHasErrors('protecao');
});
