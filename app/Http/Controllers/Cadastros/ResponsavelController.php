<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarResponsavelRequest;
use App\Models\Responsavel;
use App\Models\User;
use App\Services\ExclusaoProtegida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResponsavelController extends Controller
{
    public function index(Request $request): View
    {
        return view('cadastros.responsaveis.index', [
            'responsaveis' => Responsavel::filtrar($request->only('busca', 'status'))->with('usuario')->orderBy('nome')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Responsavel(['ativo' => true]));
    }

    public function store(SalvarResponsavelRequest $request): RedirectResponse
    {
        $responsavel = Responsavel::create($request->validated());

        return redirect()->route('cadastros.responsaveis.index')->with('sucesso', "Responsável {$responsavel->nome} cadastrado.");
    }

    public function edit(Responsavel $responsavel): View
    {
        return $this->formulario($responsavel);
    }

    public function update(SalvarResponsavelRequest $request, Responsavel $responsavel): RedirectResponse
    {
        $responsavel->update($request->validated());

        return redirect()->route('cadastros.responsaveis.index')->with('sucesso', "Responsável {$responsavel->nome} atualizado.");
    }

    public function destroy(Responsavel $responsavel, ExclusaoProtegida $exclusao): RedirectResponse
    {
        $exclusao->excluir($responsavel, "o responsável {$responsavel->nome}");

        return redirect()->route('cadastros.responsaveis.index')->with('sucesso', "Responsável {$responsavel->nome} excluído.");
    }

    private function formulario(Responsavel $responsavel): View
    {
        return view('cadastros.responsaveis.form', [
            'responsavel' => $responsavel,
            'usuarios' => User::where('ativo', true)->orWhere('id', $responsavel->user_id)->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
