@props(['value' => true, 'label' => 'Ativo'])
<div class="form-check form-switch mb-3">
    <input type="hidden" name="ativo" value="0">
    <input class="form-check-input @error('ativo') is-invalid @enderror" type="checkbox" role="switch" id="ativo" name="ativo" value="1"
           @checked(old('ativo', $value))>
    <label class="form-check-label" for="ativo">{{ $label }}</label>
    @error('ativo')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
