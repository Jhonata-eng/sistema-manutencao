<?php

use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\Fornecedor;
use App\Models\Manutencao;

beforeEach(function () {
    $this->campinas = Cidade::factory()->create();
    $this->sorocaba = Cidade::factory()->create();
    $this->ativoCampinas = Ativo::factory()->create(['cidade_id' => $this->campinas->id]);
    $this->ativoSorocaba = Ativo::factory()->create(['cidade_id' => $this->sorocaba->id]);
    $this->ativoCampinas->update(['marca' => 'Marca Campinas']);
    $this->ativoSorocaba->update(['marca' => 'Marca Sorocaba']);
    $this->manutencaoSorocaba = Manutencao::factory()->create(['ativo_id' => $this->ativoSorocaba->id]);
    Auditoria::registrar('cancelada', $this->manutencaoSorocaba, [], [], 'Motivo de Sorocaba');
    Fornecedor::factory()->create(['nome' => 'Fornecedor Global']);
});

function idsAuditados(string $tipo): array
{
    return test()->get(route('auditoria.index', ['tipo' => $tipo]))->viewData('registros')->pluck('auditavel_id')->unique()->values()->all();
}

it('quem não vê todas as cidades só vê a auditoria de ativos, planos e manutenções das suas cidades', function () {
    $this->actingAs(usuarioCom(['auditoria.visualizar'], $this->campinas));

    expect(idsAuditados('ativo'))->toBe([$this->ativoCampinas->id])
        ->and(idsAuditados('manutencao'))->toBe([])
        ->and(idsAuditados('fornecedor'))->not->toBe([]);   // cadastros não pertencem a uma cidade

    $registroSorocaba = Auditoria::where('auditavel_type', 'ativo')->where('auditavel_id', $this->ativoSorocaba->id)->first();
    $this->get(route('auditoria.show', $registroSorocaba))->assertForbidden();
});

it('quem vê todas as cidades vê toda a auditoria', function () {
    $this->actingAs(usuarioCom(['auditoria.visualizar', 'dados.ver_todas_cidades']));

    expect(idsAuditados('ativo'))->toContain($this->ativoCampinas->id, $this->ativoSorocaba->id)
        ->and(idsAuditados('manutencao'))->toBe([$this->manutencaoSorocaba->id]);
});
