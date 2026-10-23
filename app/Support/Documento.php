<?php

namespace App\Support;

use Closure;

/**
 * CPF e CNPJ. O CNPJ pode ser alfanumérico (emitido a partir de jul/2026, IN RFB 2.229/2024):
 * os 12 primeiros caracteres aceitam letras e os 2 dígitos verificadores são sempre numéricos.
 */
final class Documento
{
    /** Remove a máscara e padroniza em maiúsculas: "12.abc.345/01de-35" → "12ABC34501DE35". Vazio vira null. */
    public static function normalizar(?string $documento): ?string
    {
        $documento = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $documento));

        return $documento === '' ? null : $documento;
    }

    /** Recebe o documento normalizado. */
    public static function valido(string $documento): bool
    {
        if (preg_match('/^\d{11}$/', $documento)) {
            return self::digitosConferem($documento, 9, fn (int $posicao, int $total) => $total + 1 - $posicao);
        }

        if (preg_match('/^[0-9A-Z]{12}\d{2}$/', $documento)) {
            return self::digitosConferem($documento, 12, fn (int $posicao, int $total) => ($total - 1 - $posicao) % 8 + 2);
        }

        return false;
    }

    /** CPF "000.000.000-00" ou CNPJ "00.000.000/0000-00"; outros valores voltam como estão. */
    public static function formatar(?string $documento): string
    {
        return match (strlen((string) $documento)) {
            11 => preg_replace('/^(.{3})(.{3})(.{3})(.{2})$/', '$1.$2.$3-$4', $documento),
            14 => preg_replace('/^(.{2})(.{3})(.{3})(.{4})(.{2})$/', '$1.$2.$3/$4-$5', $documento),
            default => (string) $documento,
        };
    }

    /**
     * Confere os 2 dígitos verificadores (módulo 11). Cada caractere vale ord - 48
     * (dígitos 0–9, letras A=17 … Z=42); $peso(posição, tamanho da base) dá o peso.
     */
    private static function digitosConferem(string $documento, int $tamanhoBase, Closure $peso): bool
    {
        if (count(array_unique(str_split($documento))) === 1) {
            return false;
        }

        for ($total = $tamanhoBase; $total <= $tamanhoBase + 1; $total++) {
            $soma = 0;
            for ($posicao = 0; $posicao < $total; $posicao++) {
                $soma += (ord($documento[$posicao]) - 48) * $peso($posicao, $total);
            }
            $resto = $soma % 11;

            if ((int) $documento[$total] !== ($resto < 2 ? 0 : 11 - $resto)) {
                return false;
            }
        }

        return true;
    }
}
