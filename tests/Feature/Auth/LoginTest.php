<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('redireciona visitantes para o login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('exibe a tela de login', function () {
    $this->get(route('login'))->assertOk()->assertSee('Lembrar-me');
});

it('autentica com e-mail e senha corretos', function () {
    $usuario = User::factory()->create();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password'])
        ->assertRedirect(route('inicio'));

    $this->assertAuthenticatedAs($usuario);
});

it('recusa senha incorreta', function () {
    $usuario = User::factory()->create();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'errada'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

it('recusa usuário inativo com a mesma mensagem de credenciais inválidas', function () {
    $usuario = User::factory()->inativo()->create();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

it('bloqueia o login após o limite de tentativas, mesmo com a senha certa', function () {
    $usuario = User::factory()->create();

    foreach (range(1, config('manutencao.login_max_tentativas')) as $tentativa) {
        $this->post(route('login'), ['email' => $usuario->email, 'password' => 'errada']);
    }

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Muitas tentativas');
    $this->assertGuest();
});

it('grava o cookie de "lembrar-me" quando marcado', function () {
    $usuario = User::factory()->create();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password', 'lembrar' => '1'])
        ->assertCookie(Auth::guard()->getRecallerName());
});

it('encerra a sessão no logout', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('desconecta no próximo acesso quem foi inativado com a sessão aberta', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $usuario->update(['ativo' => false]);

    $this->get(route('inicio'))->assertRedirect(route('login'));

    $this->assertGuest();
});
