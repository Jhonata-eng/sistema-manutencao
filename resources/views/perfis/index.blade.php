@extends('layouts.app')

@section('titulo', 'Perfis e permissões')

@section('conteudo')
    <x-cabecalho titulo="Perfis e permissões">
        <a href="{{ route('perfis.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo perfil</a>
    </x-cabecalho>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Perfil</th>
                    <th>Descrição</th>
                    <th class="text-center">Permissões</th>
                    <th class="text-center">Usuários</th>
                    <th class="text-end">Ações</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($perfis as $perfil)
                    <tr>
                        <td class="fw-semibold">{{ $perfil->nome }}</td>
                        <td class="small text-body-secondary">{{ $perfil->descricao }}</td>
                        <td class="text-center">{{ $perfil->permissoes_count }} de {{ $totalPermissoes }}</td>
                        <td class="text-center">{{ $perfil->usuarios_count }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('perfis.edit', $perfil) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @if ($perfil->usuarios_count === 0)
                                <form method="POST" action="{{ route('perfis.destroy', $perfil) }}" class="d-inline" data-confirmar="Excluir o perfil {{ $perfil->nome }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
