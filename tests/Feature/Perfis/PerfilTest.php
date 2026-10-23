<?php

use App\Models\Auditoria;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

function idsPermissoes(string ...$chaves): array
{
    return Permissao::whereIn('chave', $chaves)->pluck('id')->all();
}

it('cria os perfis padrão com as permissões esperadas', function () {
    $permissoes = fn (string $nome) => Perfil::where('nome', $nome)->first()->permissoes->pluck('chave');

    expect($permissoes('Administrador'))->toHaveCount(count(Permissao::CATALOGO))
        ->and($permissoes('Gestor'))->not->toContain('usuarios.gerenciar', 'perfis.gerenciar', 'dados.ver_todas_cidades')
        ->and($permissoes('Gestor'))->toContain('manutencoes.corrigir_valor', 'auditoria.visualizar')
        ->and($permissoes('Técnico')->sort()->values()->all())->toBe(collect([
            'anexos.enviar', 'ativos.visualizar', 'dashboard.visualizar', 'manutencoes.lancar_nao_programada',
            'manutencoes.registrar_execucao', 'manutencoes.visualizar', 'planos.visualizar',
        ])->sort()->values()->all())
        ->and($permissoes('Visualizador'))->toHaveCount(4);
});

it('bloqueia quem não tem perfis.gerenciar', function () {
    $this->actingAs(User::factory()->comPermissoes('usuarios.gerenciar')->create());

    $this->get(route('perfis.index'))->assertForbidden();
    $this->post(route('perfis.store'), ['nome' => 'X'])->assertForbidden();
});

it('cria perfil com permissões e audita a lista', function () {
    $this->actingAs($this->admin)
        ->post(route('perfis.store'), [
            'nome' => 'Almoxarifado',
            'descricao' => 'Consulta ativos',
            'permissoes' => idsPermissoes('ativos.visualizar', 'dashboard.visualizar'),
        ])
        ->assertRedirect(route('perfis.index'));

    $perfil = Perfil::where('nome', 'Almoxarifado')->first();
    expect($perfil->permissoes->pluck('chave')->sort()->values()->all())->toBe(['ativos.visualizar', 'dashboard.visualizar']);

    $registro = Auditoria::where('evento', 'permissoes_alteradas')->where('auditavel_id', $perfil->id)->first();
    expect($registro->antes['permissoes'])->toBe([])
        ->and($registro->depois['permissoes'])->toBe(['ativos.visualizar', 'dashboard.visualizar']);
});

it('altera permissões de um perfil e o efeito vale para os usuários dele', function () {
    $perfil = Perfil::factory()->comPermissoes('ativos.visualizar')->create();
    $usuario = User::factory()->create(['perfil_id' => $perfil->id]);

    $this->actingAs($this->admin)
        ->put(route('perfis.update', $perfil), [
            'nome' => $perfil->nome,
            'permissoes' => idsPermissoes('ativos.visualizar', 'ativos.criar'),
        ])
        ->assertRedirect(route('perfis.index'));

    expect($usuario->fresh()->can('ativos.criar'))->toBeTrue()
        ->and($usuario->fresh()->can('ativos.excluir'))->toBeFalse();
});

it('não deixa remover perfis.gerenciar do único perfil que a tem', function () {
    $perfilAdmin = $this->admin->perfil;

    $this->actingAs($this->admin)
        ->put(route('perfis.update', $perfilAdmin), [
            'nome' => $perfilAdmin->nome,
            'permissoes' => idsPermissoes('usuarios.gerenciar'),
        ])
        ->assertSessionHasErrors('protecao');

    expect($perfilAdmin->permissoes()->count())->toBe(count(Permissao::CATALOGO));
});

it('exclui perfil sem usuários e recusa perfil em uso', function () {
    $livre = Perfil::factory()->create();
    $emUso = User::factory()->create()->perfil;

    $this->actingAs($this->admin)->delete(route('perfis.destroy', $livre))->assertRedirect(route('perfis.index'));
    $this->actingAs($this->admin)->delete(route('perfis.destroy', $emUso))->assertSessionHasErrors('protecao');

    expect(Perfil::find($livre->id))->toBeNull()
        ->and(Perfil::find($emUso->id))->not->toBeNull();
});

it('mostra no menu só os itens permitidos', function () {
    $usuario = User::factory()->comPermissoes('usuarios.gerenciar')->create();

    $this->actingAs($usuario)
        ->get(route('inicio'))
        ->assertOk()
        ->assertSee(route('usuarios.index'))
        ->assertDontSee(route('perfis.index'))
        ->assertDontSee(route('auditoria.index'));
});
