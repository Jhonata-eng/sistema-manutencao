@extends('layouts.app')

@section('titulo', 'Manutenção #'.$manutencao->id)

@section('conteudo')
    <x-cabecalho :titulo="'Manutenção #'.$manutencao->id">
        <a href="{{ route('ativos.show', $manutencao->ativo) }}" class="btn btn-outline-secondary">Ver ativo</a>
        @can('registrarExecucao', $manutencao)
            <a href="{{ route('manutencoes.execucao.create', $manutencao) }}" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Registrar execução</a>
        @endcan
        @can('corrigirValor', $manutencao)
            <a href="{{ route('manutencoes.correcao-valor.create', $manutencao) }}" class="btn btn-outline-primary"><i class="bi bi-currency-exchange me-1"></i>Corrigir valor</a>
        @endcan
    </x-cabecalho>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="fs-5 fw-semibold">{{ $manutencao->servico() }}</span>
                <x-status-manutencao :manutencao="$manutencao" />
                <span class="badge text-bg-light border">{{ $manutencao->tipo->rotulo() }}</span>
                @if ($manutencao->semComprovante())
                    <span class="badge text-bg-warning"><i class="bi bi-exclamation-triangle me-1"></i>Sem comprovante</span>
                @endif
            </div>
            <dl class="row mb-0">
                <dt class="col-sm-4 col-lg-3">Ativo</dt>
                <dd class="col-sm-8 col-lg-9">
                    <a href="{{ route('ativos.show', $manutencao->ativo) }}">{{ $manutencao->ativo->patrimonio }}</a>
                    · {{ $manutencao->ativo->marca }} {{ $manutencao->ativo->modelo }} · {{ $manutencao->ativo->cidade->nome_uf }}
                    @if ($manutencao->ativo->trashed()) <span class="text-body-secondary">(excluído)</span> @endif
                </dd>
                <dt class="col-sm-4 col-lg-3">Plano</dt>
                <dd class="col-sm-8 col-lg-9">
                    @if ($manutencao->plano)
                        <a href="{{ route('planos.show', $manutencao->plano) }}">{{ $manutencao->plano->descricao }}</a> ({{ $manutencao->plano->rotuloPeriodicidade() }})
                    @else
                        —
                    @endif
                </dd>
                @if ($manutencao->data_prevista)
                    <dt class="col-sm-4 col-lg-3">Data prevista</dt>
                    <dd class="col-sm-8 col-lg-9">
                        {{ data_br($manutencao->data_prevista) }}
                        @if ($manutencao->data_referencia && ! $manutencao->data_prevista->equalTo($manutencao->data_referencia))
                            <span class="text-body-secondary">(reagendada; data original {{ data_br($manutencao->data_referencia) }})</span>
                        @endif
                    </dd>
                @endif
                @if ($manutencao->valor_previsto !== null)
                    <dt class="col-sm-4 col-lg-3">Valor previsto</dt><dd class="col-sm-8 col-lg-9">{{ moeda($manutencao->valor_previsto) }}</dd>
                @endif
                @if ($manutencao->data_execucao)
                    <dt class="col-sm-4 col-lg-3">Data de execução</dt><dd class="col-sm-8 col-lg-9">{{ data_br($manutencao->data_execucao) }}</dd>
                    <dt class="col-sm-4 col-lg-3">Valor real</dt><dd class="col-sm-8 col-lg-9 fw-semibold">{{ moeda($manutencao->valor_real) }}</dd>
                    <dt class="col-sm-4 col-lg-3">Fornecedor</dt><dd class="col-sm-8 col-lg-9">{{ $manutencao->fornecedor?->nome ?? '—' }}</dd>
                    <dt class="col-sm-4 col-lg-3">Registrado por</dt><dd class="col-sm-8 col-lg-9">{{ $manutencao->registradoPor?->name ?? '—' }}</dd>
                @endif
                @if ($manutencao->observacoes)
                    <dt class="col-sm-4 col-lg-3">Observações</dt><dd class="col-sm-8 col-lg-9" style="white-space: pre-line">{{ $manutencao->observacoes }}</dd>
                @endif
                @if ($manutencao->cancelado_em || $manutencao->motivo_cancelamento)
                    <dt class="col-sm-4 col-lg-3">Cancelamento</dt>
                    <dd class="col-sm-8 col-lg-9">
                        {{ $manutencao->motivo_cancelamento }}
                        <span class="text-body-secondary">— {{ $manutencao->canceladoPor?->name ?? 'Sistema' }}, {{ data_hora_br($manutencao->cancelado_em) }}</span>
                    </dd>
                @endif
            </dl>
        </div>
    </div>

    @can('cancelarReagendar', $manutencao)
        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <form method="POST" action="{{ route('manutencoes.reagendar', $manutencao) }}" class="card h-100">
                    @csrf
                    <div class="card-header fw-semibold">Reagendar</div>
                    <div class="card-body">
                        <x-campo-data name="nova_data" label="Nova data" :min="today()->format('Y-m-d')" required />
                        <x-campo name="motivo_reagendamento" label="Justificativa" maxlength="1000" required grupo="" />
                    </div>
                    <div class="card-footer"><button class="btn btn-outline-primary">Reagendar</button></div>
                </form>
            </div>
            <div class="col-lg-6">
                <form method="POST" action="{{ route('manutencoes.cancelar', $manutencao) }}" class="card h-100" data-confirmar="Cancelar esta manutenção? Ela não será recriada pelo plano.">
                    @csrf
                    <div class="card-header fw-semibold">Cancelar</div>
                    <div class="card-body">
                        <x-campo name="motivo_cancelamento" label="Justificativa" maxlength="1000" required grupo="" />
                    </div>
                    <div class="card-footer"><button class="btn btn-outline-danger">Cancelar manutenção</button></div>
                </form>
            </div>
        </div>
    @endcan

    @if ($manutencao->status === \App\Enums\StatusManutencao::Realizada)
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold">Anexos</span>
                @can('enviarAnexo', $manutencao)
                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#enviar-anexo">
                        <i class="bi bi-paperclip me-1"></i>Enviar anexo
                    </button>
                @endcan
            </div>
            @can('enviarAnexo', $manutencao)
                <div @class(['collapse', 'show' => $errors->hasAny(['anexo_arquivo', 'anexo_tipo', 'anexo_numero_nota', 'anexo_data_emissao', 'anexo_valor_nota'])]) id="enviar-anexo">
                    <form method="POST" action="{{ route('manutencoes.anexos.store', $manutencao) }}" enctype="multipart/form-data" class="card-body border-bottom">
                        @csrf
                        <x-campos-anexo :tipos="\App\Enums\TipoAnexo::opcoes()" titulo="Novo anexo" :obrigatorio="true" />
                        <button class="btn btn-primary">Enviar</button>
                    </form>
                </div>
            @endcan
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Tipo</th><th>Arquivo</th><th>Nº da nota</th><th>Emissão</th><th class="text-end">Valor da nota</th><th>Enviado por</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($manutencao->anexos as $anexo)
                        <tr>
                            <td>{{ $anexo->tipo->rotulo() }}</td>
                            <td><a href="{{ route('anexos.show', $anexo) }}" target="_blank">{{ $anexo->nome_original }}</a></td>
                            <td>{{ $anexo->numero_nota ?? '—' }}</td>
                            <td>{{ data_br($anexo->data_emissao) ?: '—' }}</td>
                            <td class="text-end text-nowrap">{{ moeda($anexo->valor_nota) }}</td>
                            <td class="small">{{ $anexo->enviadoPor->name }}<br><span class="text-body-secondary">{{ data_hora_br($anexo->created_at) }}</span></td>
                            <td class="text-end">
                                @can('delete', $anexo)
                                    <x-botao-excluir :acao="route('anexos.destroy', $anexo)" :confirmar="'Excluir o anexo '.$anexo->nome_original.'?'" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-3">Nenhum anexo.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($manutencao->correcoesValor->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header fw-semibold">Correções de valor</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Data</th><th>Usuário</th><th class="text-end">Valor anterior</th><th class="text-end">Valor novo</th><th>Motivo</th><th>Comprovante</th></tr></thead>
                    <tbody>
                    @foreach ($manutencao->correcoesValor->sortByDesc('id') as $correcao)
                        <tr>
                            <td class="text-nowrap">{{ data_hora_br($correcao->created_at) }}</td>
                            <td>{{ $correcao->usuario->name }}</td>
                            <td class="text-end text-nowrap">{{ moeda($correcao->valor_anterior) }}</td>
                            <td class="text-end text-nowrap fw-semibold">{{ moeda($correcao->valor_novo) }}</td>
                            <td>{{ $correcao->motivo }}</td>
                            <td><a href="{{ route('anexos.show', $correcao->anexo) }}" target="_blank">{{ $correcao->anexo->descricao() }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header fw-semibold">Histórico</div>
        <ul class="list-group list-group-flush">
            @forelse ($eventos as $registro)
                <li class="list-group-item">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <span class="fw-semibold">{{ $registro->rotuloEvento() }}</span>
                        <span class="small text-body-secondary">{{ data_hora_br($registro->created_at) }} · {{ $registro->usuario?->name ?? 'Sistema' }}</span>
                    </div>
                    @switch($registro->evento)
                        @case('reagendada')
                            <div class="small">{{ data_br($registro->antes['data_prevista']) }} → {{ data_br($registro->depois['data_prevista']) }}</div>
                            @break
                        @case('valor_corrigido')
                            <div class="small">{{ moeda($registro->antes['valor_real']) }} → {{ moeda($registro->depois['valor_real']) }} · comprovante: {{ $registro->depois['comprovante'] }}</div>
                            @break
                        @case('executada')
                        @case('nao_programada_lancada')
                            <div class="small">Executada em {{ data_br($registro->depois['data_execucao']) }} por {{ moeda($registro->depois['valor_real']) }}</div>
                            @break
                        @case('anexo_enviado')
                            <div class="small">{{ $registro->depois['anexo'] }}</div>
                            @break
                        @case('anexo_excluido')
                            <div class="small">{{ $registro->antes['anexo'] }}</div>
                            @break
                    @endswitch
                    @if ($registro->motivo)
                        <div class="small text-body-secondary">Justificativa: {{ $registro->motivo }}</div>
                    @endif
                </li>
            @empty
                <li class="list-group-item text-body-secondary">Sem eventos registrados.</li>
            @endforelse
        </ul>
    </div>
@endsection
