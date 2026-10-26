<?php

namespace App\Http\Controllers;

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Http\Requests\CancelarManutencaoRequest;
use App\Http\Requests\FiltrarManutencoesRequest;
use App\Http\Requests\ReagendarManutencaoRequest;
use App\Models\Fornecedor;
use App\Models\Manutencao;
use App\Models\Responsavel;
use App\Models\TipoEquipamento;
use App\Models\User;
use App\Services\ExportacaoManutencoes;
use App\Services\FiltroManutencoes;
use App\Services\ManutencaoService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManutencaoController extends Controller
{
    public function __construct(private ManutencaoService $manutencoes, private FiltroManutencoes $filtro) {}

    /** Visão unificada: programadas e não programadas com filtros e totais do filtro no rodapé. */
    public function index(FiltrarManutencoesRequest $request): View
    {
        $filtros = $request->filtros(mesAtualPorPadrao: true);
        $consulta = $this->filtro->consulta($request->user(), $filtros);

        return view('manutencoes.index', [
            'filtros' => $filtros,
            'manutencoes' => (clone $consulta)
                ->with(['ativo.cidade', 'plano', 'fornecedor', 'anexos'])
                ->orderByRaw(FiltroManutencoes::DATA_EFETIVA)
                ->orderBy('manutencoes.id')
                ->paginate(25)
                ->withQueryString(),
            'totais' => $this->filtro->totais($consulta),
            ...$this->opcoesDosFiltros($request->user()),
        ]);
    }

    /** Calendário mensal (domingo a sábado) com os mesmos filtros, exceto o período. */
    public function calendario(FiltrarManutencoesRequest $request): View
    {
        $mes = CarbonImmutable::createFromFormat('!Y-m', $request->validated('mes') ?? today()->format('Y-m'));
        $filtros = [...$request->filtros(), 'de' => $mes->toDateString(), 'ate' => $mes->endOfMonth()->toDateString()];
        $consulta = $this->filtro->consulta($request->user(), $filtros);

        $porDia = (clone $consulta)
            ->with(['ativo', 'plano'])
            ->orderByRaw(FiltroManutencoes::DATA_EFETIVA)
            ->orderBy('manutencoes.id')
            ->get()
            ->groupBy(fn (Manutencao $manutencao) => $manutencao->dataEfetiva()->toDateString());

        $dias = CarbonPeriod::create(
            $mes->startOfWeek(CarbonInterface::SUNDAY),
            $mes->endOfMonth()->endOfWeek(CarbonInterface::SATURDAY),
        )->map(fn ($dia) => CarbonImmutable::instance($dia));

        return view('manutencoes.calendario', [
            'mes' => $mes,
            'filtros' => $filtros,
            'semanas' => collect($dias)->chunk(7),
            'porDia' => $porDia,
            'totais' => $this->filtro->totais($consulta),
            ...$this->opcoesDosFiltros($request->user()),
        ]);
    }

    public function exportar(FiltrarManutencoesRequest $request, ExportacaoManutencoes $exportacao): StreamedResponse
    {
        return $exportacao->csv($this->filtro->consulta($request->user(), $request->filtros(mesAtualPorPadrao: true)));
    }

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

    private function opcoesDosFiltros(User $usuario): array
    {
        return [
            'opcoesStatus' => [
                StatusManutencao::Prevista->value => 'Prevista (todas)',
                'atrasada' => 'Atrasada',
                StatusManutencao::Realizada->value => 'Realizada',
                StatusManutencao::Cancelada->value => 'Cancelada',
            ],
            'opcoesTipo' => array_column(array_map(fn (TipoManutencao $t) => [$t->value, $t->rotulo()], TipoManutencao::cases()), 1, 0),
            'cidades' => $usuario->cidadesAcessiveis()->orderBy('nome')->get()->pluck('nome_uf', 'id'),
            'tiposEquipamento' => TipoEquipamento::orderBy('nome')->pluck('nome', 'id'),
            'responsaveis' => Responsavel::orderBy('nome')->pluck('nome', 'id'),
            'fornecedores' => Fornecedor::orderBy('nome')->pluck('nome', 'id'),
        ];
    }

    public function reagendar(ReagendarManutencaoRequest $request, Manutencao $manutencao): RedirectResponse
    {
        $this->manutencoes->reagendar($manutencao, $request->validated('nova_data'), $request->validated('motivo_reagendamento'));

        return redirect()->route('manutencoes.show', $manutencao)
            ->with('sucesso', 'Manutenção reagendada para '.data_br($request->validated('nova_data')).'.');
    }
}
