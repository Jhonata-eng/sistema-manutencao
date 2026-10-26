@extends('layouts.app')

@section('titulo', 'Manutenções')

@section('conteudo')
    <x-cabecalho titulo="Manutenções">
        <a href="{{ route('manutencoes.calendario', request()->except(['de', 'ate', 'page']) + ['mes' => isset($filtros['de']) ? substr($filtros['de'], 0, 7) : today()->format('Y-m')]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar3 me-1"></i>Calendário
        </a>
        @can('relatorios.exportar')
            <a href="{{ route('manutencoes.exportar', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-csv me-1"></i>Exportar CSV</a>
        @endcan
        @can('manutencoes.lancar_nao_programada')
            <a href="{{ route('manutencoes.nao-programada.create') }}" class="btn btn-primary"><i class="bi bi-wrench-adjustable me-1"></i>Lançar não programada</a>
        @endcan
    </x-cabecalho>

    @include('manutencoes._filtros', ['acao' => route('manutencoes.index')])

    <div class="card">
        <div class="card-header small text-body-secondary">
            {{ $manutencoes->total() }} {{ $manutencoes->total() === 1 ? 'manutenção' : 'manutenções' }}
            @if (isset($filtros['de']) || isset($filtros['ate']))
                · de {{ data_br($filtros['de'] ?? null) ?: 'sempre' }} até {{ data_br($filtros['ate'] ?? null) ?: 'hoje em diante' }}
            @else
                · todo o período
            @endif
            <span class="d-none d-md-inline">· por {{ match ($filtros['data'] ?? 'efetiva') {
                'prevista' => 'data prevista',
                'execucao' => 'data de execução',
                default => 'data de execução (realizadas) ou prevista (demais)',
            } }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Ativo</th>
                    <th>Serviço</th>
                    <th>Status</th>
                    <th>Fornecedor</th>
                    <th class="text-end">Previsto</th>
                    <th class="text-end">Real</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($manutencoes as $manutencao)
                    <tr>
                        <td class="text-nowrap">{{ data_br($manutencao->dataEfetiva()) }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('ativos.show', $manutencao->ativo) }}">{{ $manutencao->ativo->patrimonio }}</a>
                            <div class="small text-body-secondary">{{ $manutencao->ativo->cidade->nome_uf }}</div>
                        </td>
                        <td>
                            {{ $manutencao->servico() }}
                            <div class="small text-body-secondary">{{ $manutencao->tipo->rotulo() }}</div>
                        </td>
                        <td class="text-nowrap">
                            <x-status-manutencao :manutencao="$manutencao" />
                            @if ($manutencao->semComprovante())
                                <span class="badge text-bg-warning" title="Sem nota fiscal ou recibo">Sem comprovante</span>
                            @endif
                        </td>
                        <td class="small">{{ $manutencao->fornecedor?->nome ?? '—' }}</td>
                        <td class="text-end text-nowrap">{{ moeda($manutencao->valor_previsto) }}</td>
                        <td class="text-end text-nowrap">{{ moeda($manutencao->valor_real) }}</td>
                        <td class="text-end text-nowrap">
                            @can('registrarExecucao', $manutencao)
                                <a href="{{ route('manutencoes.execucao.create', $manutencao) }}" class="btn btn-sm btn-outline-primary" title="Registrar execução"><i class="bi bi-check2-circle"></i></a>
                            @endcan
                            <a href="{{ route('manutencoes.show', $manutencao) }}" class="btn btn-sm btn-outline-secondary" title="Detalhes"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-body-secondary py-4">Nenhuma manutenção encontrada com esses filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-body-tertiary">
            <x-totais-manutencoes :totais="$totais" />
        </div>
    </div>
    <div class="mt-3">{{ $manutencoes->links() }}</div>
@endsection
