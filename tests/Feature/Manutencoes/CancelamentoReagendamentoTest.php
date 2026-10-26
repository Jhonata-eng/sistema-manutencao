<?php

use App\Enums\StatusManutencao;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\Manutencao;
use App\Models\PlanoManutencao;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-03-15 10:00'));
    $this->cidade = Cidade::factory()->create();
    $this->plano = PlanoManutencao::factory()->create([
        'ativo_id' => Ativo::factory()->create(['cidade_id' => $this->cidade->id])->id,
        'data_inicio' => '2026-03-20',
    ]);
    $this->artisan('manutencoes:estender-horizonte');
    $this->manutencao = $this->plano->manutencoes()->where('data_prevista', '2026-04-20')->first();
    $this->usuario = usuarioCom(['manutencoes.visualizar', 'manutencoes.cancelar_reagendar'], $this->cidade);
    $this->actingAs($this->usuario);
});

it('cancela com justificativa obrigatória e mantém a data ocupada no calendário', function () {
    $this->post(route('manutencoes.cancelar', $this->manutencao), ['motivo_cancelamento' => ''])->assertSessionHasErrors('motivo_cancelamento');

    $this->post(route('manutencoes.cancelar', $this->manutencao), ['motivo_cancelamento' => 'Equipamento em reforma'])->assertSessionHasNoErrors();

    $cancelada = $this->manutencao->fresh();
    expect($cancelada->status)->toBe(StatusManutencao::Cancelada)
        ->and($cancelada->motivo_cancelamento)->toBe('Equipamento em reforma')
        ->and($cancelada->cancelado_por)->toBe($this->usuario->id)
        ->and($cancelada->data_referencia->toDateString())->toBe('2026-04-20')
        ->and(Auditoria::where('evento', 'cancelada')->where('auditavel_id', $cancelada->id)->value('motivo'))->toBe('Equipamento em reforma');

    $this->artisan('manutencoes:estender-horizonte')->expectsOutputToContain('0 manutenção(ões)');
    expect($this->plano->manutencoes()->where('data_referencia', '2026-04-20')->count())->toBe(1);
});

it('reagenda com justificativa, mantendo a data de referência, e mostra no histórico', function () {
    $this->post(route('manutencoes.reagendar', $this->manutencao), ['nova_data' => '2026-04-25', 'motivo_reagendamento' => 'Fornecedor sem agenda'])
        ->assertSessionHasNoErrors();

    $reagendada = $this->manutencao->fresh();
    $registro = Auditoria::where('evento', 'reagendada')->where('auditavel_id', $reagendada->id)->first();
    expect($reagendada->data_prevista->toDateString())->toBe('2026-04-25')
        ->and($reagendada->data_referencia->toDateString())->toBe('2026-04-20')
        ->and($registro->antes)->toBe(['data_prevista' => '2026-04-20'])
        ->and($registro->depois)->toBe(['data_prevista' => '2026-04-25'])
        ->and($registro->motivo)->toBe('Fornecedor sem agenda');

    $this->get(route('manutencoes.show', $reagendada))
        ->assertSee('Reagendamento')
        ->assertSee('20/04/2026 → 25/04/2026')
        ->assertSee('Fornecedor sem agenda');
});

it('valida o reagendamento', function (array $dados, string $campo) {
    $this->post(route('manutencoes.reagendar', $this->manutencao), [
        'nova_data' => '2026-04-25', 'motivo_reagendamento' => 'Motivo', ...$dados,
    ])->assertSessionHasErrors($campo);
})->with([
    'data no passado' => [['nova_data' => '2026-03-14'], 'nova_data'],
    'mesma data' => [['nova_data' => '2026-04-20'], 'nova_data'],
    'sem justificativa' => [['motivo_reagendamento' => ''], 'motivo_reagendamento'],
]);

it('só manutenções previstas podem ser canceladas ou reagendadas', function () {
    $realizada = Manutencao::factory()->doPlano($this->plano, '2026-03-20')->realizada('100.00')->create(['data_referencia' => '2026-01-20']);

    $this->post(route('manutencoes.cancelar', $realizada), ['motivo_cancelamento' => 'X'])->assertForbidden();
    $this->post(route('manutencoes.reagendar', $realizada), ['nova_data' => '2026-04-25', 'motivo_reagendamento' => 'X'])->assertForbidden();
});

it('exige manutencoes.cancelar_reagendar e esconde os formulários de quem não tem', function () {
    $this->actingAs(usuarioCom(['manutencoes.visualizar'], $this->cidade));

    $this->get(route('manutencoes.show', $this->manutencao))->assertOk()->assertDontSee(route('manutencoes.cancelar', $this->manutencao));
    $this->post(route('manutencoes.cancelar', $this->manutencao), ['motivo_cancelamento' => 'X'])->assertForbidden();
});
