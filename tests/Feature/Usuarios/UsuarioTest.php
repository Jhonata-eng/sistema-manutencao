<?php

use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

it('bloqueia quem não tem usuarios.gerenciar', function () {
    $this->actingAs(User::factory()->comPermissoes('dashboard.visualizar')->create());

    $this->get(route('usuarios.index'))->assertForbidden();
    $this->get(route('usuarios.create'))->assertForbidden();
    $this->post(route('usuarios.store'), [])->assertForbidden();
});

it('lista e filtra usuários', function () {
    User::factory()->create(['name' => 'Maria Técnica']);
    User::factory()->inativo()->create(['name' => 'João Inativo']);

    $this->actingAs($this->admin)
        ->get(route('usuarios.index', ['busca' => 'Maria']))
        ->assertOk()
        ->assertSee('Maria Técnica')
        ->assertDontSee('João Inativo');

    $this->actingAs($this->admin)
        ->get(route('usuarios.index', ['status' => 'inativo']))
        ->assertSee('João Inativo')
        ->assertDontSee('Maria Técnica');
});

it('cria usuário com perfil e cidades, sem gravar a senha na auditoria', function () {
    $tecnico = Perfil::where('nome', 'Técnico')->first();
    $cidades = Cidade::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), [
            'name' => 'Ana Souza',
            'email' => 'ana@exemplo.com.br',
            'perfil_id' => $tecnico->id,
            'cidades' => $cidades->pluck('id')->all(),
            'password' => 'senha-forte-1',
            'password_confirmation' => 'senha-forte-1',
            'ativo' => '1',
        ])
        ->assertRedirect(route('usuarios.index'));

    $ana = User::where('email', 'ana@exemplo.com.br')->first();
    expect($ana->perfil->is($tecnico))->toBeTrue()
        ->and($ana->cidades->pluck('id')->sort()->values()->all())->toBe($cidades->pluck('id')->sort()->values()->all())
        ->and(Hash::check('senha-forte-1', $ana->password))->toBeTrue();

    $criacao = Auditoria::where('evento', 'criado')->where('auditavel_type', 'usuario')->where('auditavel_id', $ana->id)->first();
    expect($criacao->user_id)->toBe($this->admin->id)
        ->and($criacao->depois)->not->toHaveKey('password')
        ->and($criacao->depois)->not->toHaveKey('remember_token');

    expect(Auditoria::where('evento', 'cidades_alteradas')->where('auditavel_id', $ana->id)->first()->depois['cidades'])
        ->toHaveCount(2);
});

it('não aceita e-mail repetido', function () {
    $existente = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), [
            'name' => 'Outro',
            'email' => $existente->email,
            'perfil_id' => $existente->perfil_id,
            'password' => 'senha-forte-1',
            'password_confirmation' => 'senha-forte-1',
            'ativo' => '1',
        ])
        ->assertSessionHasErrors('email');
});

it('edita usuário, troca cidades e audita só o que mudou', function () {
    [$cidadeA, $cidadeB] = Cidade::factory()->count(2)->create();
    $usuario = User::factory()->create(['name' => 'Nome Antigo']);
    $usuario->cidades()->attach($cidadeA);

    $this->actingAs($this->admin)
        ->put(route('usuarios.update', $usuario), [
            'name' => 'Nome Novo',
            'email' => $usuario->email,
            'perfil_id' => $usuario->perfil_id,
            'cidades' => [$cidadeB->id],
            'ativo' => '1',
        ])
        ->assertRedirect(route('usuarios.index'));

    expect($usuario->fresh()->name)->toBe('Nome Novo')
        ->and($usuario->cidades()->pluck('cidades.id')->all())->toBe([$cidadeB->id]);

    $edicao = Auditoria::where('evento', 'editado')->where('auditavel_id', $usuario->id)->first();
    expect($edicao->antes)->toBe(['name' => 'Nome Antigo'])
        ->and($edicao->depois)->toBe(['name' => 'Nome Novo']);

    $cidades = Auditoria::where('evento', 'cidades_alteradas')->where('auditavel_id', $usuario->id)->latest('id')->first();
    expect($cidades->antes['cidades'])->toBe([$cidadeA->nome_uf])
        ->and($cidades->depois['cidades'])->toBe([$cidadeB->nome_uf]);
});

it('não deixa o usuário inativar a si mesmo', function () {
    $this->actingAs($this->admin)
        ->put(route('usuarios.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'perfil_id' => $this->admin->perfil_id,
            'ativo' => '0',
        ])
        ->assertSessionHasErrors('ativo');

    expect($this->admin->fresh()->ativo)->toBeTrue();
});

it('não deixa o único administrador tirar de si o acesso administrativo', function () {
    $gestor = Perfil::firstWhere('nome', 'Gestor');

    $this->actingAs($this->admin)
        ->put(route('usuarios.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'perfil_id' => $gestor->id,
            'ativo' => '1',
        ])
        ->assertSessionHasErrors('protecao');

    expect($this->admin->fresh()->perfil->nome)->toBe('Administrador');
});

it('redefine a senha de outro usuário e encerra as sessões dele', function () {
    $usuario = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'sessao-aberta', 'user_id' => $usuario->id, 'payload' => '', 'last_activity' => time()]);
    $tokenAntigo = $usuario->remember_token;

    $this->actingAs($this->admin)
        ->put(route('usuarios.senha.update', $usuario), [
            'password' => 'redefinida-123',
            'password_confirmation' => 'redefinida-123',
        ])
        ->assertRedirect(route('usuarios.index'));

    $usuario->refresh();
    expect(Hash::check('redefinida-123', $usuario->password))->toBeTrue()
        ->and($usuario->remember_token)->not->toBe($tokenAntigo)
        ->and(DB::table('sessions')->where('user_id', $usuario->id)->exists())->toBeFalse()
        ->and(Auditoria::where('evento', 'senha_redefinida')->where('auditavel_id', $usuario->id)->value('user_id'))->toBe($this->admin->id);
});

it('manda o administrador para "minha senha" ao tentar redefinir a própria', function () {
    $this->actingAs($this->admin)
        ->get(route('usuarios.senha.edit', $this->admin))
        ->assertRedirect(route('minha-senha.edit'));

    $this->actingAs($this->admin)
        ->put(route('usuarios.senha.update', $this->admin), [
            'password' => 'redefinida-123',
            'password_confirmation' => 'redefinida-123',
        ])
        ->assertForbidden();
});
