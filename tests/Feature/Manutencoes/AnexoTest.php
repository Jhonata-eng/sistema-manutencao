<?php

use App\Models\Anexo;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\Manutencao;
use App\Services\AnexoService;
use App\Services\ManutencaoService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->cidade = Cidade::factory()->create();
    $this->manutencao = Manutencao::factory()->realizada('100.00')->create([
        'ativo_id' => Ativo::factory()->create(['cidade_id' => $this->cidade->id])->id,
    ]);
    $this->usuario = usuarioCom(['manutencoes.visualizar', 'anexos.enviar', 'anexos.excluir'], $this->cidade);
    $this->actingAs($this->usuario);
});

function enviarAnexo(array $dados)
{
    return test()->post(route('manutencoes.anexos.store', test()->manutencao), ['anexo_tipo' => 'nota_fiscal', ...$dados]);
}

it('aceita PDF, JPG, PNG e XML conferindo o conteúdo', function (string $tipo, string $mime) {
    enviarAnexo(['anexo_arquivo' => arquivoFalso($tipo)])->assertSessionHasNoErrors();

    $anexo = Anexo::first();
    expect($anexo->mime)->toBe($mime);
    Storage::disk('local')->assertExists($anexo->caminho);
})->with([
    'PDF' => ['pdf', 'application/pdf'],
    'JPG' => ['jpg', 'image/jpeg'],
    'PNG' => ['png', 'image/png'],
    'XML da NF-e' => ['xml', 'text/xml'],
]);

it('recusa conteúdo de tipo não aceito, mesmo com extensão permitida', function () {
    enviarAnexo(['anexo_arquivo' => arquivoFalso('txt', 'nota.pdf')])->assertSessionHasErrors('anexo_arquivo');

    expect(Anexo::count())->toBe(0);
});

it('recusa extensão não permitida, mesmo com conteúdo aceito', function () {
    enviarAnexo(['anexo_arquivo' => arquivoFalso('pdf', 'nota.exe')])->assertSessionHasErrors('anexo_arquivo');
});

it('respeita o tamanho máximo configurado', function () {
    config(['manutencao.anexo_tamanho_max_mb' => 1]);

    enviarAnexo(['anexo_arquivo' => arquivoFalso('pdf', 'grande.pdf', 2 * 1024 * 1024)])->assertSessionHasErrors('anexo_arquivo');
    enviarAnexo(['anexo_arquivo' => arquivoFalso('pdf', 'pequeno.pdf')])->assertSessionHasNoErrors();
});

it('entrega o arquivo só a quem pode ver a manutenção', function () {
    enviarAnexo(['anexo_arquivo' => arquivoFalso('pdf', 'nota.pdf')]);
    $anexo = Anexo::first();

    $this->get(route('anexos.show', $anexo))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename=nota.pdf');

    $this->actingAs(usuarioCom(['manutencoes.visualizar'], Cidade::factory()->create()))
        ->get(route('anexos.show', $anexo))->assertForbidden();

    auth()->logout();
    $this->get(route('anexos.show', $anexo))->assertRedirect(route('login'));
});

it('XML é sempre baixado, nunca aberto no navegador', function () {
    enviarAnexo(['anexo_arquivo' => arquivoFalso('xml', 'nfe.xml')]);

    $this->get(route('anexos.show', Anexo::first()))->assertHeader('Content-Disposition', 'attachment; filename=nfe.xml');
});

it('exclui com anexos.excluir, apaga o arquivo e audita', function () {
    enviarAnexo(['anexo_arquivo' => arquivoFalso('pdf', 'nota.pdf')]);
    $anexo = Anexo::first();

    $this->actingAs(usuarioCom(['manutencoes.visualizar', 'anexos.enviar'], $this->cidade))
        ->delete(route('anexos.destroy', $anexo))->assertForbidden();

    $this->actingAs($this->usuario)->delete(route('anexos.destroy', $anexo))->assertSessionHasNoErrors();

    expect(Anexo::count())->toBe(0)
        ->and(Auditoria::where('evento', 'anexo_excluido')->where('auditavel_id', $this->manutencao->id)->exists())->toBeTrue();
    Storage::disk('local')->assertMissing($anexo->caminho);
});

it('não exclui o comprovante de uma correção de valor', function () {
    $comprovante = app(AnexoService::class)->enviar($this->manutencao, ['arquivo' => arquivoFalso('pdf'), 'tipo' => 'recibo'], $this->usuario);
    app(ManutencaoService::class)->corrigirValor($this->manutencao, '150.00', 'Reajuste', $comprovante->id, null, $this->usuario);

    $this->delete(route('anexos.destroy', $comprovante))->assertSessionHasErrors('protecao');

    Storage::disk('local')->assertExists($comprovante->caminho);
});

it('só anexa em manutenção realizada e da cidade do usuário', function () {
    $prevista = Manutencao::factory()->create(['ativo_id' => $this->manutencao->ativo_id]);
    $outraCidade = Manutencao::factory()->realizada('10.00')->create();

    $this->post(route('manutencoes.anexos.store', $prevista), ['anexo_tipo' => 'recibo', 'anexo_arquivo' => arquivoFalso('pdf')])->assertForbidden();
    $this->post(route('manutencoes.anexos.store', $outraCidade), ['anexo_tipo' => 'recibo', 'anexo_arquivo' => arquivoFalso('pdf')])->assertForbidden();
});
