<?php

use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('envia os cabeçalhos de segurança e a CSP nas páginas HTML', function () {
    $this->get(route('login'))
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Content-Security-Policy');

    expect($this->get(route('login'))->headers->get('Content-Security-Policy'))
        ->toContain("script-src 'self'")
        ->toContain("frame-ancestors 'none'")
        ->not->toContain('unsafe-eval');
});

it('não aplica CSP a arquivos servidos (PDF e imagens abrem no navegador)', function () {
    Storage::fake('local');
    $cidade = Cidade::factory()->create();
    $this->actingAs(usuarioCom(['ativos.visualizar', 'ativos.criar'], $cidade));
    $ativo = Ativo::factory()->create(['cidade_id' => $cidade->id, 'foto_path' => 'ativos/fotos/x.png']);
    Storage::disk('local')->put('ativos/fotos/x.png', file_get_contents(imagemFalsa()->getPathname()));

    $resposta = $this->get(route('ativos.foto', $ativo))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($resposta->headers->has('Content-Security-Policy'))->toBeFalse();
});

it('mostra páginas de erro em português, sem detalhes internos', function () {
    $this->actingAs(User::factory()->comPermissoes()->create());

    $this->get(route('usuarios.index'))->assertForbidden()->assertSee('Acesso negado');
    $this->get('/rota-que-nao-existe')->assertNotFound()->assertSee('Página não encontrada');
});

it('não expõe o disco privado por URL', function () {
    $this->get('/storage/anexos/2026/10/qualquer.pdf')->assertNotFound();
});
