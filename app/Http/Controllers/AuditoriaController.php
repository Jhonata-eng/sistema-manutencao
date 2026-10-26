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
                ->visivelPara($request->user())
                ->with('usuario')
                ->filtrar($request->only('usuario', 'evento', 'tipo', 'registro', 'de', 'ate'))
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'usuarios' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function show(Request $request, Auditoria $auditoria): View
    {
        abort_unless(Auditoria::visivelPara($request->user())->whereKey($auditoria->id)->exists(), 403);

        return view('auditoria.show', ['registro' => $auditoria->load('usuario')]);
    }
}
