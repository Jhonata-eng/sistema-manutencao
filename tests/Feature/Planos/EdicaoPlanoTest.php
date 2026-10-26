<?php

use App\Enums\StatusManutencao;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\PlanoManutencao;
use App\Services\PlanoService;
use Illuminate\Support\Carbon;

/*
 * Cenário: plano mensal criado em 10/01/2026 (R$ 100,00), gerando 10/01 a 10/12/2026.
 * Janeiro foi realizado, fevereiro cancelado manualmente e hoje é 15/03/2026,
 * então 10/03 está atrasada e 10/04 em diante são pendentes futuras.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-01-10 08:00'));
    $cidade = Cidade::factory()->create();
    $this->usuario = usuarioCom(['ativos.visualizar', 'planos.visualizar', 'planos.criar', 'planos.editar', 'planos.excluir'], $cidade);
    $this->actingAs($this->usuario);

    $this->plano = new PlanoManutencao;
    $this->plano->ativo_id = Ativo::factory()->create(['cidade_id' => $cidade->id])->id;
    app(PlanoService::class)->salvar($this->plano, [
        'descricao' => 'Limpeza', 'periodicidade_meses' => 1, 'data_inicio' => '2026-01-10', 'valor_estimado' => '100.00', 'ativo' => true,
    ], $this->usuario);

    $this->plano->manutencoes()->where('data_prevista', '2026-01-10')
        ->update(['status' => StatusManutencao::Realizada, 'data_execucao' => '2026-01-12', 'valor_real' => '120.00']);
    $this->plano->manutencoes()->where('data_prevista', '2026-02-10')
        ->update(['status' => StatusManutencao::Cancelada, 'motivo_cancelamento' => 'Equipamento em reforma']);

    $this->travelTo(Carbon::parse('2026-03-15 08:00'));
});

function editarPlano(array $sobrescrever = [])
{
    return test()->put(route('planos.update', test()->plano), dadosPlano([
        'descricao' => 'Limpeza', 'data_inicio' => '2026-01-10', 'valor_estimado' => '100,00', ...$sobrescrever,
    ]));
}

function valorPrevistoEm(string $data): string
{
    return test()->plano->manutencoes()->where('data_prevista', $data)->value('valor_previsto');
}

it('mudar só o valor atualiza as pendentes futuras e mais nada', function () {
    editarPlano(['valor_estimado' => '200,00'])->assertSessionHasNoErrors();

    expect(valorPrevistoEm('2026-04-10'))->toBe('200.00')
        ->and(valorPrevistoEm('2026-12-10'))->toBe('200.00')
        ->and(valorPrevistoEm('2026-03-10'))->toBe('100.00')   // atrasada
        ->and(valorPrevistoEm('2026-01-10'))->toBe('100.00')   // realizada
        ->and(valorPrevistoEm('2026-02-10'))->toBe('100.00')   // cancelada
        ->and($this->plano->manutencoes()->count())->toBe(12);
});

it('mudar a periodicidade recalcula só as pendentes futuras', function () {
    editarPlano(['periodicidade' => '3'])->assertSessionHasNoErrors();

    expect(datasDoPlano($this->plano, 'prevista'))->toBe(['2026-03-10', '2026-04-10', '2026-07-10', '2026-10-10', '2027-01-10'])
        ->and(datasDoPlano($this->plano, 'realizada'))->toBe(['2026-01-10'])
        ->and(datasDoPlano($this->plano, 'cancelada'))->toBe(['2026-02-10'])
        ->and(Auditoria::where('evento', 'ocorrencias_recalculadas')->where('auditavel_id', $this->plano->id)->exists())->toBeTrue();
});

it('mantém a pendente reagendada a partir de uma data passada e aplica o novo valor a ela', function () {
    $this->plano->manutencoes()->where('data_referencia', '2026-03-10')->update(['data_prevista' => '2026-03-20']);

    editarPlano(['periodicidade' => '3', 'valor_estimado' => '250,00'])->assertSessionHasNoErrors();

    $reagendada = $this->plano->manutencoes()->where('data_referencia', '2026-03-10')->first();
    expect($reagendada->data_prevista->toDateString())->toBe('2026-03-20')
        ->and($reagendada->valor_previsto)->toBe('250.00')
        ->and(valorPrevistoEm('2026-07-10'))->toBe('250.00');
});

it('inativar exige confirmação e cancela todas as pendentes, inclusive as atrasadas', function () {
    editarPlano(['ativo' => '0'])->assertSessionHasErrors('confirmar_cancelamento');
    expect($this->plano->fresh()->ativo)->toBeTrue();

    editarPlano(['ativo' => '0', 'confirmar_cancelamento' => '1'])->assertSessionHasNoErrors();

    $atrasada = $this->plano->manutencoes()->where('data_prevista', '2026-03-10')->first();
    expect($this->plano->fresh()->ativo)->toBeFalse()
        ->and($this->plano->manutencoes()->pendentes()->count())->toBe(0)
        ->and($atrasada->status)->toBe(StatusManutencao::Cancelada)
        ->and($atrasada->motivo_cancelamento)->toBe('Plano inativado.')
        ->and($atrasada->cancelado_por)->toBe($this->usuario->id)
        ->and(datasDoPlano($this->plano, 'realizada'))->toBe(['2026-01-10']);

    $registro = Auditoria::where('evento', 'ocorrencias_canceladas')->where('auditavel_id', $this->plano->id)->first();
    expect($registro->depois['quantidade'])->toBe(10)
        ->and($registro->motivo)->toBe('Plano inativado.');
});

it('reativar volta a gerar as previstas a partir de hoje', function () {
    editarPlano(['ativo' => '0', 'confirmar_cancelamento' => '1']);

    editarPlano(['ativo' => '1'])->assertSessionHasNoErrors();

    expect(datasDoPlano($this->plano, 'prevista'))->toHaveCount(12)
        ->and(datasDoPlano($this->plano, 'prevista')[0])->toBe('2026-04-10')
        ->and(datasDoPlano($this->plano, 'prevista')[11])->toBe('2027-03-10')
        // A cancelada manualmente continua ocupando a data do calendário.
        ->and(datasDoPlano($this->plano, 'cancelada'))->toContain('2026-02-10');
});

it('não exclui plano com histórico e exclui plano sem histórico junto com as previstas', function () {
    $this->delete(route('planos.destroy', $this->plano))->assertSessionHasErrors('protecao');
    expect(PlanoManutencao::find($this->plano->id))->not->toBeNull();

    $novo = new PlanoManutencao;
    $novo->ativo_id = $this->plano->ativo_id;
    app(PlanoService::class)->salvar($novo, ['descricao' => 'Revisão', 'periodicidade_meses' => 6, 'data_inicio' => '2026-04-01', 'valor_estimado' => '500.00', 'ativo' => true], $this->usuario);
    expect($novo->manutencoes()->count())->toBe(2);

    $this->delete(route('planos.destroy', $novo))->assertRedirect(route('ativos.show', $novo->ativo_id));

    expect(PlanoManutencao::find($novo->id))->toBeNull()
        ->and($novo->manutencoes()->count())->toBe(0);
});

it('atualiza o valor médio com base nas execuções', function () {
    $this->plano->manutencoes()->where('data_prevista', '2026-03-10')
        ->update(['status' => StatusManutencao::Realizada, 'data_execucao' => '2026-03-11', 'valor_real' => '131.51']);

    $this->get(route('planos.show', $this->plano))
        ->assertSee('R$ 125,76')
        ->assertSee('Atualizar valor médio com base no histórico');

    $this->post(route('planos.atualizar-valor-medio', $this->plano))->assertSessionHasNoErrors();

    expect($this->plano->fresh()->valor_estimado)->toBe('125.76')
        ->and(valorPrevistoEm('2026-04-10'))->toBe('125.76');
});

it('não atualiza o valor médio sem execuções', function () {
    $this->plano->manutencoes()->where('status', StatusManutencao::Realizada)->update(['status' => StatusManutencao::Cancelada]);

    $this->post(route('planos.atualizar-valor-medio', $this->plano))->assertSessionHasErrors('protecao');
});
