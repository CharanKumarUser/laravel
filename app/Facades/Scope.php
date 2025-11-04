<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Scope.
 */
class Scope extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\ScopeService::class;
    }
}