<?php

namespace Database\Factories;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'perfil_id' => Perfil::factory(),
            'ativo' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /** Usuário com um perfil próprio contendo exatamente estas permissões. */
    public function comPermissoes(string ...$chaves): static
    {
        return $this->state(fn () => ['perfil_id' => Perfil::factory()->comPermissoes(...$chaves)]);
    }

    /** Usuário com o perfil padrão "Administrador" (criado pelo DatabaseSeeder). */
    public function administrador(): static
    {
        return $this->state(fn () => ['perfil_id' => Perfil::where('nome', 'Administrador')->value('id')]);
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['ativo' => false]);
    }
}
