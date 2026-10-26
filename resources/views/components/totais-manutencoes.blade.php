@props(['totais'])
{{-- Totais do filtro: previsto (sem canceladas), realizado e diferença = realizado − previsto. --}}
@php($diferenca = $totais['diferenca'])
<div {{ $attributes->class(['row g-2 text-center']) }}>
    <div class="col-sm-4"><div class="small text-body-secondary">Previsto <span title="Programadas não canceladas">(sem canceladas)</span></div><div class="fs-5 fw-semibold">{{ moeda($totais['previsto']) }}</div></div>
    <div class="col-sm-4"><div class="small text-body-secondary">Realizado</div><div class="fs-5 fw-semibold">{{ moeda($totais['realizado']) }}</div></div>
    <div class="col-sm-4">
        <div class="small text-body-secondary">Diferença (realizado − previsto)</div>
        <div class="fs-5 fw-semibold">
            {{ moeda($diferenca) }}
            @if ((float) $diferenca > 0)
                <span class="small fw-normal text-danger"><i class="bi bi-arrow-up-short"></i>acima do previsto</span>
            @elseif ((float) $diferenca < 0)
                <span class="small fw-normal text-success"><i class="bi bi-arrow-down-short"></i>abaixo do previsto</span>
            @endif
        </div>
    </div>
</div>
