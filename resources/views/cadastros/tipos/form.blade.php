@extends('layouts.app')

@section('titulo', $tipo->exists ? 'Editar tipo de equipamento' : 'Novo tipo de equipamento')

@section('conteudo')
    <x-cabecalho :titulo="$tipo->exists ? 'Editar tipo de equipamento' : 'Novo tipo de equipamento'" />

    <form method="POST" action="{{ $tipo->exists ? route('cadastros.tipos.update', $tipo) : route('cadastros.tipos.store') }}" class="card" style="max-width: 560px">
        @csrf
        @if ($tipo->exists) @method('PUT') @endif
        <div class="card-body">
            <x-campo name="nome" label="Nome" :value="$tipo->nome" required maxlength="60" placeholder="Ex.: Ar-condicionado" />
            <x-interruptor-ativo :value="$tipo->ativo" label="Ativo (disponível para novos ativos)" />
        </div>
        <x-botoes-formulario :voltar="route('cadastros.tipos.index')" />
    </form>
@endsection
