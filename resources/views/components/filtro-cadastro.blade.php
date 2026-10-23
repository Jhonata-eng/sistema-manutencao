@props(['rota', 'placeholder' => 'Buscar…'])
<form method="GET" action="{{ route($rota) }}" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-7">
            <x-campo name="busca" label="Buscar" :value="request('busca')" :placeholder="$placeholder" grupo="" />
        </div>
        <div class="col-6 col-md-3">
            <x-selecao name="status" label="Situação" :opcoes="['ativo' => 'Ativos', 'inativo' => 'Inativos']" :value="request('status')" vazio="Todas" grupo="" />
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary flex-grow-1">Filtrar</button>
            <a href="{{ route($rota) }}" class="btn btn-outline-secondary" title="Limpar filtros"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</form>
