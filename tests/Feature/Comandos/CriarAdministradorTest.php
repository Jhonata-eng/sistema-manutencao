<?php

use App\Models\User;

it('cria o administrador pelas opções', function () {
    $this->artisan('app:criar-admin', ['--nome' => 'Admin', '--email' => 'admin@exemplo.com.br', '--senha' => 'senha-forte-1'])
        ->assertSuccessful();

    $admin = User::where('email', 'admin@exemplo.com.br')->first();
    expect($admin->perfil->nome)->toBe('Administrador')
        ->and($admin->ativo)->toBeTrue()
        ->and($admin->can('perfis.gerenciar'))->toBeTrue();
});

it('pergunta os dados que faltam', function () {
    $this->artisan('app:criar-admin')
        ->expectsQuestion('Nome', 'Admin')
        ->expectsQuestion('E-mail', 'admin@exemplo.com.br')
        ->expectsQuestion('Senha (mínimo de 8 caracteres)', 'senha-forte-1')
        ->assertSuccessful();

    expect(User::where('email', 'admin@exemplo.com.br')->exists())->toBeTrue();
});

it('recusa dados inválidos', function () {
    $this->artisan('app:criar-admin', ['--nome' => 'Admin', '--email' => 'invalido', '--senha' => 'curta'])
        ->assertFailed();

    expect(User::count())->toBe(0);
});
