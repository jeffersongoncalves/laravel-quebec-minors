<?php

namespace JeffersonGoncalves\QuebecMinors\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\PestPluginDatabaseMigrations\LoadsPackageMigrations;
use JeffersonGoncalves\QuebecMinors\QuebecMinorsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use LoadsPackageMigrations;

    protected function getPackageProviders($app): array
    {
        return [
            QuebecMinorsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations(QuebecMinorsServiceProvider::class);

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }
}
