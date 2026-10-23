<?php

namespace App\Rules;

use App\Support\Documento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Espera o valor já normalizado com Documento::normalizar(). */
class CpfCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Documento::valido($value)) {
            $fail('O :attribute não é um CPF ou CNPJ válido.');
        }
    }
}
