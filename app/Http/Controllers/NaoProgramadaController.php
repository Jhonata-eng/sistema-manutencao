<?php

namespace App\Http\Controllers;

use App\Enums\TipoAnexo;
use App\Http\Requests\LancarNaoProgramadaRequest;
use App\Models\Ativo;
use App\Models\Fornecedor;
use App\Models\Manutencao;
use App\Services\ManutencaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Passo 1: buscar o ativo (patrimônio, marca/modelo ou cidade) entre os acessíveis ao usuário.
 * Passo 2 (?ativo=ID): formulário de lançamento.
 */
class NaoProgramadaController extends Controller
{
    public function create(Request $request): View
    {
        $usuario = $request->user();

        if ($request->filled('ativo')) {
            $ativo = Ativo::visivelPara($usuario)->with('cidade')->findOrFail($request->integer('ativo'));
            Gate::authorize('lancarNaoProgramada', [Manutencao::class, $ativo]);

            return view('manutencoes.nao-programada', [
                'ativo' => $ativo,
                'fornecedores' => Fornecedor::selecionaveis()->orderBy('nome')->pluck('nome', 'id'),
                'tiposAnexo' => TipoAnexo::opcoes(),
            ]);
        }

        $buscou = $request->filled('busca') || $request->filled('cidade');

        return view('manutencoes.escolher-ativo', [
            'cidades' => $usuario->cidadesAcessiveis()->orderBy('nome')->get()->pluck('nome_uf', 'id'),
            'ativos' => $buscou
                ? Ativo::visivelPara($usuario)->with(['tipo', 'cidade'])->filtrar($request->only('busca', 'cidade'))
                    ->orderBy('patrimonio')->paginate(15)->withQueryString()
                : null,
        ]);
    }

    public function store(LancarNaoProgramadaRequest $request, ManutencaoService $manutencoes): RedirectResponse
    {
        $manutencao = $manutencoes->lancarNaoProgramada($request->ativo(), $request->dadosExecucao(), $request->anexo(), $request->user());

        return redirect()->route('manutencoes.show', $manutencao)->with('sucesso', 'Manutenção não programada lançada.');
    }
}
