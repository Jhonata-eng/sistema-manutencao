<?php

namespace App\Http\Controllers;

use App\Enums\TipoAnexo;
use App\Http\Requests\CorrigirValorRequest;
use App\Models\Manutencao;
use App\Services\ManutencaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\View\View;

class CorrecaoValorController extends Controller
{
    #[Authorize('corrigirValor', 'manutencao')]
    public function create(Manutencao $manutencao): View
    {
        return view('manutencoes.correcao-valor', [
            'manutencao' => $manutencao->load('ativo'),
            'comprovantes' => $manutencao->anexos()
                ->whereIn('tipo', TipoAnexo::comprovantes())
                ->get()
                ->mapWithKeys(fn ($anexo) => [$anexo->id => $anexo->descricao()]),
            'tiposAnexo' => TipoAnexo::opcoes(TipoAnexo::comprovantes()),
        ]);
    }

    public function store(CorrigirValorRequest $request, Manutencao $manutencao, ManutencaoService $manutencoes): RedirectResponse
    {
        $correcao = $manutencoes->corrigirValor(
            $manutencao,
            $request->validated('valor_novo'),
            $request->validated('motivo'),
            $request->validated('comprovante_id'),
            $request->anexo(),
            $request->user(),
        );

        return redirect()->route('manutencoes.show', $manutencao)
            ->with('sucesso', 'Valor corrigido de '.moeda($correcao->valor_anterior).' para '.moeda($correcao->valor_novo).'.');
    }
}
