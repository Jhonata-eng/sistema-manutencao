<?php

namespace App\Http\Controllers;

use App\Enums\TipoAnexo;
use App\Http\Requests\RegistrarExecucaoRequest;
use App\Models\Fornecedor;
use App\Models\Manutencao;
use App\Services\ManutencaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\View\View;

class ExecucaoController extends Controller
{
    #[Authorize('registrarExecucao', 'manutencao')]
    public function create(Manutencao $manutencao): View
    {
        $manutencao->load(['ativo.cidade', 'plano']);
        $fornecedorPadrao = $manutencao->plano?->fornecedor_id;

        return view('manutencoes.execucao', [
            'manutencao' => $manutencao,
            'fornecedorPadrao' => $fornecedorPadrao,
            'fornecedores' => Fornecedor::selecionaveis($fornecedorPadrao)->orderBy('nome')->pluck('nome', 'id'),
            'tiposAnexo' => TipoAnexo::opcoes(),
        ]);
    }

    public function store(RegistrarExecucaoRequest $request, Manutencao $manutencao, ManutencaoService $manutencoes): RedirectResponse
    {
        $manutencoes->registrarExecucao($manutencao, $request->dadosExecucao(), $request->anexo(), $request->user());

        return redirect()->route('manutencoes.show', $manutencao)->with('sucesso', 'Execução registrada.');
    }
}
