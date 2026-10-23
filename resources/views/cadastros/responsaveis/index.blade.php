@extends('layouts.app')

@section('titulo', 'Responsáveis')

@section('conteudo')
    <x-cabecalho titulo="Responsáveis">
        <a href="{{ route('cadastros.responsaveis.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo responsável</a>
    </x-cabecalho>

    <x-filtro-cadastro rota="cadastros.responsaveis.index" placeholder="Nome, e-mail ou setor" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Nome</th><th>Setor</th><th>Contato</th><th>Usuário do sistema</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                @forelse ($responsaveis as $responsavel)
                    <tr>
                        <td>{{ $responsavel->nome }}</td>
                        <td>{{ $responsavel->setor ?? '—' }}</td>
                        <td class="small">
                            {{ $responsavel->email }}
                            @if ($responsavel->email && $responsavel->telefone)<br>@endif
                            {{ $responsavel->telefone }}
                        </td>
                        <td>{{ $responsavel->usuario?->name ?? '—' }}</td>
                        <td><x-situacao :ativo="$responsavel->ativo" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('cadastros.responsaveis.edit', $responsavel) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <x-botao-excluir :acao="route('cadastros.responsaveis.destroy', $responsavel)" :confirmar="'Excluir o responsável '.$responsavel->nome.'?'" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">Nenhum responsável encontrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $responsaveis->links() }}</div>
@endsection
