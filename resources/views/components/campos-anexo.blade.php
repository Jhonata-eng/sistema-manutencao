@props(['tipos', 'obrigatorio' => false, 'titulo' => 'Anexo'])
<fieldset class="border rounded px-3 pt-2 mb-3">
    <legend class="float-none w-auto px-2 fs-6 mb-0">
        {{ $titulo }} @unless ($obrigatorio)<span class="text-body-secondary small">(opcional)</span>@endunless
    </legend>
    <div class="row">
        <div class="col-md-8">
            <x-campo name="anexo_arquivo" label="Arquivo" type="file" :required="$obrigatorio"
                     accept=".pdf,.jpg,.jpeg,.png,.xml,application/pdf,image/jpeg,image/png,text/xml,application/xml"
                     :ajuda="'PDF, JPG, PNG ou XML (NF-e), até '.config('manutencao.anexo_tamanho_max_mb').' MB.'" />
        </div>
        <div class="col-md-4"><x-selecao name="anexo_tipo" label="Tipo" :opcoes="$tipos" /></div>
        <div class="col-md-4"><x-campo name="anexo_numero_nota" label="Número da nota" maxlength="50" /></div>
        <div class="col-md-4"><x-campo-data name="anexo_data_emissao" label="Data de emissão" :max="today()->format('Y-m-d')" /></div>
        <div class="col-md-4"><x-campo-dinheiro name="anexo_valor_nota" label="Valor da nota" /></div>
    </div>
</fieldset>
