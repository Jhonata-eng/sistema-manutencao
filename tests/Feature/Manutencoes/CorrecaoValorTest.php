<?php

use App\Models\Anexo;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\CorrecaoValor;
use App\Models\Manutencao;
use App\Services\AnexoService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->cidade = Cidade::factory()->create();
    $this->manutencao = Manutencao::factory()->realizada('100.00')->create([
        'ativo_id' => Ativo::factory()->create(['cidade_id' => $this->cidade->id])->id,
    ]);
    $this->usuario = usuarioCom(['manutencoes.visualizar', 'manutencoes.corrigir_valor'], $this->cidade);
    $this->actingAs($this->usuario);
    $this->recibo = app(AnexoService::class)->enviar($this->manutencao, ['arquivo' => arquivoFalso('pdf', 'recibo.pdf'), 'tipo' => 'recibo'], $this->usuario);
});

function corrigirValor(array $dados)
{
    return test()->post(route('manutencoes.correcao-valor.store', test()->manutencao), ['valor_novo' => '180,00', 'motivo' => 'Nota com valor maior', ...$dados]);
}

it('corrige o valor com um novo comprovante e grava o histórico', function () {
    corrigirValor([
        'anexo_arquivo' => arquivoFalso('pdf', 'nf-999.pdf'),
        'anexo_tipo' => 'nota_fiscal',
        'anexo_numero_nota' => '999',
    ])->assertRedirect(route('manutencoes.show', $this->manutencao));

    $correcao = CorrecaoValor::first();
    $notaNova = Anexo::where('nome_original', 'nf-999.pdf')->first();
    expect($this->manutencao->fresh()->valor_real)->toBe('180.00')
        ->and($correcao->valor_anterior)->toBe('100.00')
        ->and($correcao->valor_novo)->toBe('180.00')
        ->and($correcao->motivo)->toBe('Nota com valor maior')
        ->and($correcao->anexo_id)->toBe($notaNova->id)
        ->and($correcao->user_id)->toBe($this->usuario->id)
        ->and($correcao->created_at)->not->toBeNull();

    $registro = Auditoria::where('evento', 'valor_corrigido')->where('auditavel_id', $this->manutencao->id)->first();
    expect($registro->antes)->toBe(['valor_real' => '100.00'])
        ->and($registro->depois['valor_real'])->toBe('180.00')
        ->and($registro->motivo)->toBe('Nota com valor maior');

    $this->get(route('manutencoes.show', $this->manutencao))
        ->assertSee('Correções de valor')
        ->assertSeeInOrder(['R$ 100,00', 'R$ 180,00', 'Nota com valor maior', 'Nota fiscal nº 999']);
});

it('aceita um comprovante já anexado', function () {
    corrigirValor(['comprovante_id' => $this->recibo->id])->assertSessionHasNoErrors();

    expect(CorrecaoValor::first()->anexo_id)->toBe($this->recibo->id)
        ->and(Anexo::count())->toBe(1);
});

it('mantém o histórico de várias correções', function () {
    corrigirValor(['comprovante_id' => $this->recibo->id]);
    corrigirValor(['valor_novo' => '150,00', 'motivo' => 'Desconto concedido', 'comprovante_id' => $this->recibo->id]);

    expect(CorrecaoValor::orderBy('id')->get(['valor_anterior', 'valor_novo'])->toArray())->toBe([
        ['valor_anterior' => '100.00', 'valor_novo' => '180.00'],
        ['valor_anterior' => '180.00', 'valor_novo' => '150.00'],
    ])->and($this->manutencao->fresh()->valor_real)->toBe('150.00');
});

it('exige motivo e comprovante', function () {
    corrigirValor(['motivo' => '', 'comprovante_id' => $this->recibo->id])->assertSessionHasErrors('motivo');
    corrigirValor([])->assertSessionHasErrors(['comprovante_id', 'anexo_arquivo']);

    expect(CorrecaoValor::count())->toBe(0)
        ->and($this->manutencao->fresh()->valor_real)->toBe('100.00');
});

it('só aceita nota fiscal ou recibo desta manutenção como comprovante', function () {
    $laudo = app(AnexoService::class)->enviar($this->manutencao, ['arquivo' => arquivoFalso('pdf'), 'tipo' => 'laudo'], $this->usuario);
    $reciboDeOutra = Anexo::factory()->create(['tipo' => 'recibo']);

    corrigirValor(['comprovante_id' => $laudo->id])->assertSessionHasErrors('comprovante_id');
    corrigirValor(['comprovante_id' => $reciboDeOutra->id])->assertSessionHasErrors('comprovante_id');
    corrigirValor(['anexo_arquivo' => arquivoFalso('pdf'), 'anexo_tipo' => 'laudo'])->assertSessionHasErrors('anexo_tipo');
    corrigirValor(['comprovante_id' => $this->recibo->id, 'anexo_arquivo' => arquivoFalso('pdf'), 'anexo_tipo' => 'recibo'])
        ->assertSessionHasErrors('anexo_arquivo');

    expect(CorrecaoValor::count())->toBe(0);
});

it('recusa valor igual ao atual', function () {
    corrigirValor(['valor_novo' => '100,00', 'comprovante_id' => $this->recibo->id])->assertSessionHasErrors('valor_novo');
});

it('exige manutencoes.corrigir_valor, manutenção realizada e da cidade do usuário', function () {
    $prevista = Manutencao::factory()->create(['ativo_id' => $this->manutencao->ativo_id]);
    $outraCidade = Manutencao::factory()->realizada('10.00')->create();

    $this->get(route('manutencoes.correcao-valor.create', $prevista))->assertForbidden();
    $this->get(route('manutencoes.correcao-valor.create', $outraCidade))->assertForbidden();

    $this->actingAs(usuarioCom(['manutencoes.visualizar', 'anexos.enviar'], $this->cidade));
    $this->get(route('manutencoes.show', $this->manutencao))->assertDontSee(route('manutencoes.correcao-valor.create', $this->manutencao));
    corrigirValor(['comprovante_id' => $this->recibo->id])->assertForbidden();
});
