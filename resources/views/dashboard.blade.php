@extends('layouts.app')

@section('titulo', 'Painel')

@php
    $cards = $painel['cards'];
    $hoje = today();
    $mesAtual = $hoje->format('Y-m');
    $nomeMes = $hoje->translatedFormat('F');
    $lista = fn (array $extra) => route('manutencoes.index', [...$filtros, ...$extra]);
@endphp

@section('conteudo')
    <x-cabecalho titulo="Painel" />

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><x-selecao name="cidade" label="Cidade" :opcoes="$cidades" :value="$filtros['cidade'] ?? null" vazio="Todas" grupo="" /></div>
            <div class="col-md-3"><x-selecao name="tipo_equipamento" label="Tipo de equipamento" :opcoes="$tipos" :value="$filtros['tipo_equipamento'] ?? null" vazio="Todos" grupo="" /></div>
            <div class="col-md-3"><x-selecao name="responsavel" label="Responsável" :opcoes="$responsaveis" :value="$filtros['responsavel'] ?? null" vazio="Todos" grupo="" /></div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <a href="{{ route('inicio') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-3">
        <div class="col">
            <x-indicador :rotulo="'Previsto em '.$nomeMes" :valor="moeda($cards['previsto_mes'])" detalhe="Programadas do mês, sem canceladas"
                         :link="$lista(['mes' => $mesAtual, 'data' => 'prevista', 'tipo' => 'programada'])" />
        </div>
        <div class="col">
            <x-indicador :rotulo="'Realizado em '.$nomeMes" :valor="moeda($cards['realizado_mes'])" detalhe="Programadas e não programadas"
                         :link="$lista(['mes' => $mesAtual, 'status' => 'realizada'])" />
        </div>
        <div class="col">
            <x-indicador rotulo="Atrasadas" icone="exclamation-triangle" :valor="$cards['atrasadas']['quantidade']"
                         :detalhe="moeda($cards['atrasadas']['valor']).' previstos'" :link="$lista(['status' => 'atrasada'])" />
        </div>
        <div class="col">
            <x-indicador rotulo="Próximos 30 dias" icone="calendar-week" :valor="$cards['proximos_30_dias']['quantidade']"
                         :detalhe="moeda($cards['proximos_30_dias']['valor']).' previstos'"
                         :link="$lista(['status' => 'prevista', 'de' => $hoje->toDateString(), 'ate' => $hoje->copy()->addDays(30)->toDateString()])" />
        </div>
        <div class="col">
            <x-indicador rotulo="Ativos" :valor="$cards['ativos']" :detalhe="$cards['ativos_em_operacao'].' em operação'"
                         :link="route('ativos.index', array_filter(['cidade' => $filtros['cidade'] ?? null, 'tipo' => $filtros['tipo_equipamento'] ?? null, 'responsavel' => $filtros['responsavel'] ?? null]))" />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="fw-semibold">Gasto previsto nos próximos 12 meses</div>
                    <div class="small text-body-secondary">Manutenções pendentes, pelo mês da data prevista. Clique numa barra para ver as manutenções.</div>
                </div>
                <div class="card-body">
                    <div class="area-grafico"><canvas id="grafico-previsao" role="img" aria-label="Gráfico de barras do gasto previsto por mês"></canvas></div>
                    <details class="mt-2">
                        <summary class="small">Ver dados em tabela</summary>
                        <table class="table table-sm small mt-2 mb-0">
                            <thead><tr><th>Mês</th><th class="text-end">Previsto</th></tr></thead>
                            <tbody>
                            @foreach ($painel['previsao'] as $mes => $valor)
                                <tr><td>{{ \Carbon\CarbonImmutable::createFromFormat('!Y-m', $mes)->translatedFormat('M/Y') }}</td><td class="text-end">{{ moeda($valor) }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </details>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="fw-semibold">Realizado × previsto nos últimos 12 meses</div>
                    <div class="small text-body-secondary">Valor real pelo mês da execução; linha com o previsto das programadas do mês.</div>
                </div>
                <div class="card-body">
                    <div class="area-grafico"><canvas id="grafico-realizado" role="img" aria-label="Gráfico de barras empilhadas do gasto realizado, com linha do previsto"></canvas></div>
                    <details class="mt-2">
                        <summary class="small">Ver dados em tabela</summary>
                        <table class="table table-sm small mt-2 mb-0">
                            <thead><tr><th>Mês</th><th class="text-end">Programadas</th><th class="text-end">Não programadas</th><th class="text-end">Previsto</th></tr></thead>
                            <tbody>
                            @foreach ($painel['realizado']['programadas'] as $mes => $valor)
                                <tr>
                                    <td>{{ \Carbon\CarbonImmutable::createFromFormat('!Y-m', $mes)->translatedFormat('M/Y') }}</td>
                                    <td class="text-end">{{ moeda($valor) }}</td>
                                    <td class="text-end">{{ moeda($painel['realizado']['nao_programadas'][$mes]) }}</td>
                                    <td class="text-end">{{ moeda($painel['realizado']['previsto'][$mes]) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </details>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap align-items-end justify-content-between gap-2">
            <div>
                <div class="fw-semibold">Distribuição do gasto realizado</div>
                <div class="small text-body-secondary">De {{ data_br($distribuicaoDe) }} a {{ data_br($distribuicaoAte) }}</div>
            </div>
            <form method="GET" class="d-flex flex-wrap align-items-end gap-2">
                @foreach ($filtros as $chave => $valor)
                    <input type="hidden" name="{{ $chave }}" value="{{ $valor }}">
                @endforeach
                <x-campo-data name="dist_de" label="De" :value="$distribuicaoDe" grupo="" class="form-control-sm" />
                <x-campo-data name="dist_ate" label="Até" :value="$distribuicaoAte" grupo="" class="form-control-sm" />
                <button class="btn btn-sm btn-outline-primary">Aplicar</button>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-4">
                @foreach (['tipo' => ['Por tipo de equipamento', 'grafico-por-tipo'], 'cidade' => ['Por cidade', 'grafico-por-cidade']] as $chave => [$titulo, $id])
                    <div class="col-lg-6">
                        <div class="small fw-semibold mb-2">{{ $titulo }}</div>
                        @if ($painel['distribuicao'][$chave]->isEmpty())
                            <p class="text-body-secondary small">Nenhum gasto realizado no período.</p>
                        @else
                            <div class="position-relative"><canvas id="{{ $id }}" role="img" aria-label="Gráfico de barras {{ mb_strtolower($titulo) }}"></canvas></div>
                            <details class="mt-2">
                                <summary class="small">Ver dados em tabela</summary>
                                <table class="table table-sm small mt-2 mb-0">
                                    <tbody>
                                    @foreach ($painel['distribuicao'][$chave] as $linha)
                                        <tr><td>{{ $linha->rotulo }}</td><td class="text-end">{{ moeda($linha->total) }}</td></tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach (['atrasadas' => ['Atrasadas', ['status' => 'atrasada'], 'Nenhuma manutenção atrasada.'], 'proximas' => ['Próximas (30 dias)', ['status' => 'prevista', 'de' => $hoje->toDateString(), 'ate' => $hoje->copy()->addDays(30)->toDateString()], 'Nenhuma manutenção prevista nos próximos 30 dias.']] as $chave => [$titulo, $filtroLista, $vazio])
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            @if ($chave === 'atrasadas')<i class="bi bi-exclamation-triangle me-1"></i>@endif{{ $titulo }}
                        </span>
                        <a href="{{ $lista($filtroLista) }}" class="small">Ver todas</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                            @forelse ($painel[$chave] as $manutencao)
                                <tr>
                                    <td class="text-nowrap">{{ data_br($manutencao->data_prevista) }}</td>
                                    <td>
                                        <a href="{{ route('manutencoes.show', $manutencao) }}">{{ $manutencao->servico() }}</a>
                                        <div class="small text-body-secondary">{{ $manutencao->ativo->patrimonio }} · {{ $manutencao->ativo->cidade->nome_uf }}</div>
                                    </td>
                                    <td class="text-end text-nowrap">{{ moeda($manutencao->valor_previsto) }}</td>
                                    <td class="text-end">
                                        @can('registrarExecucao', $manutencao)
                                            <a href="{{ route('manutencoes.execucao.create', $manutencao) }}" class="btn btn-sm btn-outline-primary">Registrar</a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-body-secondary py-4">{{ $vazio }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script type="application/json" id="dados-graficos">@json($graficos)</script>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/chart.js/4.6.1/chart.umd.min.js') }}"></script>
    <script src="{{ asset_versionado('js/graficos.js') }}"></script>
@endpush
