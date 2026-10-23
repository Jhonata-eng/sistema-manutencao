@extends('layouts.app')

@section('titulo', 'Ativos')

@section('conteudo')
    <x-cabecalho titulo="Ativos">
        @can('create', \App\Models\Ativo::class)
            <a href="{{ route('ativos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo ativo</a>
        @endcan
    </x-cabecalho>

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-xl-3">
                <x-campo name="busca" label="Buscar" :value="request('busca')" placeholder="Patrimônio, marca, modelo, série, local" grupo="" />
            </div>
            <div class="col-6 col-md-4 col-xl-2"><x-selecao name="cidade" label="Cidade" :opcoes="$cidades" :value="request('cidade')" vazio="Todas" grupo="" /></div>
            <div class="col-6 col-md-4 col-xl-2"><x-selecao name="tipo" label="Tipo" :opcoes="$tipos" :value="request('tipo')" vazio="Todos" grupo="" /></div>
            <div class="col-6 col-md-4 col-xl-2"><x-selecao name="responsavel" label="Responsável" :opcoes="$responsaveis" :value="request('responsavel')" vazio="Todos" grupo="" /></div>
            <div class="col-6 col-md-4 col-xl-2">
                <x-selecao name="status" label="Status" :opcoes="\App\Enums\StatusAtivo::opcoes()" :value="request('status')" vazio="Todos" grupo="" />
            </div>
            <div class="col-md-4 col-xl-1 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" title="Filtrar"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('ativos.index') }}" class="btn btn-outline-secondary" title="Limpar filtros"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Patrimônio</th>
                    <th>Tipo</th>
                    <th>Marca / modelo</th>
                    <th>Cidade</th>
                    <th>Localização</th>
                    <th>Responsável</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($ativos as $ativo)
                    <tr>
                        <td class="fw-semibold text-nowrap"><a href="{{ route('ativos.show', $ativo) }}">{{ $ativo->patrimonio }}</a></td>
                        <td>{{ $ativo->tipo->nome }}</td>
                        <td>{{ $ativo->marca }} {{ $ativo->modelo }}</td>
                        <td class="text-nowrap">{{ $ativo->cidade->nome_uf }}</td>
                        <td class="small">{{ $ativo->localizacao }}</td>
                        <td>{{ $ativo->responsavel?->nome ?? '—' }}</td>
                        <td><x-status-ativo :status="$ativo->status" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('ativos.show', $ativo) }}" class="btn btn-sm btn-outline-secondary" title="Detalhes"><i class="bi bi-eye"></i></a>
                            @can('update', $ativo)
                                <a href="{{ route('ativos.edit', $ativo) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-body-secondary py-4">Nenhum ativo encontrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $ativos->links() }}</div>
@endsection
