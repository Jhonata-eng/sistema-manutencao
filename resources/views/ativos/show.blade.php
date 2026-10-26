@extends('layouts.app')

@section('titulo', 'Ativo '.$ativo->patrimonio)

@section('conteudo')
    <x-cabecalho :titulo="'Ativo '.$ativo->patrimonio">
        <a href="{{ route('ativos.index') }}" class="btn btn-outline-secondary">Voltar</a>
        @can('update', $ativo)
            <a href="{{ route('ativos.edit', $ativo) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Editar</a>
        @endcan
        @can('delete', $ativo)
            <x-botao-excluir :acao="route('ativos.destroy', $ativo)" rotulo="Excluir"
                             :confirmar="'Excluir o ativo '.$ativo->patrimonio.'? O histórico continua nos relatórios.'
                                 .($pendentes ? ' Os planos serão inativados e '.$pendentes.' manutenção(ões) pendente(s) cancelada(s).' : '')" />
        @endcan
    </x-cabecalho>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="fs-5 fw-semibold">{{ $ativo->tipo->nome }} · {{ $ativo->marca }} {{ $ativo->modelo }}</span>
                        <x-status-ativo :status="$ativo->status" />
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Patrimônio</dt><dd class="col-sm-8">{{ $ativo->patrimonio }}</dd>
                        <dt class="col-sm-4">Número de série</dt><dd class="col-sm-8">{{ $ativo->numero_serie ?? '—' }}</dd>
                        <dt class="col-sm-4">Capacidade</dt><dd class="col-sm-8">{{ $ativo->capacidade ?? '—' }}</dd>
                        <dt class="col-sm-4">Ano de fabricação</dt><dd class="col-sm-8">{{ $ativo->ano_fabricacao ?? '—' }}</dd>
                        <dt class="col-sm-4">Data de aquisição</dt><dd class="col-sm-8">{{ data_br($ativo->data_aquisicao) ?: '—' }}</dd>
                        <dt class="col-sm-4">Cidade</dt><dd class="col-sm-8">{{ $ativo->cidade->nome_uf }}</dd>
                        <dt class="col-sm-4">Localização</dt><dd class="col-sm-8">{{ $ativo->localizacao }}</dd>
                        <dt class="col-sm-4">Responsável</dt><dd class="col-sm-8">{{ $ativo->responsavel?->nome ?? '—' }}</dd>
                        @if ($ativo->observacoes)
                            <dt class="col-sm-4">Observações</dt><dd class="col-sm-8" style="white-space: pre-line">{{ $ativo->observacoes }}</dd>
                        @endif
                        <dt class="col-sm-4">Cadastrado por</dt>
                        <dd class="col-sm-8">{{ $ativo->criador->name }} em {{ data_hora_br($ativo->created_at) }}</dd>
                    </dl>
                </div>
                @if ($ativo->foto_path)
                    <div class="col-lg-auto">
                        <a href="{{ route('ativos.foto', $ativo) }}" target="_blank">
                            <img src="{{ route('ativos.foto', $ativo) }}" alt="Foto do ativo {{ $ativo->patrimonio }}" class="img-fluid rounded border" style="max-height: 260px">
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('planos.visualizar')
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold">Planos de manutenção</span>
                @can('create', [\App\Models\PlanoManutencao::class, $ativo])
                    @if ($ativo->emOperacao())
                        <a href="{{ route('ativos.planos.create', $ativo) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo plano</a>
                    @endif
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Periodicidade</th>
                        <th>Próxima</th>
                        <th class="text-end">Valor estimado</th>
                        <th>Situação</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($planos as $plano)
                        <tr>
                            <td><a href="{{ route('planos.show', $plano) }}">{{ $plano->descricao }}</a></td>
                            <td>{{ $plano->rotuloPeriodicidade() }}</td>
                            <td class="text-nowrap">{{ data_br($plano->proxima_ocorrencia) ?: '—' }}</td>
                            <td class="text-end text-nowrap">{{ moeda($plano->valor_estimado) }}</td>
                            <td><x-situacao :ativo="$plano->ativo" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-body-secondary py-4">
                                Nenhum plano cadastrado.
                                @unless ($ativo->emOperacao()) Planos só podem ser criados para ativos em operação. @endunless
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endcan

    @can('manutencoes.visualizar')
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold">Histórico de manutenções <span class="fw-normal small text-body-secondary">(as próximas estão em cada plano)</span></span>
                <span class="ms-auto">Total gasto: <strong>{{ moeda($totalGasto) }}</strong></span>
                @can('lancarNaoProgramada', [\App\Models\Manutencao::class, $ativo])
                    <a href="{{ route('manutencoes.nao-programada.create', ['ativo' => $ativo->id]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-wrench-adjustable me-1"></i>Lançar não programada
                    </a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Serviço</th>
                        <th>Status</th>
                        <th class="text-end">Previsto</th>
                        <th class="text-end">Real</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($historico as $manutencao)
                        <tr>
                            <td class="text-nowrap">{{ data_br($manutencao->data_execucao ?? $manutencao->data_prevista) }}</td>
                            <td class="small">{{ $manutencao->tipo->rotulo() }}</td>
                            <td>{{ $manutencao->servico() }}</td>
                            <td class="text-nowrap">
                                <x-status-manutencao :manutencao="$manutencao" />
                                @if ($manutencao->semComprovante())
                                    <span class="badge text-bg-warning" title="Sem nota fiscal ou recibo">Sem comprovante</span>
                                @endif
                            </td>
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
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">Nenhuma manutenção realizada, cancelada ou atrasada.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mb-3">{{ $historico->links() }}</div>
    @endcan
@endsection
