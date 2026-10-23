<?php

namespace Database\Seeders;

use App\Models\Permissao;
use Illuminate\Database\Seeder;

/** Sincroniza a tabela permissoes com o catálogo Permissao::CATALOGO. */
class PermissoesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissao::CATALOGO as $chave => [$grupo, $descricao]) {
            Permissao::updateOrCreate(['chave' => $chave], ['grupo' => $grupo, 'descricao' => $descricao]);
        }
    }
}
