<?php

namespace Database\Factories;

use App\Models\TipoEquipamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoEquipamento>
 */
class TipoEquipamentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => 'Tipo '.fake()->unique()->numerify('#####'),
            'ativo' => true,
        ];
    }
}
