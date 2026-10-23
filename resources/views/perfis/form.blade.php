@extends('layouts.app')

@section('titulo', $perfil->exists ? 'Editar perfil' : 'Novo perfil')

@section('conteudo')
    <x-cabecalho :titulo="$perfil->exists ? 'Editar perfil' : 'Novo perfil'" />

    <form method="POST" action="{{ $perfil->exists ? route('perfis.update', $perfil) : route('perfis.store') }}">
        @csrf
        @if ($perfil->exists) @method('PUT') @endif

        <div class="card mb-3" style="max-width: 760px">
            <div class="card-body">
                <x-campo name="nome" label="Nome" :value="$perfil->nome" required maxlength="60" />
                <x-campo name="descricao" label="Descrição" :value="$perfil->descricao" maxlength="255" grupo="" />
            </div>
        </div>

        <h2 class="h5">Permissões</h2>
        @php($marcadas = array_map('intval', old('permissoes', $permissoesMarcadas)))
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-3">
            @foreach ($gruposPermissoes as $grupo => $permissoes)
                <div class="col">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">{{ $grupo }}</div>
                        <div class="card-body">
                            @foreach ($permissoes as $permissao)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissoes[]" value="{{ $permissao->id }}" id="permissao-{{ $permissao->id }}"
                                           @checked(in_array($permissao->id, $marcadas, true))>
                                    <label class="form-check-label" for="permissao-{{ $permissao->id }}">
                                        {{ $permissao->descricao }}
                                        <code class="d-block small">{{ $permissao->chave }}</code>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Salvar</button>
            <a href="{{ route('perfis.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
@endsection
