<?php

namespace Database\Factories;

use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fornecedor>
 */
class FornecedorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->company(),
            'documento' => preg_replace('/\D/', '', fake()->unique()->cnpj()),
            'telefone' => fake()->numerify('(##) ####-####'),
            'email' => fake()->companyEmail(),
            'ativo' => true,
        ];
    }
}
