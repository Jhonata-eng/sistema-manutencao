<?php

use App\Services\Recorrencia;
use Carbon\CarbonImmutable;

function datasRecorrencia(string $inicio, int $meses, ?string $termino, string $de, string $ate): array
{
    $datas = Recorrencia::datas(
        CarbonImmutable::parse($inicio),
        $meses,
        $termino ? CarbonImmutable::parse($termino) : null,
        CarbonImmutable::parse($de),
        CarbonImmutable::parse($ate),
    );

    return array_map(fn (CarbonImmutable $data) => $data->toDateString(), $datas);
}

it('usa o último dia do mês quando o dia não existe, sempre contando do início', function () {
    expect(datasRecorrencia('2026-01-31', 1, null, '2026-01-01', '2026-08-01'))->toBe([
        '2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31', '2026-06-30', '2026-07-31',
    ]);
});

it('trata 29 de fevereiro em planos anuais', function () {
    expect(datasRecorrencia('2028-02-29', 12, null, '2028-01-01', '2032-03-01'))->toBe([
        '2028-02-29', '2029-02-28', '2030-02-28', '2031-02-28', '2032-02-29',
    ]);
});

it('respeita periodicidade personalizada e inclui a data de término', function () {
    expect(datasRecorrencia('2026-11-30', 5, '2027-09-30', '2026-01-01', '2030-01-01'))->toBe([
        '2026-11-30', '2027-04-30', '2027-09-30',
    ]);
});

it('devolve só a janela pedida, com início inclusivo e fim exclusivo', function () {
    expect(datasRecorrencia('2026-01-10', 1, null, '2026-03-10', '2026-06-10'))->toBe([
        '2026-03-10', '2026-04-10', '2026-05-10',
    ]);
});
