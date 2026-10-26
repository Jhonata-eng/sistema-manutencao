@extends('layouts.app')

@section('titulo', 'Registrar execução')

@section('conteudo')
    <x-cabecalho titulo="Registrar execução" />
    <div class="card card-body mb-3" style="max-width: 960px">
        <div><strong>{{ $manutencao->plano?->descricao }}</strong></div>
        <div class="text-body-secondary">
            Ativo <a href="{{ route('ativos.show', $manutencao->ativo) }}">{{ $manutencao->ativo->patrimonio }}</a>
            · {{ $manutencao->ativo->marca }} {{ $manutencao->ativo->modelo }} · {{ $manutencao->ativo->cidade->nome_uf }}
            · prevista para {{ data_br($manutencao->data_prevista) }} · {{ moeda($manutencao->valor_previsto) }}
        </div>
    </div>

    <form method="POST" action="{{ route('manutencoes.execucao.store', $manutencao) }}" enctype="multipart/form-data" class="card" style="max-width: 960px">
        @csrf
        <div class="card-body">
            @include('manutencoes._campos-execucao', ['padrao' => [
                'data_execucao' => today(),
                'valor_real' => $manutencao->valor_previsto,
                'fornecedor_id' => $fornecedorPadrao,
                'descricao' => $manutencao->plano?->descricao,
            ]])
        </div>
        <x-botoes-formulario :voltar="route('manutencoes.show', $manutencao)" />
    </form>
@endsection
