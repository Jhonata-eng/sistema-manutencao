<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Grava na auditoria a criação, a edição e a exclusão do model, com os valores antes/depois.
 * Atributos ocultos ($hidden, ex.: senha) e timestamps nunca são gravados. A exclusão lógica
 * (soft delete) também gera o evento "excluido".
 */
trait Auditavel
{
    public static function bootAuditavel(): void
    {
        static::created(function (Model $modelo) {
            Auditoria::registrar('criado', $modelo, [], $modelo->atributosAuditaveis($modelo->getAttributes()));
        });

        static::updated(function (Model $modelo) {
            $depois = $modelo->atributosAuditaveis($modelo->getChanges());

            if ($depois !== []) {
                Auditoria::registrar('editado', $modelo, array_intersect_key($modelo->getRawOriginal(), $depois), $depois);
            }
        });

        static::deleted(function (Model $modelo) {
            Auditoria::registrar('excluido', $modelo, $modelo->atributosAuditaveis($modelo->getRawOriginal()));
        });
    }

    /**
     * Sincroniza uma relação N–N e, se ela mudou, grava o evento com os rótulos antes/depois.
     * Pivôs não disparam eventos de model, por isso o registro é explícito.
     *
     * @param  Closure(Model): string  $rotulo
     */
    public function sincronizarAuditando(string $relacao, array $ids, string $evento, Closure $rotulo): void
    {
        $rotulos = fn () => $this->{$relacao}()->get()->map($rotulo)->sort()->values()->all();

        $antes = $rotulos();
        $this->{$relacao}()->sync($ids);
        $depois = $rotulos();

        if ($antes !== $depois) {
            Auditoria::registrar($evento, $this, [$relacao => $antes], [$relacao => $depois]);
        }
    }

    protected function atributosAuditaveis(array $atributos): array
    {
        return Arr::except($atributos, ['created_at', 'updated_at', 'deleted_at', ...$this->getHidden()]);
    }
}
