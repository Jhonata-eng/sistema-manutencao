<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarTipoEquipamentoRequest;
use App\Models\TipoEquipamento;
use App\Services\ExclusaoProtegida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TipoEquipamentoController extends Controller
{
    public function index(Request $request): View
    {
        return view('cadastros.tipos.index', [
            'tipos' => TipoEquipamento::filtrar($request->only('busca', 'status'))->withCount('ativos')->orderBy('nome')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cadastros.tipos.form', ['tipo' => new TipoEquipamento(['ativo' => true])]);
    }

    public function store(SalvarTipoEquipamentoRequest $request): RedirectResponse
    {
        $tipo = TipoEquipamento::create($request->validated());

        return redirect()->route('cadastros.tipos.index')->with('sucesso', "Tipo {$tipo->nome} cadastrado.");
    }

    public function edit(TipoEquipamento $tipo): View
    {
        return view('cadastros.tipos.form', ['tipo' => $tipo]);
    }

    public function update(SalvarTipoEquipamentoRequest $request, TipoEquipamento $tipo): RedirectResponse
    {
        $tipo->update($request->validated());

        return redirect()->route('cadastros.tipos.index')->with('sucesso', "Tipo {$tipo->nome} atualizado.");
    }

    public function destroy(TipoEquipamento $tipo, ExclusaoProtegida $exclusao): RedirectResponse
    {
        $exclusao->excluir($tipo, "o tipo {$tipo->nome}");

        return redirect()->route('cadastros.tipos.index')->with('sucesso', "Tipo {$tipo->nome} excluído.");
    }
}
