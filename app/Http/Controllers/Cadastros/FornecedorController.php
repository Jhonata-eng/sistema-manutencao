<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarFornecedorRequest;
use App\Models\Fornecedor;
use App\Services\ExclusaoProtegida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FornecedorController extends Controller
{
    public function index(Request $request): View
    {
        return view('cadastros.fornecedores.index', [
            'fornecedores' => Fornecedor::filtrar($request->only('busca', 'status'))->orderBy('nome')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cadastros.fornecedores.form', ['fornecedor' => new Fornecedor(['ativo' => true])]);
    }

    public function store(SalvarFornecedorRequest $request): RedirectResponse
    {
        $fornecedor = Fornecedor::create($request->validated());

        return redirect()->route('cadastros.fornecedores.index')->with('sucesso', "Fornecedor {$fornecedor->nome} cadastrado.");
    }

    public function edit(Fornecedor $fornecedor): View
    {
        return view('cadastros.fornecedores.form', ['fornecedor' => $fornecedor]);
    }

    public function update(SalvarFornecedorRequest $request, Fornecedor $fornecedor): RedirectResponse
    {
        $fornecedor->update($request->validated());

        return redirect()->route('cadastros.fornecedores.index')->with('sucesso', "Fornecedor {$fornecedor->nome} atualizado.");
    }

    public function destroy(Fornecedor $fornecedor, ExclusaoProtegida $exclusao): RedirectResponse
    {
        $exclusao->excluir($fornecedor, "o fornecedor {$fornecedor->nome}");

        return redirect()->route('cadastros.fornecedores.index')->with('sucesso', "Fornecedor {$fornecedor->nome} excluído.");
    }
}
