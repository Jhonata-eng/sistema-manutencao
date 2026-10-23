@extends('layouts.app')

@section('titulo', 'Cidades')

@section('conteudo')
    <x-cabecalho titulo="Cidades">
        <a href="{{ route('cadastros.cidades.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nova cidade</a>
    </x-cabecalho>

    <x-filtro-cadastro rota="cadastros.cidades.index" placeholder="Nome ou UF" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Cidade</th><th>UF</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                @forelse ($cidades as $cidade)
                    <tr>
                        <td>{{ $cidade->nome }}</td>
                        <td>{{ $cidade->uf }}</td>
                        <td><x-situacao :ativo="$cidade->ativo" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('cadastros.cidades.edit', $cidade) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <x-botao-excluir :acao="route('cadastros.cidades.destroy', $cidade)" :confirmar="'Excluir a cidade '.$cidade->nome_uf.'?'" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-body-secondary py-4">Nenhuma cidade encontrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $cidades->links() }}</div>
@endsection
