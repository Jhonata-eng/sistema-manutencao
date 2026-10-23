<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarCidadeRequest;
use App\Models\Cidade;
use App\Services\ExclusaoProtegida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CidadeController extends Controller
{
    public function index(Request $request): View
    {
        return view('cadastros.cidades.index', [
            'cidades' => Cidade::filtrar($request->only('busca', 'status'))->orderBy('nome')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cadastros.cidades.form', ['cidade' => new Cidade(['ativo' => true])]);
    }

    public function store(SalvarCidadeRequest $request): RedirectResponse
    {
        $cidade = Cidade::create($request->validated());

        return redirect()->route('cadastros.cidades.index')->with('sucesso', "Cidade {$cidade->nome_uf} cadastrada.");
    }

    public function edit(Cidade $cidade): View
    {
        return view('cadastros.cidades.form', ['cidade' => $cidade]);
    }

    public function update(SalvarCidadeRequest $request, Cidade $cidade): RedirectResponse
    {
        $cidade->update($request->validated());

        return redirect()->route('cadastros.cidades.index')->with('sucesso', "Cidade {$cidade->nome_uf} atualizada.");
    }

    public function destroy(Cidade $cidade, ExclusaoProtegida $exclusao): RedirectResponse
    {
        $exclusao->excluir($cidade, "a cidade {$cidade->nome_uf}");

        return redirect()->route('cadastros.cidades.index')->with('sucesso', "Cidade {$cidade->nome_uf} excluída.");
    }
}
