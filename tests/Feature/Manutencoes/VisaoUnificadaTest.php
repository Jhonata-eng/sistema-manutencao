<?php

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Models\Anexo;
use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\Fornecedor;
use App\Models\Manutencao;
use App\Models\PlanoManutencao;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-06-15 10:00'));
    $this->campinas = Cidade::factory()->create();
    $this->ativo = Ativo::factory()->create(['cidade_id' => $this->campinas->id, 'patrimonio' => 'CPS-AC-01']);
    $this->plano = PlanoManutencao::factory()->create(['ativo_id' => $this->ativo->id, 'descricao' => 'Limpeza mensal', 'valor_estimado' => '100.00']);
    $this->fornecedor = Fornecedor::factory()->create();

    $this->atrasada = Manutencao::factory()->doPlano($this->plano, '2026-06-05')->create();
    $this->prevista = Manutencao::factory()->doPlano($this->plano, '2026-06-25')->create();
    $this->cancelada = Manutencao::factory()->doPlano($this->plano, '2026-06-28')->create(['status' => StatusManutencao::Cancelada]);
    // Prevista para 31/05, executada em 02/06: pela data efetiva é de junho, pela prevista é de maio.
    $this->comNota = Manutencao::factory()->doPlano($this->plano, '2026-05-31')->realizada('130.00', '2026-06-02')->create(['fornecedor_id' => $this->fornecedor->id]);
    Anexo::factory()->create(['manutencao_id' => $this->comNota->id, 'tipo' => 'nota_fiscal']);
    $this->naoProgramada = Manutencao::factory()->realizada('450.00', '2026-06-10')->create([
        'ativo_id' => $this->ativo->id, 'tipo' => TipoManutencao::NaoProgramada, 'valor_previsto' => null,
        'data_prevista' => null, 'data_referencia' => null, 'descricao' => 'Troca do compressor',
    ]);
    $this->deMaio = Manutencao::factory()->doPlano($this->plano, '2026-05-10')->realizada('100.00')->create();
    $this->outraCidade = Manutencao::factory()->create(['data_prevista' => '2026-06-20', 'data_referencia' => '2026-06-20']);

    $this->actingAs(usuarioCom(['manutencoes.visualizar'], $this->campinas));
});

function idsListados($resposta): array
{
    return $resposta->viewData('manutencoes')->pluck('id')->sort()->values()->all();
}

function ids(Manutencao ...$manutencoes): array
{
    return collect($manutencoes)->pluck('id')->sort()->values()->all();
}

it('abre no mês atual, pela data efetiva, só com as cidades do usuário', function () {
    $resposta = $this->get(route('manutencoes.index'))->assertOk()->assertSee('de 01/06/2026 até 30/06/2026');

    expect(idsListados($resposta))->toBe(ids($this->atrasada, $this->prevista, $this->cancelada, $this->comNota, $this->naoProgramada));
});

it('soma no rodapé o previsto sem canceladas, o realizado e a diferença', function () {
    $totais = $this->get(route('manutencoes.index'))->viewData('totais');

    // previsto: 100 (atrasada) + 100 (prevista) + 100 (realizada com nota); realizado: 130 + 450.
    expect($totais)->toBe(['previsto' => '300.00', 'realizado' => '580.00', 'diferenca' => '280.00']);
});

it('filtra', function (array $filtros, Closure $esperado) {
    $resposta = $this->get(route('manutencoes.index', ['de' => '2026-05-01', 'ate' => '2026-06-30', ...$filtros]))->assertOk();

    expect(idsListados($resposta))->toBe($esperado());
})->with([
    'atrasadas' => [['status' => 'atrasada'], fn () => ids(test()->atrasada)],
    'previstas (inclui atrasadas)' => [['status' => 'prevista'], fn () => ids(test()->atrasada, test()->prevista)],
    'canceladas' => [['status' => 'cancelada'], fn () => ids(test()->cancelada)],
    'não programadas' => [['tipo' => 'nao_programada'], fn () => ids(test()->naoProgramada)],
    'sem comprovante' => [['sem_comprovante' => '1'], fn () => ids(test()->naoProgramada, test()->deMaio)],
    'fornecedor' => [fn () => ['fornecedor' => test()->fornecedor->id], fn () => ids(test()->comNota)],
    'patrimônio' => [['ativo' => 'CPS-AC'], fn () => ids(test()->atrasada, test()->prevista, test()->cancelada, test()->comNota, test()->naoProgramada, test()->deMaio)],
]);

it('o período pode usar só a data prevista ou só a de execução', function () {
    $maio = ['de' => '2026-05-01', 'ate' => '2026-05-31'];

    expect(idsListados($this->get(route('manutencoes.index', $maio))))->toBe(ids($this->deMaio))
        ->and(idsListados($this->get(route('manutencoes.index', [...$maio, 'data' => 'prevista']))))->toBe(ids($this->comNota, $this->deMaio))
        ->and(idsListados($this->get(route('manutencoes.index', [...$maio, 'data' => 'execucao']))))->toBe(ids($this->deMaio));
});

it('"mes=AAAA-MM" (clique no gráfico) vira o período do mês', function () {
    expect(idsListados($this->get(route('manutencoes.index', ['mes' => '2026-05']))))->toBe(ids($this->deMaio));
});

it('quem vê todas as cidades vê também as de outra cidade', function () {
    $this->actingAs(usuarioCom(['manutencoes.visualizar', 'dados.ver_todas_cidades']));

    expect(idsListados($this->get(route('manutencoes.index'))))->toContain($this->outraCidade->id);
});

it('recusa filtro inválido e exige manutencoes.visualizar', function () {
    $this->get(route('manutencoes.index', ['de' => '31/12/2026']))->assertSessionHasErrors('de');
    $this->get(route('manutencoes.index', ['status' => 'qualquer']))->assertSessionHasErrors('status');

    $this->actingAs(usuarioCom(['ativos.visualizar'], $this->campinas))->get(route('manutencoes.index'))->assertForbidden();
});

it('mostra o calendário do mês com as manutenções nos dias', function () {
    $resposta = $this->get(route('manutencoes.calendario', ['mes' => '2026-06']))->assertOk()->assertSee('Junho de 2026');

    expect($resposta->viewData('porDia')->keys()->sort()->values()->all())->toBe(['2026-06-02', '2026-06-05', '2026-06-10', '2026-06-25', '2026-06-28'])
        ->and($resposta->viewData('semanas'))->toHaveCount(5);
    $resposta->assertSee(route('manutencoes.show', $this->atrasada))->assertDontSee(route('manutencoes.show', $this->outraCidade));
});

it('o calendário mostra "+N mais" quando o dia tem muitas manutenções', function () {
    foreach (range(1, 5) as $i) {
        Manutencao::factory()->create(['ativo_id' => $this->ativo->id, 'data_prevista' => '2026-06-18', 'data_referencia' => null]);
    }

    $this->get(route('manutencoes.calendario', ['mes' => '2026-06']))->assertSee('+ 1 mais');
});
