<?php

use App\Models\Anexo;
use App\Models\Ativo;
use App\Models\Auditoria;
use App\Models\Cidade;
use App\Models\Fornecedor;
use App\Models\Manutencao;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\PlanoManutencao;
use App\Models\Responsavel;
use App\Models\TipoEquipamento;
use App\Models\User;
use Illuminate\Routing\Route as Rota;
use Illuminate\Support\Facades\Route;

/*
 * Varre TODAS as rotas autenticadas registradas, para que uma rota nova sem autorização
 * falhe aqui mesmo que ninguém tenha escrito um teste específico para ela.
 */

/** Rotas que qualquer usuário logado pode usar. */
const ROTAS_DE_QUALQUER_USUARIO = ['inicio', 'logout', 'minha-senha.edit', 'minha-senha.update'];

/** Parâmetros de rota que identificam um registro de uma cidade. */
const PARAMETROS_COM_CIDADE = ['ativo', 'plano', 'manutencao', 'anexo', 'auditoria', 'usuario'];

beforeEach(function () {
    $this->cidade = Cidade::factory()->create();
    $ativo = Ativo::factory()->create(['cidade_id' => $this->cidade->id]);
    $plano = PlanoManutencao::factory()->create(['ativo_id' => $ativo->id]);
    $manutencao = Manutencao::factory()->doPlano($plano, today()->toDateString())->create();
    $outroUsuario = User::factory()->create();
    $outroUsuario->cidades()->attach($this->cidade);

    $this->parametros = [
        'ativo' => $ativo->id,
        'plano' => $plano->id,
        'manutencao' => $manutencao->id,
        'anexo' => Anexo::factory()->create(['manutencao_id' => Manutencao::factory()->realizada('10.00')->create(['ativo_id' => $ativo->id])->id])->id,
        'auditoria' => Auditoria::where('auditavel_type', 'ativo')->where('auditavel_id', $ativo->id)->value('id'),
        'usuario' => $outroUsuario->id,
        'perfil' => Perfil::factory()->create()->id,
        'cidade' => $this->cidade->id,
        'tipo' => TipoEquipamento::factory()->create()->id,
        'responsavel' => Responsavel::factory()->create()->id,
        'fornecedor' => Fornecedor::factory()->create()->id,
    ];
});

/** @return list<array{string, string, Rota}> [método, nome, rota] das rotas protegidas por login */
function rotasAutenticadas(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (Rota $rota) => $rota->getName() && in_array('auth', $rota->gatherMiddleware(), true))
        ->flatMap(fn (Rota $rota) => collect($rota->methods())
            ->reject(fn (string $metodo) => in_array($metodo, ['HEAD', 'PATCH'], true))
            ->map(fn (string $metodo) => [$metodo, $rota->getName(), $rota]))
        ->values()
        ->all();
}

function urlDaRota(Rota $rota, array $parametros): string
{
    return route($rota->getName(), array_intersect_key($parametros, array_flip($rota->parameterNames())));
}

it('visitante é mandado para o login em todas as rotas autenticadas', function () {
    $rotas = rotasAutenticadas();
    expect(count($rotas))->toBeGreaterThan(60);

    foreach ($rotas as [$metodo, $nome, $rota]) {
        $this->call($metodo, urlDaRota($rota, $this->parametros))
            ->assertRedirect(route('login'), "{$metodo} {$nome} não exige login");
    }
});

it('usuário sem nenhuma permissão recebe 403 em todas as rotas restritas', function () {
    $this->actingAs(usuarioCom([], $this->cidade));

    foreach (rotasAutenticadas() as [$metodo, $nome, $rota]) {
        if (in_array($nome, ROTAS_DE_QUALQUER_USUARIO, true)) {
            continue;
        }

        expect($this->call($metodo, urlDaRota($rota, $this->parametros))->status())
            ->toBe(403, "{$metodo} {$nome} não exige permissão");
    }
});

it('usuário com todas as permissões, mas de outra cidade, recebe 403 nos registros da cidade', function () {
    $todasMenosVerTodasCidades = array_values(array_diff(array_keys(Permissao::CATALOGO), ['dados.ver_todas_cidades']));
    $this->actingAs(usuarioCom($todasMenosVerTodasCidades, Cidade::factory()->create()));
    $verificadas = 0;

    foreach (rotasAutenticadas() as [$metodo, $nome, $rota]) {
        if (array_intersect($rota->parameterNames(), PARAMETROS_COM_CIDADE) === []) {
            continue;
        }

        $verificadas++;
        expect($this->call($metodo, urlDaRota($rota, $this->parametros))->status())
            ->toBe(403, "{$metodo} {$nome} expõe registro de outra cidade");
    }

    expect($verificadas)->toBeGreaterThan(25);
});
