@props(['name', 'label', 'type' => 'text', 'value' => null, 'ajuda' => null, 'grupo' => 'mb-3'])
<div class="{{ $grupo }}">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}"
           @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($ajuda)<div class="form-text">{{ $ajuda }}</div>@endif
</div>
