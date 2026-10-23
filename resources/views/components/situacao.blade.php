@props(['ativo'])
@if ($ativo)
    <span class="badge text-bg-success">Ativo</span>
@else
    <span class="badge text-bg-secondary">Inativo</span>
@endif
