@props(['name', 'label', 'value' => null, 'ajuda' => null, 'grupo' => 'mb-3'])
<div class="{{ $grupo }}">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <div class="input-group has-validation">
        <span class="input-group-text">R$</span>
        <input type="text" inputmode="decimal" id="{{ $name }}" name="{{ $name }}" placeholder="0,00"
               value="{{ old($name, $value === null ? '' : moeda($value, false)) }}"
               {{ $attributes->class(['form-control text-end', 'is-invalid' => $errors->has($name)]) }}>
        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @if ($ajuda)<div class="form-text">{{ $ajuda }}</div>@endif
</div>
