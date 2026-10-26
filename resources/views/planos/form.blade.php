@extends('layouts.app')

@php($titulo = $plano->exists ? 'Editar plano de manutenção' : 'Novo plano de manutenção')
@php($periodicidadeComNome = array_key_exists($plano->periodicidade_meses, \App\Models\PlanoManutencao::PERIODICIDADES))

@section('titulo', $titulo)

@section('conteudo')
    <x-cabecalho :titulo="$titulo" />
    <p class="text-body-secondary">
        Ativo <a href="{{ route('ativos.show', $plano->equipamento) }}">{{ $plano->equipamento->patrimonio }}</a>
        · {{ $plano->equipamento->marca }} {{ $plano->equipamento->modelo }}
    </p>

    <form method="POST" class="card" style="max-width: 860px"
          action="{{ $plano->exists ? route('planos.update', $plano) : route('ativos.planos.store', $plano->equipamento) }}">
        @csrf
        @if ($plano->exists) @method('PUT') @endif
        <div class="card-body">
            <x-campo name="descricao" label="Serviço" :value="$plano->descricao" required maxlength="150" placeholder="Ex.: Limpeza de filtros e serpentina" />

            <div class="row">
                <div class="col-md-4">
                    <x-selecao name="periodicidade" label="Periodicidade" vazio="" required
                               :opcoes="\App\Models\PlanoManutencao::PERIODICIDADES + ['personalizada' => 'Personalizada (N meses)']"
                               :value="$periodicidadeComNome ? $plano->periodicidade_meses : 'personalizada'" />
                </div>
                <div class="col-md-3" data-exibir-se="#periodicidade" data-valores="personalizada">
                    <x-campo name="periodicidade_meses" label="A cada (meses)" type="number" min="1" :max="\App\Models\PlanoManutencao::PERIODICIDADE_MAXIMA"
                             :value="$plano->periodicidade_meses" />
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <x-campo-data name="data_inicio" label="Primeira ocorrência" :value="$plano->data_inicio" required
                                  :min="$plano->exists ? null : today()->format('Y-m-d')"
                                  ajuda="As próximas seguem o mesmo dia do mês (dia inexistente vai para o último dia)." />
                </div>
                <div class="col-md-4">
                    <x-campo-data name="data_termino" label="Término (opcional)" :value="$plano->data_termino" />
                </div>
                <div class="col-md-4">
                    <x-campo-dinheiro name="valor_estimado" label="Valor médio estimado" :value="$plano->valor_estimado" required />
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <x-selecao name="fornecedor_id" label="Fornecedor padrão (opcional)" :opcoes="$fornecedores" :value="$plano->fornecedor_id" vazio="Nenhum" />
                </div>
            </div>

            <div class="mb-3">
                <label for="observacoes" class="form-label">Observações</label>
                <textarea id="observacoes" name="observacoes" rows="3" maxlength="2000" @class(['form-control', 'is-invalid' => $errors->has('observacoes')])>{{ old('observacoes', $plano->observacoes) }}</textarea>
                @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <x-interruptor-ativo :value="$plano->ativo" label="Plano ativo (gera as manutenções previstas)" />
            <x-confirmacao-cancelamento :pendentes="$pendentes" exibir-se="#ativo" valores="desmarcado"
                                        mensagem="Ao inativar o plano," />

            @if ($plano->exists)
                <p class="form-text mb-0">
                    Mudanças de periodicidade ou datas recalculam as manutenções pendentes a partir de hoje; mudança de valor vale para as pendentes futuras.
                    Realizadas, canceladas e atrasadas não são alteradas.
                </p>
            @endif
        </div>
        <x-botoes-formulario :voltar="$plano->exists ? route('planos.show', $plano) : route('ativos.show', $plano->equipamento)" />
    </form>
@endsection
