<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class InicioController extends Controller
{
    // O dashboard (cards e gráficos) é construído na Fase 5.
    public function __invoke(): View
    {
        return view('inicio');
    }
}
