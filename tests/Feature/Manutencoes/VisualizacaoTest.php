<?php

use App\Enums\TipoManutencao;
use App\Models\Anexo;
use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\Manutencao;
use App\Models\PlanoManutencao;
use App\Models\User;

beforeEach(function () {
    $this->cidade = Cidade::factory()->create();
    $this->ativo = Ativo::factory()->create(['cidade_id' => $this->cidade->id]);
});

it('mostra a manutenção só a quem tem manutencoes.visualizar e acesso à cidade', function () {
    $manutencao = Manutencao::factory()->create(['ativo_id' => $this->ativo->id]);

    $this->actingAs(usuarioCom(['manutencoes.visualizar'], $this->cidade))->get(route('manutencoes.show', $manutencao))->assertOk();
    $this->actingAs(usuarioCom(['manutencoes.visualizar'], Cidade::factory()->create()))->get(route('manutencoes.show', $manutencao))->assertForbidden();
    $this->actingAs(usuarioCom(['ativos.visualizar'], $this->cidade))->get(route('manutencoes.show', $manutencao))->assertForbidden();
});

it('mostra no ativo o histórico completo e o total gasto só das realizadas', function () {
    $plano = PlanoManutencao::factory()->create(['ativo_id' => $this->ativo->id, 'descricao' => 'Limpeza mensal']);
    Manutencao::factory()->doPlano($plano, '2026-01-10')->realizada('100.00')->create();
    Manutencao::factory()->realizada('250.50')->create([
        'ativo_id' => $this->ativo->id, 'tipo' => TipoManutencao::NaoProgramada, 'valor_previsto' => null,
        'data_prevista' => null, 'data_referencia' => null, 'descricao' => 'Troca do compressor',
    ]);
    Manutencao::factory()->doPlano($plano, '2026-02-10')->create(['valor_previsto' => '999.00']);
    Manutencao::factory()->doPlano($plano, '2026-03-10')->create(['status' => 'cancelada']);
    Manutencao::factory()->doPlano($plano, today()->addMonth()->toDateString())->create(['valor_previsto' => '777.00']);

    $this->actingAs(usuarioCom(['ativos.visualizar', 'manutencoes.visualizar'], $this->cidade))
        ->get(route('ativos.show', $this->ativo))
        ->assertSee('Histórico de manutenções')
        ->assertSee('Total gasto: <strong>R$ 350,50</strong>', false)
        ->assertSee('Troca do compressor')
        ->assertSee('Limpeza mensal')
        ->assertSee('Sem comprovante')
        ->assertSee('R$ 999,00')        // atrasada continua no histórico
        ->assertDontSee('R$ 777,00');   // pendente futura fica só no plano
});

it('o escopo das manutenções inclui ativos excluídos das cidades do usuário', function () {
    $usuario = usuarioCom(['manutencoes.visualizar'], $this->cidade);
    $daCidade = Manutencao::factory()->realizada('10.00')->create(['ativo_id' => $this->ativo->id]);
    $this->ativo->delete();
    Manutencao::factory()->create();

    expect(Manutencao::visivelPara($usuario)->pluck('id')->all())->toBe([$daCidade->id]);
});

it('abre ativo, plano e manutenção com várias linhas para quem pode agir em todas (sem consultas por linha)', function () {
    $plano = PlanoManutencao::factory()->create(['ativo_id' => $this->ativo->id]);
    foreach (['2026-01-10', '2026-02-10', '2026-03-10'] as $data) {
        Manutencao::factory()->doPlano($plano, $data)->create();
    }
    $realizada = Manutencao::factory()->doPlano($plano, '2025-12-10')->realizada('80.00')->create();
    Anexo::factory()->count(2)->create(['manutencao_id' => $realizada->id]);

    // O modo estrito (fora de produção) lança exceção se uma relação for carregada linha a linha.
    $this->actingAs(User::factory()->administrador()->create());

    $this->get(route('ativos.show', $this->ativo))->assertOk();
    $this->get(route('planos.show', $plano))->assertOk();
    $this->get(route('manutencoes.show', $realizada))->assertOk();
});
