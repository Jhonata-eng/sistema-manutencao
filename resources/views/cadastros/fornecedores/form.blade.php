@extends('layouts.app')

@section('titulo', $fornecedor->exists ? 'Editar fornecedor' : 'Novo fornecedor')

@section('conteudo')
    <x-cabecalho :titulo="$fornecedor->exists ? 'Editar fornecedor' : 'Novo fornecedor'" />

    <form method="POST" action="{{ $fornecedor->exists ? route('cadastros.fornecedores.update', $fornecedor) : route('cadastros.fornecedores.store') }}" class="card" style="max-width: 760px">
        @csrf
        @if ($fornecedor->exists) @method('PUT') @endif
        <div class="card-body">
            <div class="row">
                <div class="col-md-8"><x-campo name="nome" label="Nome / razão social" :value="$fornecedor->nome" required maxlength="150" /></div>
                <div class="col-md-4">
                    <x-campo name="documento" label="CPF ou CNPJ" :value="$fornecedor->documento_formatado" maxlength="18"
                             ajuda="Com ou sem máscara. Aceita CNPJ alfanumérico." />
                </div>
                <div class="col-md-8"><x-campo name="email" label="E-mail" type="email" :value="$fornecedor->email" maxlength="255" /></div>
                <div class="col-md-4"><x-campo name="telefone" label="Telefone" type="tel" :value="$fornecedor->telefone" maxlength="20" /></div>
            </div>
            <x-interruptor-ativo :value="$fornecedor->ativo" label="Ativo (disponível para novos planos e manutenções)" />
        </div>
        <x-botoes-formulario :voltar="route('cadastros.fornecedores.index')" />
    </form>
@endsection
