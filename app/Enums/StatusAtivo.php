<?php

namespace App\Enums;

enum StatusAtivo: string
{
    case EmOperacao = 'em_operacao';
    case Inativo = 'inativo';
    case Baixado = 'baixado';

    public function rotulo(): string
    {
        return match ($this) {
            self::EmOperacao => 'Em operação',
            self::Inativo => 'Inativo',
            self::Baixado => 'Baixado',
        };
    }

    /** Classe de cor do badge do Bootstrap. */
    public function cor(): string
    {
        return match ($this) {
            self::EmOperacao => 'success',
            self::Inativo => 'secondary',
            self::Baixado => 'dark',
        };
    }

    /** @return array<string, string> valor => rótulo, para selects */
    public static function opcoes(): array
    {
        return array_column(array_map(fn (self $s) => [$s->value, $s->rotulo()], self::cases()), 1, 0);
    }
}
