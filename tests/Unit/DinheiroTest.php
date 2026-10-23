<?php

use App\Support\Dinheiro;

it('formata valores do banco no padrão brasileiro', function (?string $valor, string $esperado) {
    expect(Dinheiro::formatar($valor))->toBe($esperado);
})->with([
    ['1234.56', 'R$ 1.234,56'],
    ['0.00', 'R$ 0,00'],
    ['5', 'R$ 5,00'],
    ['1234567890.10', 'R$ 1.234.567.890,10'],
    ['-80.5', '-R$ 80,50'],
    [null, '—'],
]);

it('converte a entrada do usuário para decimal do banco', function (string $entrada, ?string $esperado) {
    expect(Dinheiro::paraDecimal($entrada))->toBe($esperado);
})->with([
    ['1.234,56', '1234.56'],
    ['1234,5', '1234.50'],
    ['R$ 10', '10.00'],
    ['1234.56', '1234.56'],
    ['1.234', '1234.00'],
    ['0,00', '0.00'],
    ['9.999.999.999,99', '9999999999.99'],
    ['10.000.000.000,00', null],
    ['12,345', null],
    ['abc', null],
    ['', null],
]);

it('formata datas em dd/mm/aaaa', function () {
    expect(data_br('2026-01-31'))->toBe('31/01/2026')
        ->and(data_br(null))->toBe('');
});
