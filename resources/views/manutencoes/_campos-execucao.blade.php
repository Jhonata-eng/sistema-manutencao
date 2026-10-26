{{-- Campos comuns ao registro de execução e à manutenção não programada. Espera $padrao (valores iniciais) e $fornecedores. --}}
<div class="row">
    <div class="col-md-4">
        <x-campo-data name="data_execucao" label="Data de execução" :value="$padrao['data_execucao']" :max="today()->format('Y-m-d')" required />
    </div>
    <div class="col-md-4">
        <x-campo-dinheiro name="valor_real" label="Valor real" :value="$padrao['valor_real']" required />
    </div>
    <div class="col-md-4">
        <x-selecao name="fornecedor_id" label="Fornecedor" :opcoes="$fornecedores" :value="$padrao['fornecedor_id']" vazio="Nenhum" />
    </div>
</div>
<div class="mb-3">
    <label for="descricao" class="form-label">Descrição do serviço executado</label>
    <textarea id="descricao" name="descricao" rows="3" maxlength="2000" required @class(['form-control', 'is-invalid' => $errors->has('descricao')])>{{ old('descricao', $padrao['descricao']) }}</textarea>
    @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label for="observacoes" class="form-label">Observações</label>
    <textarea id="observacoes" name="observacoes" rows="2" maxlength="2000" @class(['form-control', 'is-invalid' => $errors->has('observacoes')])>{{ old('observacoes') }}</textarea>
    @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
@can('anexos.enviar')
    <x-campos-anexo :tipos="$tiposAnexo" titulo="Nota fiscal, recibo ou outro anexo" />
    <p class="form-text mt-n2">Sem nota fiscal ou recibo, a manutenção fica marcada como “sem comprovante” até a nota ser anexada.</p>
@endcan
