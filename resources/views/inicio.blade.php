@extends('layouts.app')

@section('titulo', 'Início')

@section('conteudo')
    <x-cabecalho titulo="Início" />
    <div class="card">
        <div class="card-body">
            <p class="mb-1">Olá, {{ auth()->user()->name }}.</p>
            <p class="text-body-secondary mb-0">O painel de orçamento (previsto × realizado) será exibido aqui.</p>
        </div>
    </div>
@endsection
