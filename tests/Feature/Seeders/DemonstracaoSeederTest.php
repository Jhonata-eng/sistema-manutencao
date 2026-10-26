<?php

use App\Enums\StatusManutencao;
use App\Enums\TipoManutencao;
use App\Models\Ativo;
use App\Models\Manutencao;
use App\Models\User;
use Database\Seeders\DemonstracaoSeeder;
use Illuminate\Support\Facades\Storage;

it('carrega um histórico variado uma única vez', function () {
    Storage::fake('local');
    User::factory()->administrador()->create();

    $this->seed(DemonstracaoSeeder::class);
    $manutencoes = Manutencao::count();

    expect(Ativo::where('patrimonio', 'like', 'DEMO-%')->count())->toBe(20)
        ->and(Manutencao::where('status', StatusManutencao::Realizada)->count())->toBeGreaterThan(50)
        ->and(Manutencao::atrasadas()->count())->toBeGreaterThan(0)
        ->and(Manutencao::pendentesFuturas()->count())->toBeGreaterThan(0)
        ->and(Manutencao::where('tipo', TipoManutencao::NaoProgramada)->count())->toBe(16)
        ->and(Manutencao::semComprovanteAnexado()->count())->toBeGreaterThan(0)
        ->and(User::where('email', 'tecnico.demo@exemplo.com.br')->first()->cidades)->toHaveCount(1);

    $this->seed(DemonstracaoSeeder::class);
    expect(Manutencao::count())->toBe($manutencoes);
});

it('não carrega nada sem um administrador', function () {
    Storage::fake('local');

    $this->seed(DemonstracaoSeeder::class);

    expect(Ativo::count())->toBe(0);
});
