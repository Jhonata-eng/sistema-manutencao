@extends('layouts.app')

@section('titulo', 'Redefinir senha')

@section('conteudo')
    <x-cabecalho :titulo="'Redefinir senha de '.$usuario->name" />
    <div class="card" style="max-width: 480px">
        <div class="card-body">
            <p class="text-body-secondary">Ao salvar, as sessões abertas deste usuário e o “lembrar-me” serão encerrados. Informe a nova senha a ele por um canal seguro.</p>
            <form method="POST" action="{{ route('usuarios.senha.update', $usuario) }}">
                @csrf @method('PUT')
                <x-campo name="password" label="Nova senha" type="password" autocomplete="new-password" ajuda="Mínimo de 8 caracteres." required />
                <x-campo name="password_confirmation" label="Confirme a nova senha" type="password" autocomplete="new-password" required />
                <button class="btn btn-primary">Redefinir</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
@endsection
