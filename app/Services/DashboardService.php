<?php

namespace App\Services;

use App\Enums\StatusAtivo;
use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Models\Ativo;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Números do painel. Tudo passa pelo FiltroManutencoes (escopo de cidades + filtros de cidade, tipo
 * de equipamento e responsável) e é somado no SQL; os valores saem como string decimal.
 *
 * Regras (docs/PROGRESSO.md): previsto do mês = programadas não canceladas pela data prevista;
 * realizado = valor real das realizadas pela data de execução; previsão dos próximos meses = só pendentes.
 */
class DashboardService
{
    public function __construct(private FiltroManutencoes $filtro) {}

    /** @param  array{cidade?: int, tipo_equipamento?: int, responsavel?: int}  $filtros */
    public function montar(User $usuario, array $filtros, CarbonImmutable $distribuicaoDe, CarbonImmutable $distribuicaoAte): array
    {
        $base = fn () => $this->filtro->consulta($usuario, $filtros);
        $hoje = CarbonImmutable::today();
        $inicioMes = $hoje->startOfMonth();

        return [
            'cards' => $this->cards($usuario, $filtros, $base, $hoje),
            'previsao' => $this->previsao($base, $inicioMes),
            'realizado' => $this->realizado($base, $inicioMes->subMonthsNoOverflow(11)),
            'distribuicao' => [
                'tipo' => $this->distribuicao($base, $distribuicaoDe, $distribuicaoAte, 'tipos_equipamento', 'ativos.tipo_equipamento_id', 'tipos_equipamento.nome'),
                'cidade' => $this->distribuicao($base, $distribuicaoDe, $distribuicaoAte, 'cidades', 'ativos.cidade_id', "CONCAT(cidades.nome, '/', cidades.uf)"),
            ],
            'atrasadas' => $base()->atrasadas()->with(['ativo.cidade', 'plano'])->orderBy('data_prevista')->limit(10)->get(),
            'proximas' => $base()->pendentes()
                ->whereBetween('manutencoes.data_prevista', [$hoje->toDateString(), $hoje->addDays(30)->toDateString()])
                ->with(['ativo.cidade', 'plano'])->orderBy('data_prevista')->limit(10)->get(),
        ];
    }

    private function cards(User $usuario, array $filtros, Closure $base, CarbonImmutable $hoje): array
    {
        $mes = [$hoje->startOfMonth()->toDateString(), $hoje->endOfMonth()->toDateString()];
        $proximos = [$hoje->toDateString(), $hoje->addDays(30)->toDateString()];
        $ativos = Ativo::visivelPara($usuario)->filtrar(array_filter([
            'cidade' => $filtros['cidade'] ?? null,
            'tipo' => $filtros['tipo_equipamento'] ?? null,
            'responsavel' => $filtros['responsavel'] ?? null,
        ]));

        return [
            'previsto_mes' => $this->somaPrevistoProgramado($base())->whereBetween('manutencoes.data_prevista', $mes)->value('total'),
            'realizado_mes' => $this->somaRealizado($base())->whereBetween('manutencoes.data_execucao', $mes)->value('total'),
            'atrasadas' => $this->quantidadeEValor($base()->atrasadas()),
            'proximos_30_dias' => $this->quantidadeEValor($base()->pendentes()->whereBetween('manutencoes.data_prevista', $proximos)),
            'ativos' => (clone $ativos)->count(),
            'ativos_em_operacao' => (clone $ativos)->where('status', StatusAtivo::EmOperacao)->count(),
        ];
    }

    /** 12 meses a partir do atual: soma do valor previsto das pendentes, pelo mês da data prevista. */
    private function previsao(Closure $base, CarbonImmutable $inicio): array
    {
        $fim = $inicio->addMonthsNoOverflow(11)->endOfMonth();
        $totais = $base()->pendentes()
            ->whereBetween('manutencoes.data_prevista', [$inicio->toDateString(), $fim->toDateString()])
            ->selectRaw("DATE_FORMAT(manutencoes.data_prevista, '%Y-%m') as mes, SUM(manutencoes.valor_previsto) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        return $this->serieMensal($inicio, $totais);
    }

    /**
     * 12 meses até o atual: valor real das realizadas pelo mês da execução, separado em programadas
     * e não programadas, e o previsto do mês (programadas não canceladas) para a linha de comparação.
     */
    private function realizado(Closure $base, CarbonImmutable $inicio): array
    {
        $periodo = [$inicio->toDateString(), $inicio->addMonthsNoOverflow(11)->endOfMonth()->toDateString()];
        $porTipo = fn (TipoManutencao $tipo) => $this->somaRealizado($base())
            ->where('manutencoes.tipo', $tipo)
            ->whereBetween('manutencoes.data_execucao', $periodo)
            ->selectRaw("DATE_FORMAT(manutencoes.data_execucao, '%Y-%m') as mes")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $previsto = $this->somaPrevistoProgramado($base())
            ->whereBetween('manutencoes.data_prevista', $periodo)
            ->selectRaw("DATE_FORMAT(manutencoes.data_prevista, '%Y-%m') as mes")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        return [
            'programadas' => $this->serieMensal($inicio, $porTipo(TipoManutencao::Programada)),
            'nao_programadas' => $this->serieMensal($inicio, $porTipo(TipoManutencao::NaoProgramada)),
            'previsto' => $this->serieMensal($inicio, $previsto),
        ];
    }

    /** Gasto realizado no período agrupado por uma tabela do ativo (tipo ou cidade), maior primeiro. */
    private function distribuicao(Closure $base, CarbonImmutable $de, CarbonImmutable $ate, string $tabela, string $chave, string $rotulo): Collection
    {
        return $base()
            ->where('manutencoes.status', StatusManutencao::Realizada)
            ->whereBetween('manutencoes.data_execucao', [$de->toDateString(), $ate->toDateString()])
            ->join('ativos', 'ativos.id', '=', 'manutencoes.ativo_id')
            ->join($tabela, "{$tabela}.id", '=', $chave)
            ->toBase()
            ->selectRaw("{$tabela}.id as id, {$rotulo} as rotulo, SUM(manutencoes.valor_real) as total")
            ->groupBy("{$tabela}.id", 'rotulo')
            ->orderByDesc('total')
            ->get();
    }

    private function somaPrevistoProgramado(Builder $consulta): Builder
    {
        return $consulta
            ->where('manutencoes.tipo', TipoManutencao::Programada)
            ->where('manutencoes.status', '!=', StatusManutencao::Cancelada)
            ->selectRaw('COALESCE(SUM(manutencoes.valor_previsto), 0) as total');
    }

    private function somaRealizado(Builder $consulta): Builder
    {
        return $consulta
            ->where('manutencoes.status', StatusManutencao::Realizada)
            ->selectRaw('COALESCE(SUM(manutencoes.valor_real), 0) as total');
    }

    /** @return array{quantidade: int, valor: string} */
    private function quantidadeEValor(Builder $consulta): array
    {
        $linha = $consulta->toBase()->selectRaw('COUNT(*) as quantidade, COALESCE(SUM(manutencoes.valor_previsto), 0) as valor')->first();

        return ['quantidade' => (int) $linha->quantidade, 'valor' => (string) $linha->valor];
    }

    /**
     * 12 meses a partir de $inicio, com "0.00" nos meses sem valor.
     *
     * @return array<string, string> "AAAA-MM" => total
     */
    private function serieMensal(CarbonImmutable $inicio, Collection $totais): array
    {
        $serie = [];
        for ($i = 0; $i < 12; $i++) {
            $mes = $inicio->addMonthsNoOverflow($i)->format('Y-m');
            $serie[$mes] = (string) ($totais[$mes] ?? '0.00');
        }

        return $serie;
    }
}
