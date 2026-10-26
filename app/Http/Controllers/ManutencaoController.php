<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelarManutencaoRequest;
use App\Http\Requests\ReagendarManutencaoRequest;
use App\Models\Manutencao;
use App\Services\ManutencaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\View\View;

class ManutencaoController extends Controller
{
    public function __construct(private ManutencaoService $manutencoes) {}

    #[Authorize('view', 'manutencao')]
    public function show(Manutencao $manutencao): View
    {
        return view('manutencoes.show', [
            'manutencao' => $manutencao->load([
                'ativo.cidade', 'plano', 'fornecedor', 'registradoPor', 'canceladoPor',
                'anexos.enviadoPor', 'correcoesValor.usuario', 'correcoesValor.anexo',
            ]),
            'eventos' => $manutencao->eventos()->with('usuario')->latest('id')->get(),
        ]);
    }

    public function cancelar(CancelarManutencaoRequest $request, Manutencao $manutencao): RedirectResponse
    {
        $this->manutencoes->cancelar($manutencao, $request->validated('motivo_cancelamento'), $request->user());

        return redirect()->route('manutencoes.show', $manutencao)->with('sucesso', 'Manutenção cancelada.');
    }

    public function reagendar(ReagendarManutencaoRequest $request, Manutencao $manutencao): RedirectResponse
    {
        $this->manutencoes->reagendar($manutencao, $request->validated('nova_data'), $request->validated('motivo_reagendamento'));

        return redirect()->route('manutencoes.show', $manutencao)
            ->with('sucesso', 'Manutenção reagendada para '.data_br($request->validated('nova_data')).'.');
    }
}
