@extends('layouts.app')

@section('titulo', 'Auditoria')

@section('conteudo')
    <x-cabecalho titulo="Auditoria" />

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2"><x-campo name="de" label="De" type="date" :value="request('de')" grupo="" /></div>
            <div class="col-6 col-md-2"><x-campo name="ate" label="Até" type="date" :value="request('ate')" grupo="" /></div>
            <div class="col-md-3"><x-selecao name="usuario" label="Usuário" :opcoes="$usuarios" :value="request('usuario')" vazio="Todos" grupo="" /></div>
            <div class="col-6 col-md-2"><x-selecao name="evento" label="Evento" :opcoes="\App\Models\Auditoria::EVENTOS" :value="request('evento')" vazio="Todos" grupo="" /></div>
            <div class="col-6 col-md-2">
                <x-selecao name="tipo" label="Registro" :opcoes="array_map(fn ($tipo) => $tipo[1], \App\Models\Auditoria::TIPOS)" :value="request('tipo')" vazio="Todos" grupo="" />
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" title="Filtrar"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('auditoria.index') }}" class="btn btn-outline-secondary" title="Limpar filtros"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Data/hora</th>
                    <th>Usuário</th>
                    <th>Evento</th>
                    <th>Registro</th>
                    <th>IP</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td class="text-nowrap">{{ data_hora_br($registro->created_at) }}</td>
                        <td>{{ $registro->usuario?->name ?? 'Sistema' }}</td>
                        <td>{{ $registro->rotuloEvento() }}</td>
                        <td>{{ $registro->rotuloTipo() }} #{{ $registro->auditavel_id }}</td>
                        <td class="small text-body-secondary">{{ $registro->ip }}</td>
                        <td class="text-end"><a href="{{ route('auditoria.show', $registro) }}" class="btn btn-sm btn-outline-primary">Detalhes</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">Nenhum registro encontrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $registros->links() }}</div>
@endsection
