@props(['manutencao'])
@if ($manutencao->estaAtrasada())
    <span class="badge text-bg-danger">Atrasada</span>
@else
    <span class="badge text-bg-{{ $manutencao->status->cor() }}">{{ $manutencao->status->rotulo() }}</span>
@endif
