<?php

namespace App\Providers;

use App\Models\Auditoria;
use App\Models\Permissao;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Relation::enforceMorphMap(array_map(fn (array $tipo) => $tipo[0], Auditoria::TIPOS));

        foreach (array_keys(Permissao::CATALOGO) as $chave) {
            Gate::define($chave, fn (User $usuario) => $usuario->temPermissao($chave));
        }

        Password::defaults(fn () => Password::min(8));
        Paginator::useBootstrapFive();
        Route::resourceVerbs(['create' => 'novo', 'edit' => 'editar']);
    }
}
