@extends('layouts.app')

@section('titulo', 'Registro de auditoria')

@php
    $exibir = fn ($valor) => match (true) {
        $valor === null => '—',
        is_array($valor) => $valor === [] ? '(nenhum)' : implode(', ', $valor),
        default => (string) $valor,
    };
@endphp

@section('conteudo')
    <x-cabecalho :titulo="'Registro de auditoria #'.$registro->id">
        <a href="{{ url()->previous() === url()->current() ? route('auditoria.index') : url()->previous() }}" class="btn btn-outline-secondary">Voltar</a>
    </x-cabecalho>

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Data/hora</dt><dd class="col-sm-9">{{ data_hora_br($registro->created_at) }}</dd>
                <dt class="col-sm-3">Usuário</dt><dd class="col-sm-9">{{ $registro->usuario?->name ?? 'Sistema' }}</dd>
                <dt class="col-sm-3">Evento</dt><dd class="col-sm-9">{{ $registro->rotuloEvento() }}</dd>
                <dt class="col-sm-3">Registro</dt><dd class="col-sm-9">{{ $registro->rotuloTipo() }} #{{ $registro->auditavel_id }}</dd>
                @if ($registro->motivo)
                    <dt class="col-sm-3">Motivo</dt><dd class="col-sm-9">{{ $registro->motivo }}</dd>
                @endif
                <dt class="col-sm-3">IP</dt><dd class="col-sm-9">{{ $registro->ip ?? '—' }}</dd>
                <dt class="col-sm-3">Navegador</dt><dd class="col-sm-9 small text-body-secondary">{{ $registro->user_agent ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    @if ($registro->diferencas())
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Campo</th><th>Antes</th><th>Depois</th></tr></thead>
                    <tbody>
                    @foreach ($registro->diferencas() as $campo => [$antes, $depois])
                        <tr>
                            <td><code>{{ $campo }}</code></td>
                            <td class="text-break">{{ $exibir($antes) }}</td>
                            <td class="text-break">{{ $exibir($depois) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
