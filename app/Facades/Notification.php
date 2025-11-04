<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the NotificationService.
 */
class Notification extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\NotificationService::class;
    }
}