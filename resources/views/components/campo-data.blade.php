@props(['name', 'label', 'value' => null, 'ajuda' => null, 'grupo' => 'mb-3'])
{{-- Todo campo de data passa por aqui. O seletor nativo exibe a data no formato do idioma do navegador. --}}
<x-campo :name="$name" :label="$label" type="date" :ajuda="$ajuda" :grupo="$grupo"
         :value="$value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d') : $value" {{ $attributes }} />
