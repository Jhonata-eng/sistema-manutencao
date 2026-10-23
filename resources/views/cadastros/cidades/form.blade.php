@extends('layouts.app')

@section('titulo', $cidade->exists ? 'Editar cidade' : 'Nova cidade')

@section('conteudo')
    <x-cabecalho :titulo="$cidade->exists ? 'Editar cidade' : 'Nova cidade'" />

    <form method="POST" action="{{ $cidade->exists ? route('cadastros.cidades.update', $cidade) : route('cadastros.cidades.store') }}" class="card" style="max-width: 560px">
        @csrf
        @if ($cidade->exists) @method('PUT') @endif
        <div class="card-body">
            <div class="row">
                <div class="col-9"><x-campo name="nome" label="Nome" :value="$cidade->nome" required maxlength="100" /></div>
                <div class="col-3"><x-selecao name="uf" label="UF" :opcoes="array_combine(\App\Models\Cidade::UFS, \App\Models\Cidade::UFS)" :value="$cidade->uf" vazio="" required /></div>
            </div>
            <x-interruptor-ativo :value="$cidade->ativo" label="Ativa (disponível para novos ativos e usuários)" />
        </div>
        <x-botoes-formulario :voltar="route('cadastros.cidades.index')" />
    </form>
@endsection
