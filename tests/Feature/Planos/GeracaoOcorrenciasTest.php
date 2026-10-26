<?php

use App\Enums\StatusAtivo;
use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\PlanoManutencao;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-01-31 09:00'));
    $this->cidade = Cidade::factory()->create();
    $this->ativo = Ativo::factory()->create(['cidade_id' => $this->cidade->id]);
    $this->actingAs(usuarioCom(['ativos.visualizar', 'planos.visualizar', 'planos.criar', 'planos.editar'], $this->cidade));
});

it('gera as previstas do horizonte ao criar o plano, com o caso do dia 31', function () {
    $this->post(route('ativos.planos.store', $this->ativo), dadosPlano())->assertSessionHasNoErrors();

    $plano = PlanoManutencao::first();
    expect(datasDoPlano($plano))->toBe([
        '2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31', '2026-06-30',
        '2026-07-31', '2026-08-31', '2026-09-30', '2026-10-31', '2026-11-30', '2026-12-31',
    ]);

    $primeira = $plano->manutencoes()->first();
    expect($primeira->status)->toBe(StatusManutencao::Prevista)
        ->and($primeira->tipo)->toBe(TipoManutencao::Programada)
        ->and($primeira->ativo_id)->toBe($this->ativo->id)
        ->and($plano->manutencoes()->pluck('valor_previsto')->unique()->all())->toBe(['150.00']);
});

it('não gera depois da data de término', function () {
    $this->post(route('ativos.planos.store', $this->ativo), dadosPlano(['data_termino' => '2026-04-15']));

    expect(datasDoPlano(PlanoManutencao::first()))->toBe(['2026-01-31', '2026-02-28', '2026-03-31']);
});

it('aceita periodicidade personalizada', function () {
    $this->post(route('ativos.planos.store', $this->ativo), dadosPlano(['periodicidade' => 'personalizada', 'periodicidade_meses' => '5']));

    $plano = PlanoManutencao::first();
    expect($plano->periodicidade_meses)->toBe(5)
        ->and($plano->rotuloPeriodicidade())->toBe('A cada 5 meses')
        ->and(datasDoPlano($plano))->toBe(['2026-01-31', '2026-06-30', '2026-11-30']);
});

it('valida o formulário do plano', function (array $dados, string $campo) {
    $this->post(route('ativos.planos.store', $this->ativo), dadosPlano($dados))->assertSessionHasErrors($campo);

    expect(PlanoManutencao::count())->toBe(0);
})->with([
    'primeira ocorrência no passado' => [['data_inicio' => '2026-01-30'], 'data_inicio'],
    'término antes do início' => [['data_termino' => '2026-01-01'], 'data_termino'],
    'valor inválido' => [['valor_estimado' => 'abc'], 'valor_estimado'],
    'valor negativo' => [['valor_estimado' => '-10,00'], 'valor_estimado'],
    'periodicidade personalizada acima do limite' => [['periodicidade' => 'personalizada', 'periodicidade_meses' => '121'], 'periodicidade_meses'],
]);

it('não cria plano ativo para ativo fora de operação', function () {
    $this->ativo->update(['status' => StatusAtivo::Baixado]);

    $this->post(route('ativos.planos.store', $this->ativo), dadosPlano())->assertSessionHasErrors('ativo');
});

it('o comando diário estende o horizonte sem duplicar', function () {
    $this->post(route('ativos.planos.store', $this->ativo), dadosPlano());
    $plano = PlanoManutencao::first();
    $this->travelTo(Carbon::parse('2026-03-01 01:00'));

    $this->artisan('manutencoes:estender-horizonte')->expectsOutputToContain('2 manutenção(ões)')->assertSuccessful();
    $this->artisan('manutencoes:estender-horizonte')->expectsOutputToContain('0 manutenção(ões)');

    expect(datasDoPlano($plano))->toHaveCount(14)
        ->and(array_slice(datasDoPlano($plano), -2))->toBe(['2027-01-31', '2027-02-28']);
});

it('não recria ocorrência reagendada nem a cancelada manualmente', function () {
    $this->post(route('ativos.planos.store', $this->ativo), dadosPlano());
    $plano = PlanoManutencao::first();
    $plano->manutencoes()->where('data_prevista', '2026-05-31')->update(['data_prevista' => '2026-06-05']);
    $plano->manutencoes()->where('data_prevista', '2026-06-30')->update(['status' => StatusManutencao::Cancelada]);

    $this->artisan('manutencoes:estender-horizonte')->expectsOutputToContain('0 manutenção(ões)');

    expect($plano->manutencoes()->count())->toBe(12);
});

it('o comando ignora planos inativos e ativos fora de operação ou excluídos', function () {
    $normal = PlanoManutencao::factory()->create(['ativo_id' => $this->ativo->id]);
    $inativo = PlanoManutencao::factory()->create(['ativo_id' => $this->ativo->id, 'ativo' => false]);
    $deAtivoBaixado = PlanoManutencao::factory()->create(['ativo_id' => Ativo::factory()->create(['status' => StatusAtivo::Baixado])->id]);
    $deAtivoExcluido = PlanoManutencao::factory()->create();
    $deAtivoExcluido->equipamento->delete();

    $this->artisan('manutencoes:estender-horizonte')->assertSuccessful();

    expect($normal->manutencoes()->count())->toBe(12)
        ->and($inativo->manutencoes()->count())->toBe(0)
        ->and($deAtivoBaixado->manutencoes()->count())->toBe(0)
        ->and($deAtivoExcluido->manutencoes()->count())->toBe(0);
});

it('agenda o comando para rodar todo dia', function () {
    $evento = collect(app(Schedule::class)->events())
        ->first(fn ($evento) => str_contains($evento->command, 'manutencoes:estender-horizonte'));

    expect($evento)->not->toBeNull()
        ->and($evento->expression)->toBe('0 1 * * *');
});
