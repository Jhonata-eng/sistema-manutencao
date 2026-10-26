<?php

namespace Database\Factories;

use App\Models\Ativo;
use App\Models\PlanoManutencao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cria só o plano; as ocorrências são geradas pelo PlanoService/GeradorOcorrencias.
 *
 * @extends Factory<PlanoManutencao>
 */
class PlanoManutencaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ativo_id' => Ativo::factory(),
            'descricao' => fake()->randomElement(['Limpeza de filtros', 'Revisão geral', 'Troca de óleo', 'Teste de baterias']),
            'periodicidade_meses' => 1,
            'data_inicio' => today(),
            'data_termino' => null,
            'valor_estimado' => '150.00',
            'fornecedor_id' => null,
            'ativo' => true,
        ];
    }
}
