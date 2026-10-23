<?php

namespace Database\Factories;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Perfil>
 */
class PerfilFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => 'Perfil '.fake()->unique()->numerify('#####'),
            'descricao' => null,
        ];
    }

    public function comPermissoes(string ...$chaves): static
    {
        return $this->afterCreating(function (Perfil $perfil) use ($chaves) {
            $perfil->permissoes()->sync(Permissao::whereIn('chave', $chaves)->pluck('id'));
        });
    }
}
