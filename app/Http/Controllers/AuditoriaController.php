<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        return view('auditoria.index', [
            'registros' => Auditoria::query()
                ->with('usuario')
                ->filtrar($request->only('usuario', 'evento', 'tipo', 'registro', 'de', 'ate'))
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'usuarios' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function show(Auditoria $auditoria): View
    {
        return view('auditoria.show', ['registro' => $auditoria->load('usuario')]);
    }
}
