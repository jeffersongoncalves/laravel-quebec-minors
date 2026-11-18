<?php

namespace JeffersonGoncalves\QuebecMinors\Tests;

use JeffersonGoncalves\QuebecMinors\QuebecMinorsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            QuebecMinorsServiceProvider::class,
        ];
    }
}
