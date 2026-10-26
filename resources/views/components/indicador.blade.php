@props(['rotulo', 'valor', 'detalhe' => null, 'link' => null, 'icone' => null])
{{-- Indicador do painel: rótulo, valor e uma linha de detalhe; o card inteiro leva à lista filtrada. --}}
<div class="card h-100 position-relative">
    <div class="card-body">
        <div class="small text-body-secondary mb-1">
            @if ($icone)<i class="bi bi-{{ $icone }} me-1"></i>@endif{{ $rotulo }}
        </div>
        <div class="indicador-valor">{{ $valor }}</div>
        @if ($detalhe)<div class="small text-body-secondary mt-1">{{ $detalhe }}</div>@endif
        @if ($link)<a href="{{ $link }}" class="stretched-link" aria-label="Ver {{ $rotulo }}"></a>@endif
    </div>
</div>
