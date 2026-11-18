<?php

namespace JeffersonGoncalves\QuebecMinors\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\QuebecMinors\QuebecMinors
 */
class QuebecMinors extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-quebec-minors';
    }
}
