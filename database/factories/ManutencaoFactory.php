<?php

namespace Database\Factories;

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Models\Ativo;
use App\Models\Manutencao;
use App\Models\PlanoManutencao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Manutencao>
 */
class ManutencaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ativo_id' => Ativo::factory(),
            'plano_id' => null,
            'tipo' => TipoManutencao::Programada,
            'data_referencia' => today(),
            'data_prevista' => today(),
            'status' => StatusManutencao::Prevista,
            'valor_previsto' => '100.00',
        ];
    }

    /** Ocorrência de um plano na data de calendário informada. */
    public function doPlano(PlanoManutencao $plano, string $data): static
    {
        return $this->state(fn () => [
            'ativo_id' => $plano->ativo_id,
            'plano_id' => $plano->id,
            'data_referencia' => $data,
            'data_prevista' => $data,
            'valor_previsto' => $plano->valor_estimado,
        ]);
    }

    public function realizada(string $valorReal, ?string $data = null): static
    {
        return $this->state(fn (array $atributos) => [
            'status' => StatusManutencao::Realizada,
            'valor_real' => $valorReal,
            'data_execucao' => $data ?? $atributos['data_prevista'],
        ]);
    }
}
