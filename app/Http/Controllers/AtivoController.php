<?php

namespace App\Http\Controllers;

use App\Enums\StatusAtivo;
use App\Enums\StatusManutencao;
use App\Http\Requests\SalvarAtivoRequest;
use App\Models\Ativo;
use App\Models\Responsavel;
use App\Models\TipoEquipamento;
use App\Services\AtivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AtivoController extends Controller
{
    public function __construct(private AtivoService $ativos) {}

    #[Authorize('viewAny', Ativo::class)]
    public function index(Request $request): View
    {
        $usuario = $request->user();

        return view('ativos.index', [
            'ativos' => Ativo::visivelPara($usuario)
                ->with(['tipo', 'cidade', 'responsavel'])
                ->filtrar($request->only('busca', 'cidade', 'tipo', 'responsavel', 'status'))
                ->orderBy('patrimonio')
                ->paginate(20)
                ->withQueryString(),
            'cidades' => $usuario->cidadesAcessiveis()->orderBy('nome')->get()->pluck('nome_uf', 'id'),
            'tipos' => TipoEquipamento::orderBy('nome')->pluck('nome', 'id'),
            'responsaveis' => Responsavel::orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    #[Authorize('create', Ativo::class)]
    public function create(Request $request): View
    {
        return $this->formulario($request, new Ativo(['status' => StatusAtivo::EmOperacao]));
    }

    public function store(SalvarAtivoRequest $request): RedirectResponse
    {
        $ativo = $this->ativos->salvar(new Ativo, $request->dados(), $request->user(), $request->file('foto'));

        return redirect()->route('ativos.show', $ativo)->with('sucesso', "Ativo {$ativo->patrimonio} cadastrado.");
    }

    #[Authorize('view', 'ativo')]
    public function show(Ativo $ativo): View
    {
        return view('ativos.show', [
            'ativo' => $ativo->load(['tipo', 'cidade', 'responsavel', 'criador']),
            'planos' => $ativo->planos()
                ->withMin(['manutencoes as proxima_ocorrencia' => fn ($q) => $q->pendentesFuturas()], 'data_prevista')
                ->orderByDesc('ativo')
                ->orderBy('descricao')
                ->get(),
            'pendentes' => $this->ativos->pendentes($ativo),
            'historico' => $ativo->manutencoes()
                ->historico()
                ->with(['ativo', 'plano', 'anexos'])
                ->orderByDesc('data_efetiva')
                ->orderByDesc('id')
                ->paginate(15, pageName: 'historico'),
            // SUM em SQL devolve string decimal; ->sum() do Laravel converteria para float.
            'totalGasto' => $ativo->manutencoes()
                ->where('status', StatusManutencao::Realizada)
                ->selectRaw('COALESCE(SUM(valor_real), 0) as total')
                ->value('total'),
        ]);
    }

    #[Authorize('update', 'ativo')]
    public function edit(Request $request, Ativo $ativo): View
    {
        return $this->formulario($request, $ativo);
    }

    public function update(SalvarAtivoRequest $request, Ativo $ativo): RedirectResponse
    {
        $this->ativos->salvar($ativo, $request->dados(), $request->user(), $request->file('foto'), $request->boolean('remover_foto'));

        return redirect()->route('ativos.show', $ativo)->with('sucesso', "Ativo {$ativo->patrimonio} atualizado.");
    }

    #[Authorize('delete', 'ativo')]
    public function destroy(Request $request, Ativo $ativo): RedirectResponse
    {
        $this->ativos->excluir($ativo, $request->user());

        return redirect()->route('ativos.index')->with('sucesso', "Ativo {$ativo->patrimonio} excluído.");
    }

    #[Authorize('view', 'ativo')]
    public function foto(Ativo $ativo): StreamedResponse
    {
        abort_unless($ativo->foto_path && Storage::disk('local')->exists($ativo->foto_path), 404);

        return Storage::disk('local')->response($ativo->foto_path);
    }

    private function formulario(Request $request, Ativo $ativo): View
    {
        return view('ativos.form', [
            'ativo' => $ativo,
            'tipos' => TipoEquipamento::selecionaveis($ativo->tipo_equipamento_id)->orderBy('nome')->pluck('nome', 'id'),
            'cidades' => $request->user()->cidadesAcessiveis()->selecionaveis($ativo->cidade_id)->orderBy('nome')->get()->pluck('nome_uf', 'id'),
            'responsaveis' => Responsavel::selecionaveis($ativo->responsavel_id)->orderBy('nome')->pluck('nome', 'id'),
            'pendentes' => $ativo->emOperacao() ? $this->ativos->pendentes($ativo) : 0,
        ]);
    }
}
