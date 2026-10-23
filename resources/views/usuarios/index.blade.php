@extends('layouts.app')

@section('titulo', 'Usuários')

@section('conteudo')
    <x-cabecalho titulo="Usuários">
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo usuário</a>
    </x-cabecalho>

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <x-campo name="busca" label="Buscar" :value="request('busca')" placeholder="Nome ou e-mail" grupo="" />
            </div>
            <div class="col-6 col-md-3">
                <x-selecao name="perfil" label="Perfil" :opcoes="$perfis" :value="request('perfil')" vazio="Todos" grupo="" />
            </div>
            <div class="col-6 col-md-2">
                <x-selecao name="status" label="Situação" :opcoes="['ativo' => 'Ativos', 'inativo' => 'Inativos']" :value="request('status')" vazio="Todas" grupo="" />
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1">Filtrar</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary" title="Limpar filtros"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Cidades</th>
                    <th>Situação</th>
                    <th class="text-end">Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario->name }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td>{{ $usuario->perfil->nome }}</td>
                        <td class="small">
                            @if ($usuario->veTodasCidades())
                                <span class="text-body-secondary">Todas</span>
                            @else
                                {{ $usuario->cidades->pluck('nome_uf')->join(', ') ?: '—' }}
                            @endif
                        </td>
                        <td><x-situacao :ativo="$usuario->ativo" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @unless ($usuario->is(auth()->user()))
                                <a href="{{ route('usuarios.senha.edit', $usuario) }}" class="btn btn-sm btn-outline-secondary" title="Redefinir senha"><i class="bi bi-key"></i></a>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">Nenhum usuário encontrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $usuarios->links() }}</div>
@endsection
