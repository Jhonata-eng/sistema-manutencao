<?php

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\Manutencao;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-03-15 10:00'));
    $this->campinas = Cidade::factory()->create(['nome' => 'Campinas', 'uf' => 'SP']);
    $this->sorocaba = Cidade::factory()->create(['nome' => 'Sorocaba', 'uf' => 'SP']);
    $this->ativoCampinas = Ativo::factory()->create(['cidade_id' => $this->campinas->id, 'patrimonio' => 'CPS-01', 'modelo' => 'XPower 12k']);
    $this->ativoSorocaba = Ativo::factory()->create(['cidade_id' => $this->sorocaba->id, 'patrimonio' => 'SOR-01', 'modelo' => 'XPower 18k']);
    $this->usuario = usuarioCom(['manutencoes.visualizar', 'manutencoes.lancar_nao_programada'], $this->campinas);
    $this->actingAs($this->usuario);
});

it('busca o ativo por patrimônio, modelo ou cidade só entre os acessíveis', function () {
    $this->get(route('manutencoes.nao-programada.create', ['busca' => 'XPower']))
        ->assertOk()->assertSee('CPS-01')->assertDontSee('SOR-01');
    $this->get(route('manutencoes.nao-programada.create', ['busca' => 'SOR-01']))
        ->assertSee('Nenhum ativo encontrado');
    $this->get(route('manutencoes.nao-programada.create', ['cidade' => $this->campinas->id]))
        ->assertSee('CPS-01');
    $this->get(route('manutencoes.nao-programada.create', ['ativo' => $this->ativoSorocaba->id]))
        ->assertNotFound();
});

it('lança a não programada já realizada, sem valor previsto', function () {
    $this->post(route('manutencoes.nao-programada.store'), [
        'ativo_id' => $this->ativoCampinas->id,
        'data_execucao' => '2026-03-12',
        'valor_real' => '480,00',
        'descricao' => 'Troca do compressor queimado',
    ])->assertSessionHasNoErrors();

    $manutencao = Manutencao::first();
    expect($manutencao->tipo)->toBe(TipoManutencao::NaoProgramada)
        ->and($manutencao->status)->toBe(StatusManutencao::Realizada)
        ->and($manutencao->ativo_id)->toBe($this->ativoCampinas->id)
        ->and($manutencao->plano_id)->toBeNull()
        ->and($manutencao->valor_previsto)->toBeNull()
        ->and($manutencao->data_prevista)->toBeNull()
        ->and($manutencao->valor_real)->toBe('480.00')
        ->and($manutencao->registrado_por)->toBe($this->usuario->id)
        ->and(Auditoria::where('evento', 'nao_programada_lancada')->where('auditavel_id', $manutencao->id)->exists())->toBeTrue();
});

it('recusa ativo de outra cidade, ativo excluído e data futura', function () {
    $dados = ['data_execucao' => '2026-03-12', 'valor_real' => '10,00', 'descricao' => 'X'];
    $excluido = Ativo::factory()->create(['cidade_id' => $this->campinas->id]);
    $excluido->delete();

    $this->post(route('manutencoes.nao-programada.store'), [...$dados, 'ativo_id' => $this->ativoSorocaba->id])->assertSessionHasErrors('ativo_id');
    $this->post(route('manutencoes.nao-programada.store'), [...$dados, 'ativo_id' => $excluido->id])->assertSessionHasErrors('ativo_id');
    $this->post(route('manutencoes.nao-programada.store'), [...$dados, 'ativo_id' => $this->ativoCampinas->id, 'data_execucao' => '2026-03-16'])
        ->assertSessionHasErrors('data_execucao');

    expect(Manutencao::count())->toBe(0);
});

it('exige manutencoes.lancar_nao_programada', function () {
    $this->actingAs(usuarioCom(['manutencoes.visualizar'], $this->campinas));

    $this->get(route('manutencoes.nao-programada.create'))->assertForbidden();
    $this->post(route('manutencoes.nao-programada.store'), ['ativo_id' => $this->ativoCampinas->id])->assertForbidden();
});
