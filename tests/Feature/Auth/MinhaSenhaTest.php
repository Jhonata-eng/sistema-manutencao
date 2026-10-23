<?php

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('altera a própria senha informando a senha atual', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->put(route('minha-senha.update'), [
            'senha_atual' => 'password',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])
        ->assertRedirect(route('inicio'));

    expect(Hash::check('nova-senha-123', $usuario->fresh()->password))->toBeTrue()
        ->and(Auditoria::where('evento', 'senha_alterada')->where('auditavel_id', $usuario->id)->exists())->toBeTrue();
});

it('recusa a troca com a senha atual errada', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->put(route('minha-senha.update'), [
            'senha_atual' => 'errada',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])
        ->assertSessionHasErrors('senha_atual');

    expect(Hash::check('password', $usuario->fresh()->password))->toBeTrue();
});

it('exige confirmação e mínimo de 8 caracteres', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('minha-senha.update'), [
            'senha_atual' => 'password',
            'password' => 'curta',
            'password_confirmation' => 'outra',
        ])
        ->assertSessionHasErrors('password');
});
