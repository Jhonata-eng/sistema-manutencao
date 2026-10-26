@extends('layouts.app')

@section('titulo', 'Calendário de manutenções')

@php
    $filtrosLink = request()->except(['mes', 'page']);
    $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $maximoPorDia = 4;
@endphp

@section('conteudo')
    <x-cabecalho titulo="Calendário de manutenções">
        <a href="{{ route('manutencoes.index', $filtrosLink + ['mes' => $mes->format('Y-m')]) }}" class="btn btn-outline-secondary"><i class="bi bi-list-ul me-1"></i>Lista</a>
    </x-cabecalho>

    @include('manutencoes._filtros', ['acao' => route('manutencoes.calendario'), 'periodo' => 'mes'])

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <a href="{{ route('manutencoes.calendario', $filtrosLink + ['mes' => $mes->subMonthNoOverflow()->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary" title="Mês anterior"><i class="bi bi-chevron-left"></i></a>
            <span class="fw-semibold">{{ ucfirst($mes->translatedFormat('F \d\e Y')) }}</span>
            <a href="{{ route('manutencoes.calendario', $filtrosLink + ['mes' => $mes->addMonthNoOverflow()->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary" title="Próximo mês"><i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered calendario mb-0">
                <thead><tr>@foreach ($diasSemana as $dia)<th class="text-center small">{{ $dia }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach ($semanas as $semana)
                    <tr>
                        @foreach ($semana as $dia)
                            @php($doDia = $porDia->get($dia->toDateString(), collect()))
                            <td @class(['fora-do-mes' => ! $dia->isSameMonth($mes), 'hoje' => $dia->isToday()])>
                                <div class="small fw-semibold mb-1">{{ $dia->day }}</div>
                                @foreach ($doDia->take($maximoPorDia) as $manutencao)
                                    <a href="{{ route('manutencoes.show', $manutencao) }}"
                                       @class(['evento-calendario', 'estado-'.($manutencao->estaAtrasada() ? 'atrasada' : $manutencao->status->value)])
                                       title="{{ $manutencao->ativo->patrimonio }} · {{ $manutencao->servico() }} · {{ $manutencao->estaAtrasada() ? 'Atrasada' : $manutencao->status->rotulo() }}">
                                        <span class="fw-semibold">{{ $manutencao->ativo->patrimonio }}</span> {{ $manutencao->servico() }}
                                    </a>
                                @endforeach
                                @if ($doDia->count() > $maximoPorDia)
                                    <a class="small" href="{{ route('manutencoes.index', $filtrosLink + ['de' => $dia->toDateString(), 'ate' => $dia->toDateString()]) }}">
                                        + {{ $doDia->count() - $maximoPorDia }} mais
                                    </a>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-body-tertiary">
            <div class="d-flex flex-wrap gap-3 small mb-2">
                <span><span class="legenda-calendario estado-prevista"></span>Prevista</span>
                <span><span class="legenda-calendario estado-atrasada"></span>Atrasada</span>
                <span><span class="legenda-calendario estado-realizada"></span>Realizada</span>
                <span><span class="legenda-calendario estado-cancelada"></span>Cancelada</span>
            </div>
            <x-totais-manutencoes :totais="$totais" />
        </div>
    </div>
@endsection
