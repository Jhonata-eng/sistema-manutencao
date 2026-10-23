<?php

use App\Enums\StatusAtivo;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\TipoEquipamento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->cidade = Cidade::factory()->create(['nome' => 'Campinas', 'uf' => 'SP']);
    $this->tipo = TipoEquipamento::factory()->create(['nome' => 'Ar-condicionado split']);
    $this->usuario = usuarioCom(['ativos.visualizar', 'ativos.criar', 'ativos.editar', 'ativos.excluir'], $this->cidade);
    $this->actingAs($this->usuario);
});

function dadosAtivo(array $sobrescrever = []): array
{
    return [
        'patrimonio' => 'PAT-0001',
        'tipo_equipamento_id' => test()->tipo->id,
        'status' => StatusAtivo::EmOperacao->value,
        'marca' => 'Carrier',
        'modelo' => 'XPower 12k',
        'numero_serie' => 'SN123',
        'capacidade' => '12.000 BTUs',
        'ano_fabricacao' => 2022,
        'data_aquisicao' => '2023-03-15',
        'cidade_id' => test()->cidade->id,
        'localizacao' => 'POP Centro, sala técnica',
        ...$sobrescrever,
    ];
}

it('cadastra ativo registrando quem cadastrou e a auditoria', function () {
    $this->post(route('ativos.store'), dadosAtivo())->assertSessionHasNoErrors();

    $ativo = Ativo::where('patrimonio', 'PAT-0001')->first();
    expect($ativo->criado_por)->toBe($this->usuario->id)
        ->and($ativo->status)->toBe(StatusAtivo::EmOperacao)
        ->and($ativo->data_aquisicao->format('Y-m-d'))->toBe('2023-03-15')
        ->and(Auditoria::where('evento', 'criado')->where('auditavel_type', 'ativo')->where('auditavel_id', $ativo->id)->exists())->toBeTrue();
});

it('mostra os detalhes do ativo', function () {
    $ativo = Ativo::factory()->create(['cidade_id' => $this->cidade->id, 'capacidade' => '150 kVA']);

    $this->get(route('ativos.show', $ativo))
        ->assertOk()
        ->assertSee($ativo->patrimonio)
        ->assertSee('150 kVA')
        ->assertSee('Campinas/SP');
});

it('guarda a foto fora da pasta pública e troca apagando a anterior', function () {
    $this->post(route('ativos.store'), dadosAtivo(['foto' => imagemFalsa()]))->assertSessionHasNoErrors();
    $ativo = Ativo::where('patrimonio', 'PAT-0001')->first();
    $primeira = $ativo->foto_path;

    expect($primeira)->toStartWith('ativos/fotos/');
    Storage::disk('local')->assertExists($primeira);
    $this->get(route('ativos.foto', $ativo))->assertOk();

    $this->put(route('ativos.update', $ativo), dadosAtivo(['foto' => imagemFalsa('nova.png')]))->assertSessionHasNoErrors();

    Storage::disk('local')->assertMissing($primeira);
    Storage::disk('local')->assertExists($ativo->fresh()->foto_path);
});

it('remove a foto quando pedido', function () {
    $this->post(route('ativos.store'), dadosAtivo(['foto' => imagemFalsa()]));
    $ativo = Ativo::where('patrimonio', 'PAT-0001')->first();
    $caminho = $ativo->foto_path;

    $this->put(route('ativos.update', $ativo), dadosAtivo(['remover_foto' => '1']))->assertSessionHasNoErrors();

    expect($ativo->fresh()->foto_path)->toBeNull();
    Storage::disk('local')->assertMissing($caminho);
});

it('recusa arquivo que não é imagem como foto', function () {
    $this->post(route('ativos.store'), dadosAtivo(['foto' => UploadedFile::fake()->create('foto.jpg', 10, 'application/pdf')]))
        ->assertSessionHasErrors('foto');
});

it('mantém o patrimônio único mesmo entre ativos excluídos', function () {
    Ativo::factory()->create(['patrimonio' => 'PAT-0001'])->delete();

    $this->post(route('ativos.store'), dadosAtivo())->assertSessionHasErrors('patrimonio');
});

it('não aceita tipo inativo em ativo novo, mas o mantém no ativo que já o usa', function () {
    $ativo = Ativo::factory()->create(['cidade_id' => $this->cidade->id, 'tipo_equipamento_id' => $this->tipo->id, 'patrimonio' => 'PAT-0002']);
    $this->tipo->update(['ativo' => false]);

    $this->post(route('ativos.store'), dadosAtivo())->assertSessionHasErrors('tipo_equipamento_id');
    $this->put(route('ativos.update', $ativo), dadosAtivo(['patrimonio' => 'PAT-0002', 'marca' => 'Daikin']))->assertSessionHasNoErrors();

    expect($ativo->fresh()->marca)->toBe('Daikin');
});

it('filtra por busca, tipo e status', function () {
    Ativo::factory()->create(['cidade_id' => $this->cidade->id, 'patrimonio' => 'GER-100', 'status' => StatusAtivo::Baixado]);
    Ativo::factory()->create(['cidade_id' => $this->cidade->id, 'patrimonio' => 'AC-200', 'tipo_equipamento_id' => $this->tipo->id]);

    $this->get(route('ativos.index', ['busca' => 'GER']))->assertSee('GER-100')->assertDontSee('AC-200');
    $this->get(route('ativos.index', ['tipo' => $this->tipo->id]))->assertSee('AC-200')->assertDontSee('GER-100');
    $this->get(route('ativos.index', ['status' => 'baixado']))->assertSee('GER-100')->assertDontSee('AC-200');
});

it('exclui logicamente: some da lista, continua no banco e é auditado', function () {
    $ativo = Ativo::factory()->create(['cidade_id' => $this->cidade->id]);

    $this->delete(route('ativos.destroy', $ativo))->assertRedirect(route('ativos.index'));

    expect(Ativo::find($ativo->id))->toBeNull()
        ->and(Ativo::withTrashed()->find($ativo->id))->not->toBeNull()
        ->and(Auditoria::where('evento', 'excluido')->where('auditavel_type', 'ativo')->where('auditavel_id', $ativo->id)->exists())->toBeTrue();
    $this->get(route('ativos.index'))->assertDontSee(route('ativos.show', $ativo));
    $this->get(route('ativos.show', $ativo))->assertNotFound();
});

it('respeita as permissões de criar, editar e excluir', function () {
    $ativo = Ativo::factory()->create(['cidade_id' => $this->cidade->id]);
    $this->actingAs(usuarioCom(['ativos.visualizar'], $this->cidade));

    $this->get(route('ativos.index'))->assertOk()->assertDontSee(route('ativos.create'));
    $this->get(route('ativos.show', $ativo))->assertOk()->assertDontSee(route('ativos.edit', $ativo));
    $this->get(route('ativos.create'))->assertForbidden();
    $this->post(route('ativos.store'), dadosAtivo())->assertForbidden();
    $this->get(route('ativos.edit', $ativo))->assertForbidden();
    $this->delete(route('ativos.destroy', $ativo))->assertForbidden();
});

it('exige ativos.visualizar para a listagem', function () {
    $this->actingAs(usuarioCom(['dashboard.visualizar'], $this->cidade))
        ->get(route('ativos.index'))
        ->assertForbidden();
});
