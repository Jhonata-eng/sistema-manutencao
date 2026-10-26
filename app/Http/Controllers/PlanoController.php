<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarPlanoRequest;
use App\Models\Ativo;
use App\Models\Fornecedor;
use App\Models\PlanoManutencao;
use App\Services\PlanoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\View\View;

class PlanoController extends Controller
{
    public function __construct(private PlanoService $planos) {}

    #[Authorize('create', [PlanoManutencao::class, 'ativo'])]
    public function create(Ativo $ativo): View
    {
        $plano = new PlanoManutencao(['ativo' => true, 'periodicidade_meses' => 1, 'data_inicio' => today()]);
        $plano->setRelation('equipamento', $ativo);

        return $this->formulario($plano);
    }

    public function store(SalvarPlanoRequest $request, Ativo $ativo): RedirectResponse
    {
        $plano = new PlanoManutencao;
        $plano->ativo_id = $ativo->id;
        $this->planos->salvar($plano, $request->dados(), $request->user());

        return redirect()->route('planos.show', $plano)
            ->with('sucesso', "Plano criado com {$plano->manutencoes()->count()} manutenção(ões) prevista(s).");
    }

    #[Authorize('view', 'plano')]
    public function show(PlanoManutencao $plano): View
    {
        return view('planos.show', [
            'plano' => $plano->load(['equipamento.cidade', 'fornecedor']),
            'mediaReal' => $plano->mediaReal(),
            'ocorrencias' => $plano->manutencoes()->orderBy('data_prevista')->orderBy('id')->paginate(50),
        ]);
    }

    #[Authorize('update', 'plano')]
    public function edit(PlanoManutencao $plano): View
    {
        return $this->formulario($plano);
    }

    public function update(SalvarPlanoRequest $request, PlanoManutencao $plano): RedirectResponse
    {
        $this->planos->salvar($plano, $request->dados(), $request->user());

        return redirect()->route('planos.show', $plano)->with('sucesso', 'Plano atualizado.');
    }

    #[Authorize('delete', 'plano')]
    public function destroy(PlanoManutencao $plano): RedirectResponse
    {
        $this->planos->excluir($plano);

        return redirect()->route('ativos.show', $plano->ativo_id)->with('sucesso', "Plano \"{$plano->descricao}\" excluído.");
    }

    #[Authorize('update', 'plano')]
    public function atualizarValorMedio(Request $request, PlanoManutencao $plano): RedirectResponse
    {
        $media = $this->planos->atualizarValorMedio($plano, $request->user());

        return redirect()->route('planos.show', $plano)
            ->with('sucesso', 'Valor estimado atualizado para '.moeda($media).', aplicado às manutenções pendentes futuras.');
    }

    private function formulario(PlanoManutencao $plano): View
    {
        return view('planos.form', [
            'plano' => $plano,
            'fornecedores' => Fornecedor::selecionaveis($plano->fornecedor_id)->orderBy('nome')->pluck('nome', 'id'),
            'pendentes' => $plano->exists && $plano->ativo ? $plano->manutencoes()->pendentes()->count() : 0,
        ]);
    }
}
