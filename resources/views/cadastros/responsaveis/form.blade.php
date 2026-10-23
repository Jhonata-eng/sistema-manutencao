@extends('layouts.app')

@section('titulo', $responsavel->exists ? 'Editar responsável' : 'Novo responsável')

@section('conteudo')
    <x-cabecalho :titulo="$responsavel->exists ? 'Editar responsável' : 'Novo responsável'" />

    <form method="POST" action="{{ $responsavel->exists ? route('cadastros.responsaveis.update', $responsavel) : route('cadastros.responsaveis.store') }}" class="card" style="max-width: 760px">
        @csrf
        @if ($responsavel->exists) @method('PUT') @endif
        <div class="card-body">
            <div class="row">
                <div class="col-md-8"><x-campo name="nome" label="Nome" :value="$responsavel->nome" required maxlength="100" /></div>
                <div class="col-md-4"><x-campo name="setor" label="Setor" :value="$responsavel->setor" maxlength="60" /></div>
                <div class="col-md-8"><x-campo name="email" label="E-mail" type="email" :value="$responsavel->email" maxlength="255" /></div>
                <div class="col-md-4"><x-campo name="telefone" label="Telefone" type="tel" :value="$responsavel->telefone" maxlength="20" /></div>
                <div class="col-md-8">
                    <x-selecao name="user_id" label="Usuário do sistema (opcional)" :opcoes="$usuarios" :value="$responsavel->user_id" vazio="Nenhum" />
                </div>
            </div>
            <x-interruptor-ativo :value="$responsavel->ativo" label="Ativo (disponível para novos ativos)" />
        </div>
        <x-botoes-formulario :voltar="route('cadastros.responsaveis.index')" />
    </form>
@endsection
