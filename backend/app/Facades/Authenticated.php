<?php

namespace App\Facades;

use App\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static User user()
 *
 * @see \App\Service\Authenticated
 */
class Authenticated extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Service\Authenticated::class;
    }
}
