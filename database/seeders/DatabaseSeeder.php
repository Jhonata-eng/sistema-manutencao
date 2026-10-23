<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Dados obrigatórios do sistema. Pode ser rodado de novo sem duplicar nada.
 * O primeiro administrador é criado com `php artisan app:criar-admin`.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissoesSeeder::class,
            PerfisSeeder::class,
            TiposEquipamentoSeeder::class,
        ]);
    }
}
