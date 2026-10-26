<?php

namespace Database\Factories;

use App\Enums\TipoAnexo;
use App\Models\Anexo;
use App\Models\Manutencao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Só o registro; para ter o arquivo no disco, envie pelo AnexoService ou pela rota.
 *
 * @extends Factory<Anexo>
 */
class AnexoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'manutencao_id' => Manutencao::factory()->realizada('100.00'),
            'tipo' => TipoAnexo::NotaFiscal,
            'numero_nota' => fake()->numerify('#####'),
            'nome_original' => 'nota.pdf',
            'caminho' => 'anexos/teste/'.fake()->uuid().'.pdf',
            'mime' => 'application/pdf',
            'tamanho' => 1024,
            'enviado_por' => User::factory(),
        ];
    }
}
