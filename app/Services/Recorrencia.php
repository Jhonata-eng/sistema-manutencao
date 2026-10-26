<?php

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * Calendário fixo de um plano: a ocorrência k cai em início + k × periodicidade meses,
 * sempre contada a partir do início (31/01 → 28/02 → 31/03). Se o dia não existe no
 * mês, usa o último dia do mês.
 */
final class Recorrencia
{
    /**
     * Datas do calendário dentro de [$de, $ate) e até o término (inclusive), em ordem.
     *
     * @return list<CarbonImmutable>
     */
    public static function datas(
        CarbonImmutable $inicio,
        int $periodicidadeMeses,
        ?CarbonImmutable $termino,
        CarbonImmutable $de,
        CarbonImmutable $ate,
    ): array {
        $datas = [];

        for ($k = 0; ; $k++) {
            $data = $inicio->addMonthsNoOverflow($k * $periodicidadeMeses);

            if ($data->gte($ate) || ($termino && $data->gt($termino))) {
                return $datas;
            }

            if ($data->gte($de)) {
                $datas[] = $data;
            }
        }
    }
}
