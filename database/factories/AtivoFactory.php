<?php

namespace Database\Factories;

use App\Enums\StatusAtivo;
use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\TipoEquipamento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ativo>
 */
class AtivoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tipo_equipamento_id' => TipoEquipamento::factory(),
            'marca' => fake()->randomElement(['Carrier', 'Daikin', 'Stemac', 'APC', 'SMS']),
            'modelo' => strtoupper(fake()->bothify('??-####')),
            'numero_serie' => fake()->bothify('SN########'),
            'ano_fabricacao' => fake()->numberBetween(2010, 2026),
            'patrimonio' => fake()->unique()->numerify('PAT-######'),
            'capacidade' => null,
            'cidade_id' => Cidade::factory(),
            'localizacao' => 'POP '.fake()->word(),
            'status' => StatusAtivo::EmOperacao,
            'criado_por' => User::factory(),
        ];
    }
}
