<?php

namespace App\Http\Controllers;

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Http\Requests\FiltrarManutencoesRequest;
use App\Models\Responsavel;
use App\Models\TipoEquipamento;
use App\Services\DashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/** Página inicial: o painel para quem tem dashboard.visualizar; uma tela de boas-vindas para os demais. */
class InicioController extends Controller
{
    /** Filtros do painel que valem para todos os elementos (e seguem no clique para a visão unificada). */
    private const FILTROS = ['cidade', 'tipo_equipamento', 'responsavel'];

    public function __invoke(FiltrarManutencoesRequest $request, DashboardService $painel): View
    {
        $usuario = $request->user();

        if (! $usuario->can('dashboard.visualizar')) {
            return view('inicio');
        }

        $dados = $request->validate([
            'dist_de' => ['nullable', 'date_format:Y-m-d'],
            'dist_ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dist_de'],
        ]);
        // Distribuição do gasto: por padrão, os últimos 12 meses (do 1º dia de 11 meses atrás até hoje).
        $distribuicaoDe = CarbonImmutable::parse($dados['dist_de'] ?? today()->startOfMonth()->subMonthsNoOverflow(11));
        $distribuicaoAte = CarbonImmutable::parse($dados['dist_ate'] ?? today());
        $filtros = Arr::only($request->filtros(), self::FILTROS);

        $dadosPainel = $painel->montar($usuario, $filtros, $distribuicaoDe, $distribuicaoAte);

        return view('dashboard', [
            'filtros' => $filtros,
            'painel' => $dadosPainel,
            'graficos' => $this->graficos($dadosPainel, $filtros, $distribuicaoDe, $distribuicaoAte),
            'distribuicaoDe' => $distribuicaoDe,
            'distribuicaoAte' => $distribuicaoAte,
            'cidades' => $usuario->cidadesAcessiveis()->orderBy('nome')->get()->pluck('nome_uf', 'id'),
            'tipos' => TipoEquipamento::orderBy('nome')->pluck('nome', 'id'),
            'responsaveis' => Responsavel::orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    /**
     * Séries para o Chart.js (números só para desenhar; os valores exatos ficam nas tabelas) e,
     * para cada barra, a URL da visão unificada com o mesmo recorte que a barra soma.
     */
    private function graficos(array $painel, array $filtros, CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $rotulo = fn (string $mes) => CarbonImmutable::createFromFormat('!Y-m', $mes)->translatedFormat('M/y');
        $doMes = fn (string $mes, array $extra) => route('manutencoes.index', [...$filtros, 'mes' => $mes, ...$extra]);
        $numeros = fn (array $serie) => array_map('floatval', array_values($serie));
        $periodo = ['de' => $de->toDateString(), 'ate' => $ate->toDateString(), 'status' => StatusManutencao::Realizada->value];
        $distribuicao = fn (string $chave, string $filtro) => [
            'rotulos' => $painel['distribuicao'][$chave]->pluck('rotulo')->all(),
            'valores' => $painel['distribuicao'][$chave]->pluck('total')->map(fn ($total) => (float) $total)->all(),
            'urls' => $painel['distribuicao'][$chave]->map(fn ($linha) => route('manutencoes.index', [...$filtros, ...$periodo, $filtro => $linha->id]))->all(),
        ];
        $mesesRealizado = array_keys($painel['realizado']['programadas']);
        $realizada = StatusManutencao::Realizada->value;

        return [
            'previsao' => [
                'rotulos' => array_map($rotulo, array_keys($painel['previsao'])),
                'valores' => $numeros($painel['previsao']),
                'urls' => array_map(fn ($mes) => $doMes($mes, ['status' => StatusManutencao::Prevista->value]), array_keys($painel['previsao'])),
            ],
            'realizado' => [
                'rotulos' => array_map($rotulo, $mesesRealizado),
                'programadas' => $numeros($painel['realizado']['programadas']),
                'naoProgramadas' => $numeros($painel['realizado']['nao_programadas']),
                'previsto' => $numeros($painel['realizado']['previsto']),
                'urls' => [
                    array_map(fn ($mes) => $doMes($mes, ['status' => $realizada, 'tipo' => TipoManutencao::Programada->value]), $mesesRealizado),
                    array_map(fn ($mes) => $doMes($mes, ['status' => $realizada, 'tipo' => TipoManutencao::NaoProgramada->value]), $mesesRealizado),
                    array_map(fn ($mes) => $doMes($mes, ['data' => 'prevista', 'tipo' => TipoManutencao::Programada->value]), $mesesRealizado),
                ],
            ],
            'porTipo' => $distribuicao('tipo', 'tipo_equipamento'),
            'porCidade' => $distribuicao('cidade', 'cidade'),
        ];
    }
}
