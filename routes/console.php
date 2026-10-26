<?php

use Illuminate\Support\Facades\Schedule;

// Mantém sempre o horizonte de manutenções previstas (config manutencao.horizonte_meses) à frente.
Schedule::command('manutencoes:estender-horizonte')->dailyAt('01:00')->withoutOverlapping();
