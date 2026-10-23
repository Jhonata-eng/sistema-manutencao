<?php

namespace Database\Seeders;

use App\Models\TipoEquipamento;
use Illuminate\Database\Seeder;

/** Tipos citados na especificação; o cadastro continua editável pela tela. */
class TiposEquipamentoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Ar-condicionado', 'Gerador', 'Bebedouro industrial', 'Nobreak'] as $nome) {
            TipoEquipamento::firstOrCreate(['nome' => $nome]);
        }
    }
}
