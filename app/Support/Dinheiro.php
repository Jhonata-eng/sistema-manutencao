<?php

namespace App\Support;

/**
 * Conversão de valores monetários sem passar por float.
 * No banco e no PHP os valores circulam como string decimal ("1234.56").
 */
final class Dinheiro
{
    /** "1234.5" → "R$ 1.234,50" (ou "1.234,50" sem símbolo). Nulo vira "—". */
    public static function formatar(string|int|null $valor, bool $simbolo = true): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        $valor = (string) $valor;
        $negativo = str_starts_with($valor, '-');
        [$inteiro, $fracao] = array_pad(explode('.', ltrim($valor, '-'), 2), 2, '');

        $inteiro = ltrim($inteiro, '0') ?: '0';
        $inteiro = strrev(implode('.', str_split(strrev($inteiro), 3)));
        $fracao = substr(str_pad($fracao, 2, '0'), 0, 2);

        return ($negativo ? '-' : '').($simbolo ? 'R$ ' : '')."{$inteiro},{$fracao}";
    }

    /**
     * Entrada do usuário → decimal do banco: "1.234,56", "1234,5", "R$ 10" ou "1234.56" → "1234.56".
     * Retorna null se o texto não for um valor válido para DECIMAL(12,2).
     */
    public static function paraDecimal(?string $texto): ?string
    {
        $texto = str_replace(['R$', ' ', "\u{00A0}"], '', trim((string) $texto));

        if (str_contains($texto, ',')) {
            $texto = str_replace(['.', ','], ['', '.'], $texto);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $texto)) {
            $texto = str_replace('.', '', $texto);
        }

        if (! preg_match('/^(-?)(\d{1,10})(?:\.(\d{1,2}))?$/', $texto, $partes)) {
            return null;
        }

        $inteiro = ltrim($partes[2], '0') ?: '0';
        $fracao = str_pad($partes[3] ?? '', 2, '0');
        $sinal = $partes[1] === '-' && ($inteiro !== '0' || $fracao !== '00') ? '-' : '';

        return "{$sinal}{$inteiro}.{$fracao}";
    }
}
