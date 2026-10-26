<?php

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\Manutencao;
use App\Models\PlanoManutencao;
use App\Models\Responsavel;
use App\Models\TipoEquipamento;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\FiltroManutencoes;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/*
 * Hoje: 15/06/2026. Campinas tem um ar-condicionado com plano de R$ 100,00 e um gerador com uma
 * não programada; Sorocaba tem um plano de R$ 500,00 (fora do escopo do usuário de Campinas).
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-06-15 10:00'));
    $this->campinas = Cidade::factory()->create(['nome' => 'Campinas', 'uf' => 'SP']);
    $this->sorocaba = Cidade::factory()->create(['nome' => 'Sorocaba', 'uf' => 'SP']);
    $this->ar = TipoEquipamento::firstWhere('nome', 'Ar-condicionado');      // criados pelo DatabaseSeeder
    $this->gerador = TipoEquipamento::firstWhere('nome', 'Gerador');
    $this->responsavel = Responsavel::factory()->create();

    $arCampinas = Ativo::factory()->create(['cidade_id' => $this->campinas->id, 'tipo_equipamento_id' => $this->ar->id, 'responsavel_id' => $this->responsavel->id]);
    $geradorCampinas = Ativo::factory()->create(['cidade_id' => $this->campinas->id, 'tipo_equipamento_id' => $this->gerador->id]);
    $arSorocaba = Ativo::factory()->create(['cidade_id' => $this->sorocaba->id, 'tipo_equipamento_id' => $this->ar->id]);

    $plano = PlanoManutencao::factory()->create(['ativo_id' => $arCampinas->id, 'valor_estimado' => '100.00']);
    $ocorrencia = fn (string $data) => Manutencao::factory()->doPlano($plano, $data);
    $ocorrencia('2026-04-10')->realizada('90.00', '2026-04-15')->create();
    $ocorrencia('2026-05-10')->create();                                   // atrasada
    $ocorrencia('2026-06-05')->create();                                   // atrasada
    $ocorrencia('2026-06-10')->realizada('120.00', '2026-06-11')->create();
    $ocorrencia('2026-06-20')->create();
    $ocorrencia('2026-06-25')->create(['status' => StatusManutencao::Cancelada]);
    $ocorrencia('2026-07-10')->create();
    $ocorrencia('2026-08-10')->create();
    Manutencao::factory()->realizada('300.00', '2026-06-02')->create([
        'ativo_id' => $geradorCampinas->id, 'tipo' => TipoManutencao::NaoProgramada,
        'valor_previsto' => null, 'data_prevista' => null, 'data_referencia' => null,
    ]);

    $planoSorocaba = PlanoManutencao::factory()->create(['ativo_id' => $arSorocaba->id, 'valor_estimado' => '500.00']);
    Manutencao::factory()->doPlano($planoSorocaba, '2026-06-12')->realizada('999.00', '2026-06-12')->create();
    Manutencao::factory()->doPlano($planoSorocaba, '2026-06-20')->create();

    $this->usuario = usuarioCom(['dashboard.visualizar', 'manutencoes.visualizar'], $this->campinas);
});

function montarPainel(User $usuario, array $filtros = []): array
{
    return app(DashboardService::class)->montar($usuario, $filtros, CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-06-30'));
}

it('calcula os cards do mês e da carteira', function () {
    $cards = montarPainel($this->usuario)['cards'];

    expect($cards['previsto_mes'])->toBe('300.00')          // 05/06 + 10/06 + 20/06 (cancelada fora)
        ->and($cards['realizado_mes'])->toBe('420.00')      // 120 programada + 300 não programada
        ->and($cards['atrasadas'])->toBe(['quantidade' => 2, 'valor' => '200.00'])
        ->and($cards['proximos_30_dias'])->toBe(['quantidade' => 2, 'valor' => '200.00'])
        ->and($cards['ativos'])->toBe(2)
        ->and($cards['ativos_em_operacao'])->toBe(2);
});

it('soma a previsão dos próximos 12 meses só com as pendentes', function () {
    $previsao = montarPainel($this->usuario)['previsao'];

    expect(array_keys($previsao))->toHaveCount(12)
        ->and(array_key_first($previsao))->toBe('2026-06')
        ->and(array_key_last($previsao))->toBe('2027-05')
        ->and($previsao['2026-06'])->toBe('200.00')         // 05/06 (atrasada) + 20/06
        ->and($previsao['2026-07'])->toBe('100.00')
        ->and($previsao['2026-08'])->toBe('100.00')
        ->and($previsao['2026-09'])->toBe('0.00');
});

it('separa o realizado em programadas e não programadas e traz o previsto do mês', function () {
    $realizado = montarPainel($this->usuario)['realizado'];

    expect(array_key_first($realizado['programadas']))->toBe('2025-07')
        ->and(array_key_last($realizado['programadas']))->toBe('2026-06')
        ->and($realizado['programadas']['2026-06'])->toBe('120.00')
        ->and($realizado['programadas']['2026-04'])->toBe('90.00')
        ->and($realizado['nao_programadas']['2026-06'])->toBe('300.00')
        ->and($realizado['nao_programadas']['2026-04'])->toBe('0.00')
        ->and($realizado['previsto']['2026-06'])->toBe('300.00')
        ->and($realizado['previsto']['2026-05'])->toBe('100.00')
        ->and($realizado['previsto']['2026-04'])->toBe('100.00');
});

it('distribui o gasto do período por tipo e por cidade, maior primeiro', function () {
    $distribuicao = montarPainel($this->usuario)['distribuicao'];

    expect($distribuicao['tipo']->map(fn ($l) => [$l->rotulo, (string) $l->total])->all())->toBe([['Gerador', '300.00'], ['Ar-condicionado', '210.00']])
        ->and($distribuicao['cidade']->map(fn ($l) => [$l->rotulo, (string) $l->total])->all())->toBe([['Campinas/SP', '510.00']]);
});

it('lista atrasadas e próximas', function () {
    $painel = montarPainel($this->usuario);

    expect($painel['atrasadas']->map(fn ($m) => $m->data_prevista->toDateString())->all())->toBe(['2026-05-10', '2026-06-05'])
        ->and($painel['proximas']->map(fn ($m) => $m->data_prevista->toDateString())->all())->toBe(['2026-06-20', '2026-07-10']);
});

it('respeita os filtros de cidade, tipo de equipamento e responsável', function () {
    $admin = usuarioCom(['dashboard.visualizar', 'dados.ver_todas_cidades']);

    $sorocaba = montarPainel($admin, ['cidade' => $this->sorocaba->id])['cards'];
    expect($sorocaba['previsto_mes'])->toBe('1000.00')->and($sorocaba['realizado_mes'])->toBe('999.00');

    $geradores = montarPainel($this->usuario, ['tipo_equipamento' => $this->gerador->id])['cards'];
    expect($geradores['realizado_mes'])->toBe('300.00')->and($geradores['previsto_mes'])->toBe('0.00')->and($geradores['ativos'])->toBe(1);

    $doResponsavel = montarPainel($this->usuario, ['responsavel' => $this->responsavel->id])['cards'];
    expect($doResponsavel['realizado_mes'])->toBe('120.00')->and($doResponsavel['atrasadas']['quantidade'])->toBe(2);
});

it('a lista aberta pelo card ou pela barra soma o mesmo valor', function () {
    $painel = montarPainel($this->usuario);
    $filtro = app(FiltroManutencoes::class);
    $total = fn (array $filtros, string $chave) => $filtro->totais($filtro->consulta($this->usuario, $filtros))[$chave];
    $junho = ['de' => '2026-06-01', 'ate' => '2026-06-30'];

    expect($total([...$junho, 'data' => 'prevista', 'tipo' => 'programada'], 'previsto'))->toBe($painel['cards']['previsto_mes'])
        ->and($total([...$junho, 'status' => 'realizada'], 'realizado'))->toBe($painel['cards']['realizado_mes'])
        ->and($total([...$junho, 'status' => 'prevista'], 'previsto'))->toBe($painel['previsao']['2026-06'])
        ->and($total([...$junho, 'status' => 'realizada', 'tipo' => 'nao_programada'], 'realizado'))->toBe($painel['realizado']['nao_programadas']['2026-06']);
});

it('mostra o painel com os gráficos para quem tem dashboard.visualizar', function () {
    $resposta = $this->actingAs($this->usuario)->get(route('inicio'))->assertOk()
        ->assertSee('Painel')
        ->assertSee('R$ 420,00')
        ->assertSee('id="dados-graficos"', false);

    preg_match('/<script type="application\/json" id="dados-graficos">(.*?)<\/script>/s', $resposta->getContent(), $json);
    $graficos = json_decode($json[1], true);
    expect($graficos['previsao']['valores'][0])->toEqual(200)
        ->and($graficos['previsao']['urls'][0])->toContain('mes=2026-06')->toContain('status=prevista');
});

it('mostra a tela de boas-vindas para quem não tem dashboard.visualizar', function () {
    $this->actingAs(usuarioCom(['ativos.visualizar'], $this->campinas))
        ->get(route('inicio'))
        ->assertOk()
        ->assertDontSee('dados-graficos')
        ->assertSee('Use o menu ao lado');
});
