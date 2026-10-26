@extends('layouts.app')

@section('titulo', 'Corrigir valor')

@section('conteudo')
    <x-cabecalho titulo="Corrigir valor da manutenção" />
    <div class="card card-body mb-3" style="max-width: 860px">
        <div>
            Manutenção <a href="{{ route('manutencoes.show', $manutencao) }}">#{{ $manutencao->id }}</a>
            · ativo {{ $manutencao->ativo->patrimonio }} · executada em {{ data_br($manutencao->data_execucao) }}
        </div>
        <div>Valor real atual: <strong>{{ moeda($manutencao->valor_real) }}</strong></div>
    </div>

    <form method="POST" action="{{ route('manutencoes.correcao-valor.store', $manutencao) }}" enctype="multipart/form-data" class="card" style="max-width: 860px">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><x-campo-dinheiro name="valor_novo" label="Novo valor real" required /></div>
            </div>
            <div class="mb-3">
                <label for="motivo" class="form-label">Motivo da correção</label>
                <textarea id="motivo" name="motivo" rows="3" maxlength="1000" required @class(['form-control', 'is-invalid' => $errors->has('motivo')])>{{ old('motivo') }}</textarea>
                @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <h2 class="h6">Comprovante (obrigatório)</h2>
            @if ($comprovantes->isNotEmpty())
                <x-selecao name="comprovante_id" label="Usar nota fiscal ou recibo já anexado" :opcoes="$comprovantes" vazio="— Enviar um novo comprovante abaixo —" />
            @else
                <p class="text-body-secondary small">Esta manutenção não tem nota fiscal nem recibo anexado: envie o comprovante.</p>
            @endif
            <x-campos-anexo :tipos="$tiposAnexo" titulo="Novo comprovante" :obrigatorio="$comprovantes->isEmpty()" />
        </div>
        <x-botoes-formulario :voltar="route('manutencoes.show', $manutencao)" />
    </form>
@endsection
