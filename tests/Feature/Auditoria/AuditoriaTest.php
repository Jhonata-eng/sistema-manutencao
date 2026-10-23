<?php

use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\User;

it('registra usuário, IP e apenas os campos alterados na edição', function () {
    $autor = User::factory()->administrador()->create();
    $cidade = Cidade::factory()->create(['nome' => 'Itu', 'uf' => 'SP']);
    $this->actingAs($autor);

    $this->app['request']->server->set('REMOTE_ADDR', '10.1.2.3');
    $cidade->update(['nome' => 'Itu Novo']);

    $registro = Auditoria::where('evento', 'editado')->where('auditavel_type', 'cidade')->first();
    expect($registro->user_id)->toBe($autor->id)
        ->and($registro->ip)->toBe('10.1.2.3')
        ->and($registro->antes)->toBe(['nome' => 'Itu'])
        ->and($registro->depois)->toBe(['nome' => 'Itu Novo']);
});

it('registra exclusão com os valores anteriores', function () {
    $cidade = Cidade::factory()->create(['nome' => 'Salto', 'uf' => 'SP']);
    $cidade->delete();

    $registro = Auditoria::where('evento', 'excluido')->where('auditavel_id', $cidade->id)->first();
    expect($registro->antes)->toMatchArray(['nome' => 'Salto', 'uf' => 'SP'])
        ->and($registro->depois)->toBeNull();
});

it('não registra edição quando nada auditável mudou', function () {
    $usuario = User::factory()->create();
    $antes = Auditoria::count();

    $usuario->forceFill(['remember_token' => 'outro-token'])->save();

    expect(Auditoria::count())->toBe($antes);
});

it('exige auditoria.visualizar para a tela', function () {
    $this->actingAs(User::factory()->comPermissoes('usuarios.gerenciar')->create())
        ->get(route('auditoria.index'))
        ->assertForbidden();
});

it('lista, filtra e mostra os detalhes', function () {
    $autor = User::factory()->comPermissoes('auditoria.visualizar')->create(['name' => 'Auditora']);
    $this->actingAs($autor);
    $cidade = Cidade::factory()->create(['nome' => 'Sorocaba', 'uf' => 'SP']);
    $cidade->update(['nome' => 'Sorocaba Centro']);
    $edicao = Auditoria::where('evento', 'editado')->where('auditavel_type', 'cidade')->first();

    $this->get(route('auditoria.index', ['evento' => 'editado', 'tipo' => 'cidade']))
        ->assertOk()
        ->assertSee('Cidade #'.$cidade->id)
        ->assertSee('<td>Edição</td>', false)
        ->assertDontSee('<td>Criação</td>', false);

    $this->get(route('auditoria.show', $edicao))
        ->assertOk()
        ->assertSee('Sorocaba Centro')
        ->assertSee('Auditora');
});
