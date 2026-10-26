@extends('layouts.app')

@section('titulo', 'Plano: '.$plano->descricao)

@section('conteudo')
    <x-cabecalho :titulo="$plano->descricao">
        <a href="{{ route('ativos.show', $plano->equipamento) }}" class="btn btn-outline-secondary">Voltar ao ativo</a>
        @can('update', $plano)
            <a href="{{ route('planos.edit', $plano) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Editar</a>
        @endcan
        @can('delete', $plano)
            <x-botao-excluir :acao="route('planos.destroy', $plano)" rotulo="Excluir"
                             :confirmar="'Excluir o plano '.$plano->descricao.' e suas manutenções previstas?'" />
        @endcan
    </x-cabecalho>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="fs-6">
                    Ativo <a href="{{ route('ativos.show', $plano->equipamento) }}">{{ $plano->equipamento->patrimonio }}</a>
                    · {{ $plano->equipamento->marca }} {{ $plano->equipamento->modelo }} · {{ $plano->equipamento->cidade->nome_uf }}
                </span>
                <x-situacao :ativo="$plano->ativo" />
            </div>
            <dl class="row mb-0">
                <dt class="col-sm-4 col-lg-3">Periodicidade</dt><dd class="col-sm-8 col-lg-9">{{ $plano->rotuloPeriodicidade() }}</dd>
                <dt class="col-sm-4 col-lg-3">Primeira ocorrência</dt><dd class="col-sm-8 col-lg-9">{{ data_br($plano->data_inicio) }}</dd>
                <dt class="col-sm-4 col-lg-3">Término</dt><dd class="col-sm-8 col-lg-9">{{ data_br($plano->data_termino) ?: 'Sem término' }}</dd>
                <dt class="col-sm-4 col-lg-3">Valor médio estimado</dt><dd class="col-sm-8 col-lg-9">{{ moeda($plano->valor_estimado) }}</dd>
                <dt class="col-sm-4 col-lg-3">Média real das execuções</dt>
                <dd class="col-sm-8 col-lg-9">
                    @if ($mediaReal === null)
                        <span class="text-body-secondary">Sem execuções ainda</span>
                    @else
                        {{ moeda($mediaReal) }}
                        @if ($mediaReal !== $plano->valor_estimado)
                            @can('update', $plano)
                                <form method="POST" action="{{ route('planos.atualizar-valor-medio', $plano) }}" class="d-inline ms-2"
                                      data-confirmar="Usar {{ moeda($mediaReal) }} como valor estimado deste plano e das manutenções pendentes futuras?">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary">Atualizar valor médio com base no histórico</button>
                                </form>
                            @endcan
                        @endif
                    @endif
                </dd>
                <dt class="col-sm-4 col-lg-3">Fornecedor padrão</dt><dd class="col-sm-8 col-lg-9">{{ $plano->fornecedor?->nome ?? '—' }}</dd>
                @if ($plano->observacoes)
                    <dt class="col-sm-4 col-lg-3">Observações</dt><dd class="col-sm-8 col-lg-9" style="white-space: pre-line">{{ $plano->observacoes }}</dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold">Manutenções do plano</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Data prevista</th>
                    <th>Status</th>
                    <th class="text-end">Valor previsto</th>
                    <th>Execução</th>
                    <th class="text-end">Valor real</th>
                    <th>Observação</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($ocorrencias as $ocorrencia)
                    <tr>
                        <td class="text-nowrap">
                            {{ data_br($ocorrencia->data_prevista) }}
                            @if ($ocorrencia->data_referencia && ! $ocorrencia->data_prevista->equalTo($ocorrencia->data_referencia))
                                <span class="small text-body-secondary" title="Data original do calendário">(era {{ data_br($ocorrencia->data_referencia) }})</span>
                            @endif
                        </td>
                        <td><x-status-manutencao :manutencao="$ocorrencia" /></td>
                        <td class="text-end text-nowrap">{{ moeda($ocorrencia->valor_previsto) }}</td>
                        <td class="text-nowrap">{{ data_br($ocorrencia->data_execucao) ?: '—' }}</td>
                        <td class="text-end text-nowrap">{{ moeda($ocorrencia->valor_real) }}</td>
                        <td class="small text-body-secondary">{{ $ocorrencia->motivo_cancelamento }}</td>
                        <td class="text-end text-nowrap">
                            @can('registrarExecucao', $ocorrencia)
                                <a href="{{ route('manutencoes.execucao.create', $ocorrencia) }}" class="btn btn-sm btn-outline-primary" title="Registrar execução"><i class="bi bi-check2-circle"></i></a>
                            @endcan
                            @can('view', $ocorrencia)
                                <a href="{{ route('manutencoes.show', $ocorrencia) }}" class="btn btn-sm btn-outline-secondary" title="Detalhes"><i class="bi bi-eye"></i></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-body-secondary py-4">Nenhuma manutenção gerada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $ocorrencias->links() }}</div>
@endsection
