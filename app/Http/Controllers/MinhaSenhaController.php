<?php

namespace App\Http\Controllers;

use App\Http\Requests\SenhaRequest;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MinhaSenhaController extends Controller
{
    public function edit(): View
    {
        return view('minha-senha.edit');
    }

    public function update(SenhaRequest $request, UsuarioService $usuarios): RedirectResponse
    {
        $usuarios->alterarSenha($request->user(), $request->validated('password'));
        $request->session()->regenerate();

        return redirect()->route('inicio')->with('sucesso', 'Senha alterada.');
    }
}
