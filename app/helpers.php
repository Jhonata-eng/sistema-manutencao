<?php

use App\Support\Dinheiro;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('moeda')) {
    /** "1234.5" → "R$ 1.234,50" */
    function moeda(string|int|null $valor, bool $simbolo = true): string
    {
        return Dinheiro::formatar($valor, $simbolo);
    }
}

if (! function_exists('data_br')) {
    /** Data no formato dd/mm/aaaa ("" se vazia). */
    function data_br(CarbonInterface|string|null $data): string
    {
        if (blank($data)) {
            return '';
        }

        return ($data instanceof CarbonInterface ? $data : Carbon::parse($data))->format('d/m/Y');
    }
}

if (! function_exists('data_hora_br')) {
    /** Data e hora no formato dd/mm/aaaa hh:mm ("" se vazia). */
    function data_hora_br(CarbonInterface|string|null $data): string
    {
        return blank($data) ? '' : Carbon::parse($data)->format('d/m/Y H:i');
    }
}

if (! function_exists('asset_versionado')) {
    /** URL de um arquivo de public/ com ?v=<data de modificação>, para invalidar o cache do navegador. */
    function asset_versionado(string $caminho): string
    {
        return asset($caminho).'?v='.@filemtime(public_path($caminho));
    }
}
