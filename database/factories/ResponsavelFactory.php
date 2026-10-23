<?php

namespace Database\Factories;

use App\Models\Responsavel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Responsavel>
 */
class ResponsavelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->safeEmail(),
            'telefone' => fake()->numerify('(##) #####-####'),
            'setor' => fake()->randomElement(['Infraestrutura', 'Facilities', 'NOC']),
            'ativo' => true,
        ];
    }
}
