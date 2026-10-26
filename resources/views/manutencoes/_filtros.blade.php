{{-- Filtros da visão unificada e do calendário (mesmas chaves do FiltroManutencoes).
     Espera $acao, $filtros, as opções do ManutencaoController::opcoesDosFiltros() e, no calendário, $periodo = 'mes' e $mes. --}}
@php($periodo ??= 'datas')
<form method="GET" action="{{ $acao }}" class="card card-body mb-3">
    @if (! empty($filtros['ativo_id']))
        <input type="hidden" name="ativo_id" value="{{ $filtros['ativo_id'] }}">
    @endif
    <div class="row g-2 align-items-end">
        @if ($periodo === 'mes')
            <div class="col-6 col-md-3 col-xl-2">
                <x-campo name="mes" label="Mês" type="month" :value="$mes->format('Y-m')" grupo="" />
            </div>
        @else
            <div class="col-6 col-md-3 col-xl-2"><x-campo-data name="de" label="De" :value="$filtros['de'] ?? null" grupo="" /></div>
            <div class="col-6 col-md-3 col-xl-2"><x-campo-data name="ate" label="Até" :value="$filtros['ate'] ?? null" grupo="" /></div>
            <div class="col-6 col-md-3 col-xl-2">
                <x-selecao name="data" label="Período pela" :opcoes="\App\Services\FiltroManutencoes::BASES_DE_DATA" :value="$filtros['data'] ?? 'efetiva'" :vazio="null" grupo="" />
            </div>
        @endif
        <div class="col-6 col-md-3 col-xl-2"><x-selecao name="status" label="Status" :opcoes="$opcoesStatus" :value="$filtros['status'] ?? null" vazio="Todos" grupo="" /></div>
        <div class="col-6 col-md-3 col-xl-2"><x-selecao name="tipo" label="Programada?" :opcoes="$opcoesTipo" :value="$filtros['tipo'] ?? null" vazio="Todas" grupo="" /></div>
        <div class="col-6 col-md-3 col-xl-2"><x-selecao name="cidade" label="Cidade" :opcoes="$cidades" :value="$filtros['cidade'] ?? null" vazio="Todas" grupo="" /></div>
        <div class="col-6 col-md-3 col-xl-2"><x-selecao name="tipo_equipamento" label="Tipo de equipamento" :opcoes="$tiposEquipamento" :value="$filtros['tipo_equipamento'] ?? null" vazio="Todos" grupo="" /></div>
        <div class="col-6 col-md-3 col-xl-2"><x-selecao name="responsavel" label="Responsável" :opcoes="$responsaveis" :value="$filtros['responsavel'] ?? null" vazio="Todos" grupo="" /></div>
        <div class="col-6 col-md-3 col-xl-2"><x-selecao name="fornecedor" label="Fornecedor" :opcoes="$fornecedores" :value="$filtros['fornecedor'] ?? null" vazio="Todos" grupo="" /></div>
        <div class="col-6 col-md-3 col-xl-2"><x-campo name="ativo" label="Patrimônio" :value="$filtros['ativo'] ?? null" grupo="" /></div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="form-check mb-2">
                <input type="hidden" name="sem_comprovante" value="0">
                <input class="form-check-input" type="checkbox" name="sem_comprovante" id="sem_comprovante" value="1" @checked(! empty($filtros['sem_comprovante']))>
                <label class="form-check-label" for="sem_comprovante">Só sem comprovante</label>
            </div>
        </div>
        <div class="col-md-6 col-xl-4 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            <a href="{{ $acao }}" class="btn btn-outline-secondary">Limpar</a>
        </div>
    </div>
    @if (! empty($filtros['ativo_id']))
        <div class="small text-body-secondary mt-2">
            Filtrando um ativo específico. <a href="{{ request()->fullUrlWithoutQuery(['ativo_id']) }}">Remover</a>
        </div>
    @endif
</form>
