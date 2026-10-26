<?php

namespace App\Console\Commands;

use App\Services\GeradorOcorrencias;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('manutencoes:estender-horizonte')]
#[Description('Gera as manutenções previstas dos planos ativos até o fim do horizonte configurado')]
class EstenderHorizonte extends Command
{
    public function handle(GeradorOcorrencias $ocorrencias): int
    {
        $geradas = $ocorrencias->estenderHorizonte();
        $this->info("{$geradas} manutenção(ões) prevista(s) gerada(s).");

        return self::SUCCESS;
    }
}
