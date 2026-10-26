@extends('layouts.app')

@section('titulo', 'Lançar manutenção não programada')

@section('conteudo')
    <x-cabecalho titulo="Lançar manutenção não programada">
        <a href="{{ route('manutencoes.nao-programada.create') }}" class="btn btn-outline-secondary">Trocar ativo</a>
    </x-cabecalho>
    <div class="card card-body mb-3" style="max-width: 960px">
        <div>
            Ativo <a href="{{ route('ativos.show', $ativo) }}" class="fw-semibold">{{ $ativo->patrimonio }}</a>
            · {{ $ativo->marca }} {{ $ativo->modelo }} · {{ $ativo->cidade->nome_uf }} · {{ $ativo->localizacao }}
        </div>
        <div class="small text-body-secondary">Manutenção corretiva: entra no gasto realizado, sem valor previsto.</div>
    </div>

    <form method="POST" action="{{ route('manutencoes.nao-programada.store') }}" enctype="multipart/form-data" class="card" style="max-width: 960px">
        @csrf
        <input type="hidden" name="ativo_id" value="{{ $ativo->id }}">
        <div class="card-body">
            @error('ativo_id')<div class="alert alert-danger">{{ $message }}</div>@enderror
            @include('manutencoes._campos-execucao', ['padrao' => [
                'data_execucao' => today(), 'valor_real' => null, 'fornecedor_id' => null, 'descricao' => null,
            ]])
        </div>
        <x-botoes-formulario :voltar="route('ativos.show', $ativo)" />
    </form>
@endsection
