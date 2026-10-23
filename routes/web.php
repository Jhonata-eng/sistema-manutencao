<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Auth\LoginController;
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
