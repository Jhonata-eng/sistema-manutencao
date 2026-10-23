@extends('layouts.app')

@section('titulo', 'Tipos de equipamento')

@section('conteudo')
    <x-cabecalho titulo="Tipos de equipamento">
        <a href="{{ route('cadastros.tipos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo tipo</a>
    </x-cabecalho>

    <x-filtro-cadastro rota="cadastros.tipos.index" placeholder="Nome" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Tipo</th><th class="text-center">Ativos</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                @forelse ($tipos as $tipo)
                    <tr>
                        <td>{{ $tipo->nome }}</td>
                        <td class="text-center">{{ $tipo->ativos_count }}</td>
                        <td><x-situacao :ativo="$tipo->ativo" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('cadastros.tipos.edit', $tipo) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <x-botao-excluir :acao="route('cadastros.tipos.destroy', $tipo)" :confirmar="'Excluir o tipo '.$tipo->nome.'?'" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-body-secondary py-4">Nenhum tipo encontrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $tipos->links() }}</div>
@endsection
