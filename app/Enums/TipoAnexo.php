<?php

namespace App\Enums;

enum TipoAnexo: string
{
    case NotaFiscal = 'nota_fiscal';
    case Recibo = 'recibo';
    case Laudo = 'laudo';
    case Foto = 'foto';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::NotaFiscal => 'Nota fiscal',
            self::Recibo => 'Recibo',
            self::Laudo => 'Laudo',
            self::Foto => 'Foto',
            self::Outro => 'Outro',
        };
    }

    /** Tipos que contam como comprovante de pagamento ("sem comprovante" e correção de valor). */
    public static function comprovantes(): array
    {
        return [self::NotaFiscal, self::Recibo];
    }

    /** @param  list<self>|null  $tipos */
    public static function opcoes(?array $tipos = null): array
    {
        return array_column(array_map(fn (self $t) => [$t->value, $t->rotulo()], $tipos ?? self::cases()), 1, 0);
    }
}
