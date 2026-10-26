<?php

use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\Manutencao;
use App\Models\PlanoManutencao;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-06-15 10:00'));
    $this->cidade = Cidade::factory()->create(['nome' => 'Campinas', 'uf' => 'SP']);
    $ativo = Ativo::factory()->create(['cidade_id' => $this->cidade->id, 'patrimonio' => 'CPS-01']);
    $plano = PlanoManutencao::factory()->create(['ativo_id' => $ativo->id, 'descricao' => 'Limpeza', 'valor_estimado' => '1234.50']);
    Manutencao::factory()->doPlano($plano, '2026-06-10')->realizada('1300.75', '2026-06-11')->create(['descricao' => '=SOMA(A1;A2)']);
    Manutencao::factory()->doPlano($plano, '2026-06-20')->create();
    Manutencao::factory()->doPlano($plano, '2026-07-10')->create();
    Manutencao::factory()->create(['data_prevista' => '2026-06-12', 'data_referencia' => '2026-06-12']); // outra cidade
});

function linhasCsv(string $conteudo): array
{
    return array_map(fn ($linha) => str_getcsv($linha, ';', '"', ''), explode("\n", trim(substr($conteudo, 3))));
}

it('exporta a lista filtrada no formato do Excel brasileiro', function () {
    $this->actingAs(usuarioCom(['manutencoes.visualizar', 'relatorios.exportar'], $this->cidade));

    $resposta = $this->get(route('manutencoes.exportar', ['mes' => '2026-06']))->assertOk();
    $conteudo = $resposta->streamedContent();
    $linhas = linhasCsv($conteudo);

    expect(substr($conteudo, 0, 3))->toBe("\xEF\xBB\xBF")
        ->and($resposta->headers->get('Content-Type'))->toBe('text/csv; charset=UTF-8')
        ->and($resposta->headers->get('Content-Disposition'))->toContain('attachment; filename=manutencoes-')
        ->and($linhas[0][0])->toBe('ID')
        ->and($linhas)->toHaveCount(3)                          // cabeçalho + 2 de junho (julho e outra cidade fora)
        ->and($linhas[1][1])->toBe('10/06/2026')
        ->and($linhas[1][2])->toBe('11/06/2026')
        ->and($linhas[1][3])->toBe('Realizada')
        ->and($linhas[1][5])->toBe('CPS-01')
        ->and($linhas[1][8])->toBe('Campinas/SP')
        ->and($linhas[1][10])->toBe("'=SOMA(A1;A2)")         // não vira fórmula no Excel
        ->and($linhas[1][13])->toBe('1234,50')
        ->and($linhas[1][14])->toBe('1300,75')
        ->and($linhas[1][15])->toBe('Sim')                      // sem comprovante
        ->and($linhas[2][3])->toBe('Prevista')
        ->and($linhas[2][14])->toBe('');
});

it('exige relatorios.exportar e esconde o botão de quem não tem', function () {
    $this->actingAs(usuarioCom(['manutencoes.visualizar'], $this->cidade));

    $this->get(route('manutencoes.index'))->assertDontSee(route('manutencoes.exportar'));
    $this->get(route('manutencoes.exportar'))->assertForbidden();
});
