<?php

use App\Http\Controllers\AnexoController;
use App\Http\Controllers\AtivoController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Cadastros\CidadeController;
use App\Http\Controllers\Cadastros\FornecedorController;
use App\Http\Controllers\Cadastros\ResponsavelController;
use App\Http\Controllers\Cadastros\TipoEquipamentoController;
use App\Http\Controllers\CorrecaoValorController;
use App\Http\Controllers\ExecucaoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\ManutencaoController;
use App\Http\Controllers\MinhaSenhaController;
use App\Http\Controllers\NaoProgramadaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlanoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', InicioController::class)->name('inicio');

    Route::get('minha-senha', [MinhaSenhaController::class, 'edit'])->name('minha-senha.edit');
    Route::put('minha-senha', [MinhaSenhaController::class, 'update'])->name('minha-senha.update');

    // Ativos: permissão + escopo por cidade na AtivoPolicy.
    Route::resource('ativos', AtivoController::class)->parameters(['ativos' => 'ativo']);
    Route::get('ativos/{ativo}/foto', [AtivoController::class, 'foto'])->name('ativos.foto');

    // Planos: criados dentro do ativo, demais rotas rasas (planos/{plano}); permissão + cidade na PlanoManutencaoPolicy.
    Route::resource('ativos.planos', PlanoController::class)->shallow()->except('index')->parameters(['planos' => 'plano']);
    Route::post('planos/{plano}/atualizar-valor-medio', [PlanoController::class, 'atualizarValorMedio'])->name('planos.atualizar-valor-medio');

    // Visão unificada, calendário e exportação: escopo de cidades aplicado pelo FiltroManutencoes.
    Route::middleware('can:manutencoes.visualizar')->group(function () {
        Route::get('manutencoes', [ManutencaoController::class, 'index'])->name('manutencoes.index');
        Route::get('manutencoes/calendario', [ManutencaoController::class, 'calendario'])->name('manutencoes.calendario');
        Route::get('manutencoes/exportar', [ManutencaoController::class, 'exportar'])->middleware('can:relatorios.exportar')->name('manutencoes.exportar');
    });

    // Manutenções: permissão + cidade + status exigido na ManutencaoPolicy (via #[Authorize] ou Form Request).
    Route::middleware('can:manutencoes.lancar_nao_programada')->group(function () {
        Route::get('manutencoes/nao-programada', [NaoProgramadaController::class, 'create'])->name('manutencoes.nao-programada.create');
        Route::post('manutencoes/nao-programada', [NaoProgramadaController::class, 'store'])->name('manutencoes.nao-programada.store');
    });
    Route::prefix('manutencoes/{manutencao}')->name('manutencoes.')->whereNumber('manutencao')->group(function () {
        Route::get('/', [ManutencaoController::class, 'show'])->name('show');
        Route::post('cancelamento', [ManutencaoController::class, 'cancelar'])->name('cancelar');
        Route::post('reagendamento', [ManutencaoController::class, 'reagendar'])->name('reagendar');
        Route::get('execucao', [ExecucaoController::class, 'create'])->name('execucao.create');
        Route::post('execucao', [ExecucaoController::class, 'store'])->name('execucao.store');
        Route::get('correcao-valor', [CorrecaoValorController::class, 'create'])->name('correcao-valor.create');
        Route::post('correcao-valor', [CorrecaoValorController::class, 'store'])->name('correcao-valor.store');
        Route::post('anexos', [AnexoController::class, 'store'])->name('anexos.store');
    });
    Route::get('anexos/{anexo}', [AnexoController::class, 'show'])->name('anexos.show');
    Route::delete('anexos/{anexo}', [AnexoController::class, 'destroy'])->name('anexos.destroy');

    Route::middleware('can:cadastros.gerenciar')->prefix('cadastros')->name('cadastros.')->group(function () {
        Route::resource('cidades', CidadeController::class)->except('show')->parameters(['cidades' => 'cidade']);
        Route::resource('tipos', TipoEquipamentoController::class)->except('show')->parameters(['tipos' => 'tipo']);
        Route::resource('responsaveis', ResponsavelController::class)->except('show')->parameters(['responsaveis' => 'responsavel']);
        Route::resource('fornecedores', FornecedorController::class)->except('show')->parameters(['fornecedores' => 'fornecedor']);
    });

    Route::middleware('can:usuarios.gerenciar')->group(function () {
        Route::resource('usuarios', UsuarioController::class)
            ->except(['show', 'destroy'])
            ->parameters(['usuarios' => 'usuario']);
        Route::get('usuarios/{usuario}/senha', [UsuarioController::class, 'editarSenha'])->name('usuarios.senha.edit');
        Route::put('usuarios/{usuario}/senha', [UsuarioController::class, 'redefinirSenha'])->name('usuarios.senha.update');
    });

    Route::middleware('can:perfis.gerenciar')->group(function () {
        Route::resource('perfis', PerfilController::class)
            ->except('show')
            ->parameters(['perfis' => 'perfil']);
    });

    Route::middleware('can:auditoria.visualizar')->group(function () {
        Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('auditoria/{auditoria}', [AuditoriaController::class, 'show'])->name('auditoria.show');
    });
});
