<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Roda o DatabaseSeeder (permissões e perfis padrão) uma vez, junto com as migrations. */
    protected $seed = true;
}
