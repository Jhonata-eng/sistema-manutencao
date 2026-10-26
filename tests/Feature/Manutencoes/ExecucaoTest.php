<?php

use App\Enums\StatusManutencao;
use App\Enums\TipoAnexo;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\Fornecedor;
use App\Models\Manutencao;
use App\Models\PlanoManutencao;
use App\Services\AnexoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(Carbon::parse('2026-03-15 10:00'));
    $this->cidade = Cidade::factory()->create();
    $this->fornecedor = Fornecedor::factory()->create(['nome' => 'Refrigeração Alfa']);
    $this->plano = PlanoManutencao::factory()->create([
        'ativo_id' => Ativo::factory()->create(['cidade_id' => $this->cidade->id])->id,
        'descricao' => 'Limpeza de filtros',
        'valor_estimado' => '100.00',
        'fornecedor_id' => $this->fornecedor->id,
    ]);
    $this->prevista = Manutencao::factory()->doPlano($this->plano, '2026-03-10')->create();
    $this->usuario = usuarioCom(['manutencoes.visualizar', 'manutencoes.registrar_execucao', 'anexos.enviar'], $this->cidade);
    $this->actingAs($this->usuario);
});

function dadosExecucao(array $sobrescrever = []): array
{
    return [
        'data_execucao' => '2026-03-14',
        'valor_real' => '135,90',
        'fornecedor_id' => test()->fornecedor->id,
        'descricao' => 'Limpeza feita, filtro trocado',
        ...$sobrescrever,
    ];
}

it('mostra o formulário com valor, fornecedor e serviço do plano pré-preenchidos', function () {
    $this->get(route('manutencoes.execucao.create', $this->prevista))
        ->assertOk()
        ->assertSee('value="100,00"', false)
        ->assertSee('<option value="'.$this->fornecedor->id.'" selected>', false)
        ->assertSee('Limpeza de filtros');
});

it('registra a execução com nota fiscal anexada e auditoria', function () {
    $this->post(route('manutencoes.execucao.store', $this->prevista), dadosExecucao([
        'anexo_arquivo' => arquivoFalso('pdf', 'nf-123.pdf'),
        'anexo_tipo' => 'nota_fiscal',
        'anexo_numero_nota' => '123',
        'anexo_data_emissao' => '2026-03-14',
        'anexo_valor_nota' => '135,90',
    ]))->assertRedirect(route('manutencoes.show', $this->prevista));

    $manutencao = $this->prevista->fresh(['anexos']);
    $anexo = $manutencao->anexos->first();
    expect($manutencao->status)->toBe(StatusManutencao::Realizada)
        ->and($manutencao->valor_real)->toBe('135.90')
        ->and($manutencao->data_execucao->toDateString())->toBe('2026-03-14')
        ->and($manutencao->registrado_por)->toBe($this->usuario->id)
        ->and($manutencao->semComprovante())->toBeFalse()
        ->and($anexo->tipo)->toBe(TipoAnexo::NotaFiscal)
        ->and($anexo->mime)->toBe('application/pdf')
        ->and($anexo->valor_nota)->toBe('135.90')
        ->and($anexo->nome_original)->toBe('nf-123.pdf')
        ->and($anexo->caminho)->toStartWith('anexos/2026/03/')
        ->and(Auditoria::where('auditavel_type', 'manutencao')->where('auditavel_id', $manutencao->id)->pluck('evento')->all())
        ->toContain('executada', 'anexo_enviado');
    Storage::disk('local')->assertExists($anexo->caminho);
});

it('sem nota ou recibo fica "sem comprovante"; laudo não conta como comprovante', function () {
    $this->post(route('manutencoes.execucao.store', $this->prevista), dadosExecucao())->assertSessionHasNoErrors();
    $manutencao = $this->prevista->fresh();
    expect($manutencao->load('anexos')->semComprovante())->toBeTrue()
        ->and(Manutencao::semComprovanteAnexado()->pluck('id')->all())->toBe([$manutencao->id]);

    $anexos = app(AnexoService::class);
    $anexos->enviar($manutencao, ['arquivo' => arquivoFalso('pdf'), 'tipo' => 'laudo'], $this->usuario);
    expect($manutencao->load('anexos')->semComprovante())->toBeTrue();

    $anexos->enviar($manutencao, ['arquivo' => arquivoFalso('jpg'), 'tipo' => 'recibo'], $this->usuario);
    expect($manutencao->load('anexos')->semComprovante())->toBeFalse()
        ->and(Manutencao::semComprovanteAnexado()->count())->toBe(0);
});

it('valida a execução', function (array $dados, string $campo) {
    $this->post(route('manutencoes.execucao.store', $this->prevista), dadosExecucao($dados))->assertSessionHasErrors($campo);

    expect($this->prevista->fresh()->status)->toBe(StatusManutencao::Prevista);
})->with([
    'data futura' => [['data_execucao' => '2026-03-16'], 'data_execucao'],
    'valor inválido' => [['valor_real' => 'abc'], 'valor_real'],
    'sem descrição' => [['descricao' => ''], 'descricao'],
    'anexo sem tipo' => [['anexo_arquivo' => arquivoFalso('pdf')], 'anexo_tipo'],
]);

it('não registra execução de manutenção já realizada, de outra cidade ou sem permissão', function () {
    $realizada = Manutencao::factory()->doPlano($this->plano, '2026-02-10')->realizada('90.00')->create();
    $outraCidade = Manutencao::factory()->create();

    $this->post(route('manutencoes.execucao.store', $realizada), dadosExecucao())->assertForbidden();
    $this->get(route('manutencoes.execucao.create', $outraCidade))->assertForbidden();
    $this->post(route('manutencoes.execucao.store', $outraCidade), dadosExecucao())->assertForbidden();

    $this->actingAs(usuarioCom(['manutencoes.visualizar'], $this->cidade))
        ->post(route('manutencoes.execucao.store', $this->prevista), dadosExecucao())
        ->assertForbidden();
});

it('quem não pode enviar anexos não anexa arquivo na execução', function () {
    $this->actingAs(usuarioCom(['manutencoes.visualizar', 'manutencoes.registrar_execucao'], $this->cidade));

    $this->get(route('manutencoes.execucao.create', $this->prevista))->assertDontSee('name="anexo_arquivo"', false);
    $this->post(route('manutencoes.execucao.store', $this->prevista), dadosExecucao([
        'anexo_arquivo' => arquivoFalso('pdf'), 'anexo_tipo' => 'nota_fiscal',
    ]))->assertSessionHasErrors('anexo_arquivo');
});
