<?php

namespace App\Enums;

enum TipoManutencao: string
{
    case Programada = 'programada';
    case NaoProgramada = 'nao_programada';

    public function rotulo(): string
    {
        return match ($this) {
            self::Programada => 'Programada',
            self::NaoProgramada => 'Não programada',
        };
    }
}
