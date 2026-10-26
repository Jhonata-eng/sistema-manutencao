<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /** Roda o DatabaseSeeder (permissões e perfis padrão) uma vez, junto com as migrations. */
    protected $seed = true;

    /**
     * Os testes recriam o banco (RefreshDatabase). Com o cache de configuração ativo (`php artisan optimize`),
     * o phpunit.xml é ignorado e o banco seria o de desenvolvimento ou de produção: aborta antes de tocar nele.
     */
    protected function setUpTraits()
    {
        $banco = (string) config('database.connections.'.config('database.default').'.database');

        if (! str_ends_with($banco, '_testes')) {
            throw new RuntimeException(
                "Os testes só rodam num banco *_testes, mas o banco configurado é \"{$banco}\". "
                .'Se o cache de configuração estiver ativo, rode `php artisan config:clear`.'
            );
        }

        return parent::setUpTraits();
    }
}
