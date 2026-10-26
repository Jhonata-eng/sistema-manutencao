<?php

namespace App\Http\Requests\Concerns;

use App\Support\Dinheiro;

/** Converte "1.234,56" para "1234.56" antes da validação. Texto inválido fica como veio e cai na regra numeric. */
trait NormalizaDinheiro
{
    protected function normalizarDinheiro(string ...$campos): void
    {
        foreach ($campos as $campo) {
            $valor = $this->filled($campo) ? Dinheiro::paraDecimal((string) $this->input($campo)) : null;

            if ($valor !== null) {
                $this->merge([$campo => $valor]);
            }
        }
    }
}
