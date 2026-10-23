<?php

use App\Http\Controllers\AtivoController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Cadastros\CidadeController;
use App\Http\Controllers\Cadastros\FornecedorController;
use App\Http\Controllers\Cadastros\ResponsavelController;
use App\Http\Controllers\Cadastros\TipoEquipamentoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MinhaSenhaController;
use App\Http\Controllers\PerfilController;
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
