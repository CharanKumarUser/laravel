<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Helper.
 */
class Profile extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Http\Helpers\ProfileHelper::class;
    }
}