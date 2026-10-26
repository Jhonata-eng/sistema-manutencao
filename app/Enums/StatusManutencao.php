<?php

namespace App\Enums;

/** Status gravados. "Atrasada" não é status: é prevista com data_prevista anterior a hoje. */
enum StatusManutencao: string
{
    case Prevista = 'prevista';
    case Realizada = 'realizada';
    case Cancelada = 'cancelada';

    public function rotulo(): string
    {
        return match ($this) {
            self::Prevista => 'Prevista',
            self::Realizada => 'Realizada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Prevista => 'primary',
            self::Realizada => 'success',
            self::Cancelada => 'secondary',
        };
    }
}
