@extends('layouts.app')

@section('titulo', 'Alterar senha')

@section('conteudo')
    <x-cabecalho titulo="Alterar senha" />
    <div class="card" style="max-width: 480px">
        <div class="card-body">
            <form method="POST" action="{{ route('minha-senha.update') }}">
                @csrf @method('PUT')
                <x-campo name="senha_atual" label="Senha atual" type="password" autocomplete="current-password" required />
                <x-campo name="password" label="Nova senha" type="password" autocomplete="new-password" ajuda="Mínimo de 8 caracteres." required />
                <x-campo name="password_confirmation" label="Confirme a nova senha" type="password" autocomplete="new-password" required />
                <button class="btn btn-primary">Salvar</button>
            </form>
        </div>
    </div>
@endsection
