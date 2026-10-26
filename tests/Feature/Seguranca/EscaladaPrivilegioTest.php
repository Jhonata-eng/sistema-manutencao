<?php

use App\Models\Cidade;
use App\Models\Perfil;
use App\Models\User;

/*
 * Um "gerente de usuários" de Campinas: gerencia usuários e enxerga ativos, mas não é administrador.
 * Ele não pode dar a ninguém (nem a si) mais acesso do que tem, nem assumir a conta de quem tem mais.
 */
beforeEach(function () {
    $this->campinas = Cidade::factory()->create();
    $this->sorocaba = Cidade::factory()->create();
    $this->tecnico = Perfil::firstWhere('nome', 'Técnico');
    // Tem tudo o que o Técnico tem, mais gerenciar usuários (e só em Campinas).
    $this->gerente = usuarioCom([...$this->tecnico->permissoes->pluck('chave'), 'usuarios.gerenciar'], $this->campinas);
    $this->actingAs($this->gerente);
});

function dadosUsuario(array $sobrescrever = []): array
{
    return [
        'name' => 'Novo Usuário', 'email' => 'novo@exemplo.com.br', 'perfil_id' => test()->tecnico->id,
        'cidades' => [test()->campinas->id], 'password' => 'senha-forte-1', 'password_confirmation' => 'senha-forte-1', 'ativo' => '1',
        ...$sobrescrever,
    ];
}

it('não atribui perfil com permissões que o autor não tem (nem a si mesmo)', function () {
    $administrador = Perfil::firstWhere('nome', 'Administrador');

    $this->post(route('usuarios.store'), dadosUsuario(['perfil_id' => $administrador->id]))->assertSessionHasErrors('perfil_id');
    $this->put(route('usuarios.update', $this->gerente), [...dadosUsuario(['perfil_id' => $administrador->id]), 'name' => $this->gerente->name, 'email' => $this->gerente->email, 'password' => null, 'password_confirmation' => null])
        ->assertSessionHasErrors('perfil_id');

    expect($this->gerente->fresh()->perfil_id)->not->toBe($administrador->id);
    $this->get(route('usuarios.create'))->assertDontSee('>Administrador</option>', false);
});

it('não vincula cidades às quais o autor não tem acesso', function () {
    $this->post(route('usuarios.store'), dadosUsuario(['cidades' => [$this->sorocaba->id]]))->assertSessionHasErrors('cidades.0');
});

it('cria e edita usuários com acesso igual ou menor ao do autor', function () {
    $this->post(route('usuarios.store'), dadosUsuario())->assertSessionHasNoErrors();

    expect(User::where('email', 'novo@exemplo.com.br')->first()->perfil->is($this->tecnico))->toBeTrue();
});

it('não edita nem redefine a senha de quem tem mais acesso', function () {
    $admin = User::factory()->administrador()->create();
    $deOutraCidade = User::factory()->create(['perfil_id' => $this->tecnico->id]);
    $deOutraCidade->cidades()->attach($this->sorocaba);

    foreach ([$admin, $deOutraCidade] as $alvo) {
        $this->get(route('usuarios.edit', $alvo))->assertForbidden();
        $this->get(route('usuarios.senha.edit', $alvo))->assertForbidden();
        $this->put(route('usuarios.senha.update', $alvo), ['password' => 'tomada-123', 'password_confirmation' => 'tomada-123'])->assertForbidden();
        $this->put(route('usuarios.update', $alvo), ['name' => 'X'])->assertForbidden();
    }

    $this->get(route('usuarios.index'))->assertDontSee(route('usuarios.edit', $admin));
});

it('o administrador continua administrando todos', function () {
    $this->actingAs(User::factory()->administrador()->create());

    $this->get(route('usuarios.edit', $this->gerente))->assertOk();
});
