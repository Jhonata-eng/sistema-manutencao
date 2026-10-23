@extends('layouts.app')

@section('titulo', 'Fornecedores')

@section('conteudo')
    <x-cabecalho titulo="Fornecedores e prestadores">
        <a href="{{ route('cadastros.fornecedores.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo fornecedor</a>
    </x-cabecalho>

    <x-filtro-cadastro rota="cadastros.fornecedores.index" placeholder="Nome, CPF/CNPJ ou e-mail" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Nome / razão social</th><th>CPF/CNPJ</th><th>Contato</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                @forelse ($fornecedores as $fornecedor)
                    <tr>
                        <td>{{ $fornecedor->nome }}</td>
                        <td class="text-nowrap">{{ $fornecedor->documento_formatado ?: '—' }}</td>
                        <td class="small">
                            {{ $fornecedor->email }}
                            @if ($fornecedor->email && $fornecedor->telefone)<br>@endif
                            {{ $fornecedor->telefone }}
                        </td>
                        <td><x-situacao :ativo="$fornecedor->ativo" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('cadastros.fornecedores.edit', $fornecedor) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <x-botao-excluir :acao="route('cadastros.fornecedores.destroy', $fornecedor)" :confirmar="'Excluir o fornecedor '.$fornecedor->nome.'?'" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-body-secondary py-4">Nenhum fornecedor encontrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $fornecedores->links() }}</div>
@endsection
