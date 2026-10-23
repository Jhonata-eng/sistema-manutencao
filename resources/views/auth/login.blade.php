@extends('layouts.guest')

@section('titulo', 'Entrar')

@section('conteudo')
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <x-campo name="email" label="E-mail" type="email" autocomplete="username" required autofocus />
                <x-campo name="password" label="Senha" type="password" autocomplete="current-password" required />
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="lembrar" id="lembrar" value="1" @checked(old('lembrar'))>
                    <label class="form-check-label" for="lembrar">Lembrar-me neste computador</label>
                </div>
                <button class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </div>
    <p class="text-center text-body-secondary small mt-3">Esqueceu a senha? Peça a redefinição a um administrador.</p>
@endsection
