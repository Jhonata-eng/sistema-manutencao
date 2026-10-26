@extends('layouts.app')

@section('titulo', 'Lançar manutenção não programada')

@section('conteudo')
    <x-cabecalho titulo="Lançar manutenção não programada" />
    <p class="text-body-secondary">Primeiro, encontre o ativo pelo patrimônio, marca, modelo ou cidade.</p>

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6"><x-campo name="busca" label="Patrimônio, marca ou modelo" :value="request('busca')" grupo="" autofocus /></div>
            <div class="col-md-4"><x-selecao name="cidade" label="Cidade" :opcoes="$cidades" :value="request('cidade')" vazio="Todas" grupo="" /></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Buscar</button></div>
        </div>
    </form>

    @if ($ativos !== null)
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Patrimônio</th><th>Tipo</th><th>Marca / modelo</th><th>Cidade</th><th>Localização</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($ativos as $ativo)
                        <tr>
                            <td class="fw-semibold">{{ $ativo->patrimonio }}</td>
                            <td>{{ $ativo->tipo->nome }}</td>
                            <td>{{ $ativo->marca }} {{ $ativo->modelo }}</td>
                            <td>{{ $ativo->cidade->nome_uf }}</td>
                            <td class="small">{{ $ativo->localizacao }}</td>
                            <td class="text-end">
                                <a href="{{ route('manutencoes.nao-programada.create', ['ativo' => $ativo->id]) }}" class="btn btn-sm btn-primary">Selecionar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">Nenhum ativo encontrado.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $ativos->links() }}</div>
    @endif
@endsection
