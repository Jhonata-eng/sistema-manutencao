@extends('layouts.app')

@section('titulo', 'Início')

@section('conteudo')
    <x-cabecalho titulo="Início" />
    <div class="card">
        <div class="card-body">
            <p class="mb-1">Olá, {{ auth()->user()->name }}.</p>
            <p class="text-body-secondary mb-0">Use o menu ao lado para acessar as áreas liberadas para o seu perfil.</p>
        </div>
    </div>
@endsection
