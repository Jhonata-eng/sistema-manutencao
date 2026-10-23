<?php

use App\Support\Documento;

it('valida CPF e CNPJ (inclusive alfanumérico)', function (string $documento, bool $valido) {
    expect(Documento::valido($documento))->toBe($valido);
})->with([
    'CPF válido' => ['52998224725', true],
    'CPF com dígito errado' => ['52998224724', false],
    'CPF repetido' => ['11111111111', false],
    'CNPJ numérico válido' => ['11222333000181', true],
    'CNPJ numérico com dígito errado' => ['11222333000182', false],
    'CNPJ alfanumérico válido' => ['12ABC34501DE35', true],
    'CNPJ alfanumérico com dígito errado' => ['12ABC34501DE36', false],
    'CNPJ zerado' => ['00000000000000', false],
    'tamanho inválido' => ['1234567', false],
    'letra no dígito verificador' => ['12ABC34501DE3A', false],
]);

it('normaliza e formata', function () {
    expect(Documento::normalizar('12.abc.345/01de-35'))->toBe('12ABC34501DE35')
        ->and(Documento::normalizar(' '))->toBeNull()
        ->and(Documento::formatar('11222333000181'))->toBe('11.222.333/0001-81')
        ->and(Documento::formatar('52998224725'))->toBe('529.982.247-25');
});
