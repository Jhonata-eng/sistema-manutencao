@extends('layouts.app')

@section('titulo', $ativo->exists ? 'Editar ativo' : 'Novo ativo')

@section('conteudo')
    <x-cabecalho :titulo="$ativo->exists ? 'Editar ativo '.$ativo->patrimonio : 'Novo ativo'" />

    <form method="POST" enctype="multipart/form-data" class="card" style="max-width: 960px"
          action="{{ $ativo->exists ? route('ativos.update', $ativo) : route('ativos.store') }}">
        @csrf
        @if ($ativo->exists) @method('PUT') @endif
        <div class="card-body">
            <h2 class="h6 text-body-secondary">Identificação</h2>
            <div class="row">
                <div class="col-md-4"><x-campo name="patrimonio" label="Nº de patrimônio" :value="$ativo->patrimonio" required maxlength="50" /></div>
                <div class="col-md-4"><x-selecao name="tipo_equipamento_id" label="Tipo" :opcoes="$tipos" :value="$ativo->tipo_equipamento_id" required /></div>
                <div class="col-md-4">
                    <x-selecao name="status" label="Status" :opcoes="\App\Enums\StatusAtivo::opcoes()" :value="$ativo->status?->value" vazio="" required />
                </div>
                <div class="col-12">
                    <x-confirmacao-cancelamento :pendentes="$pendentes" exibir-se="#status" valores="inativo,baixado"
                                                mensagem="Ao tirar o ativo de operação, os planos serão inativados e" />
                </div>
                <div class="col-md-4"><x-campo name="marca" label="Marca" :value="$ativo->marca" required maxlength="60" /></div>
                <div class="col-md-4"><x-campo name="modelo" label="Modelo" :value="$ativo->modelo" required maxlength="100" /></div>
                <div class="col-md-4"><x-campo name="numero_serie" label="Número de série" :value="$ativo->numero_serie" maxlength="100" /></div>
                <div class="col-md-4"><x-campo name="capacidade" label="Capacidade / especificação" :value="$ativo->capacidade" maxlength="100" placeholder="Ex.: 12.000 BTUs, 150 kVA" /></div>
                <div class="col-md-4"><x-campo name="ano_fabricacao" label="Ano de fabricação" type="number" :value="$ativo->ano_fabricacao" min="1950" :max="now()->year + 1" /></div>
                <div class="col-md-4"><x-campo-data name="data_aquisicao" label="Data de aquisição" :value="$ativo->data_aquisicao" :max="now()->format('Y-m-d')" /></div>
            </div>

            <h2 class="h6 text-body-secondary mt-2">Localização e responsável</h2>
            <div class="row">
                <div class="col-md-4"><x-selecao name="cidade_id" label="Cidade" :opcoes="$cidades" :value="$ativo->cidade_id" required /></div>
                <div class="col-md-8"><x-campo name="localizacao" label="Localização detalhada" :value="$ativo->localizacao" required maxlength="150" placeholder="Ex.: POP Centro, sala técnica" /></div>
                <div class="col-md-4"><x-selecao name="responsavel_id" label="Responsável" :opcoes="$responsaveis" :value="$ativo->responsavel_id" vazio="Nenhum" /></div>
            </div>

            <h2 class="h6 text-body-secondary mt-2">Outros</h2>
            <div class="mb-3">
                <label for="observacoes" class="form-label">Observações</label>
                <textarea id="observacoes" name="observacoes" rows="3" maxlength="2000" @class(['form-control', 'is-invalid' => $errors->has('observacoes')])>{{ old('observacoes', $ativo->observacoes) }}</textarea>
                @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row align-items-end">
                <div class="col-md-6">
                    <x-campo name="foto" label="Foto (opcional)" type="file" accept="image/jpeg,image/png,image/webp" ajuda="JPG, PNG ou WebP, até 5 MB." />
                </div>
                @if ($ativo->foto_path)
                    <div class="col-md-6 mb-3 d-flex align-items-center gap-3">
                        <img src="{{ route('ativos.foto', $ativo) }}" alt="Foto atual" class="rounded border" style="height: 64px">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remover_foto" id="remover_foto" value="1" @checked(old('remover_foto'))>
                            <label class="form-check-label" for="remover_foto">Remover a foto atual</label>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <x-botoes-formulario :voltar="$ativo->exists ? route('ativos.show', $ativo) : route('ativos.index')" />
    </form>
@endsection
