<?php

return [

    // Quantos meses à frente as ocorrências "previstas" dos planos são geradas.
    'horizonte_meses' => (int) env('MANUTENCAO_HORIZONTE_MESES', 12),

    // Tamanho máximo de cada anexo, em megabytes.
    'anexo_tamanho_max_mb' => (int) env('ANEXO_TAMANHO_MAX_MB', 10),

    // Tentativas de login por e-mail + IP antes do bloqueio de 1 minuto.
    'login_max_tentativas' => (int) env('LOGIN_MAX_TENTATIVAS', 5),

];
