<?php

namespace Database\Factories;

use App\Models\Cidade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cidade>
 */
class CidadeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->city(),
            'uf' => fake()->stateAbbr(),
            'ativo' => true,
        ];
    }
}
