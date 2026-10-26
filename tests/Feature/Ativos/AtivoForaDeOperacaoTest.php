<?php

use App\Enums\StatusAtivo;
use App\Enums\StatusManutencao;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\PlanoManutencao;
use App\Services\PlanoService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-01-10 08:00'));
    $cidade = Cidade::factory()->create();
    $this->usuario = usuarioCom(['ativos.visualizar', 'ativos.editar', 'ativos.excluir'], $cidade);
    $this->actingAs($this->usuario);
    $this->ativo = Ativo::factory()->create(['cidade_id' => $cidade->id]);

    $this->plano = new PlanoManutencao;
    $this->plano->ativo_id = $this->ativo->id;
    app(PlanoService::class)->salvar($this->plano, [
        'descricao' => 'Limpeza', 'periodicidade_meses' => 1, 'data_inicio' => '2026-01-10', 'valor_estimado' => '100.00', 'ativo' => true,
    ], $this->usuario);

    // Hoje é 15/02: 10/01 e 10/02 estão atrasadas, 10/03 em diante são futuras.
    $this->travelTo(Carbon::parse('2026-02-15 08:00'));
});

function dadosDoAtivo(Ativo $ativo, array $sobrescrever = []): array
{
    return [
        'patrimonio' => $ativo->patrimonio, 'tipo_equipamento_id' => $ativo->tipo_equipamento_id, 'status' => $ativo->status->value,
        'marca' => $ativo->marca, 'modelo' => $ativo->modelo, 'cidade_id' => $ativo->cidade_id, 'localizacao' => $ativo->localizacao,
        ...$sobrescrever,
    ];
}

it('pede confirmação antes de tirar de operação um ativo com pendentes', function () {
    $this->get(route('ativos.edit', $this->ativo))->assertSee('12 manutenções pendentes serão canceladas');

    $this->put(route('ativos.update', $this->ativo), dadosDoAtivo($this->ativo, ['status' => 'baixado']))
        ->assertSessionHasErrors('confirmar_cancelamento');

    expect($this->ativo->fresh()->status)->toBe(StatusAtivo::EmOperacao)
        ->and($this->plano->manutencoes()->pendentes()->count())->toBe(12);
});

it('ao dar baixa, inativa os planos e cancela todas as pendentes', function () {
    $this->put(route('ativos.update', $this->ativo), dadosDoAtivo($this->ativo, ['status' => 'baixado', 'confirmar_cancelamento' => '1']))
        ->assertSessionHasNoErrors();

    $atrasada = $this->plano->manutencoes()->where('data_prevista', '2026-01-10')->first();
    expect($this->plano->fresh()->ativo)->toBeFalse()
        ->and($this->ativo->manutencoes()->pendentes()->count())->toBe(0)
        ->and($atrasada->status)->toBe(StatusManutencao::Cancelada)
        ->and($atrasada->motivo_cancelamento)->toBe('Ativo com status alterado para Baixado.')
        ->and(Auditoria::where('evento', 'ocorrencias_canceladas')->where('auditavel_type', 'ativo')->where('auditavel_id', $this->ativo->id)->value('depois'))
        ->toMatchArray(['quantidade' => 12]);
});

it('ao excluir o ativo, cancela as pendentes', function () {
    $this->delete(route('ativos.destroy', $this->ativo))->assertRedirect(route('ativos.index'));

    expect($this->ativo->manutencoes()->pendentes()->count())->toBe(0)
        ->and($this->ativo->manutencoes()->where('motivo_cancelamento', 'Ativo excluído.')->count())->toBe(12)
        ->and($this->plano->fresh()->ativo)->toBeFalse();
});

it('não pede confirmação quando não há pendentes', function () {
    $semPlanos = Ativo::factory()->create(['cidade_id' => $this->ativo->cidade_id]);

    $this->put(route('ativos.update', $semPlanos), dadosDoAtivo($semPlanos, ['status' => 'inativo']))->assertSessionHasNoErrors();

    expect($semPlanos->fresh()->status)->toBe(StatusAtivo::Inativo);
});
