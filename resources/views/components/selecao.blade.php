@props(['name', 'label', 'opcoes' => [], 'value' => null, 'vazio' => 'Selecione…', 'grupo' => 'mb-3'])
@php($selecionado = (string) old($name, $value))
<div class="{{ $grupo }}">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        @if ($vazio !== null)<option value="">{{ $vazio }}</option>@endif
        @foreach ($opcoes as $chave => $rotulo)
            <option value="{{ $chave }}" @selected($selecionado === (string) $chave)>{{ $rotulo }}</option>
        @endforeach
    </select>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
