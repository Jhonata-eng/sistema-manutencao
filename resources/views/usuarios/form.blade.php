@extends('layouts.app')

@section('titulo', $usuario->exists ? 'Editar usuário' : 'Novo usuário')

@section('conteudo')
    <x-cabecalho :titulo="$usuario->exists ? 'Editar usuário' : 'Novo usuário'" />

    <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="card" style="max-width: 760px">
        @csrf
        @if ($usuario->exists) @method('PUT') @endif
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><x-campo name="name" label="Nome" :value="$usuario->name" required maxlength="255" /></div>
                <div class="col-md-6"><x-campo name="email" label="E-mail" type="email" :value="$usuario->email" required maxlength="255" /></div>
                <div class="col-md-6"><x-selecao name="perfil_id" label="Perfil" :opcoes="$perfis" :value="$usuario->perfil_id" required /></div>
                <div class="col-md-6 d-flex align-items-end"><x-interruptor-ativo :value="$usuario->ativo" label="Usuário ativo" /></div>

                @unless ($usuario->exists)
                    <div class="col-md-6"><x-campo name="password" label="Senha" type="password" autocomplete="new-password" ajuda="Mínimo de 8 caracteres." required /></div>
                    <div class="col-md-6"><x-campo name="password_confirmation" label="Confirme a senha" type="password" autocomplete="new-password" required /></div>
                @endunless
            </div>

            <fieldset>
                <legend class="form-label fs-6">Cidades vinculadas</legend>
                <p class="form-text mt-0">O usuário só vê e opera ativos destas cidades, a menos que o perfil tenha a permissão “Ver e operar dados de todas as cidades”.</p>
                @php($marcadas = array_map('intval', old('cidades', $cidadesMarcadas)))
                <div class="row row-cols-2 row-cols-md-3 g-1">
                    @forelse ($cidades as $cidade)
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="cidades[]" value="{{ $cidade->id }}" id="cidade-{{ $cidade->id }}"
                                       @checked(in_array($cidade->id, $marcadas, true))>
                                <label class="form-check-label" for="cidade-{{ $cidade->id }}">
                                    {{ $cidade->nome_uf }} @unless ($cidade->ativo)<span class="text-body-secondary">(inativa)</span>@endunless
                                </label>
                            </div>
                        </div>
                    @empty
                        <p class="text-body-secondary">Nenhuma cidade cadastrada.</p>
                    @endforelse
                </div>
                @error('cidades.*')<div class="text-danger small">{{ $message }}</div>@enderror
            </fieldset>
        </div>
        <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary">Salvar</button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
@endsection
