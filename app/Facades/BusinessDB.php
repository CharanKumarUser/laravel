<?php
declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Database\Query\Builder table(string $table)
 * @method static \Illuminate\Database\Connection getConnection()
 */
class BusinessDB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'business.db';
    }
}
