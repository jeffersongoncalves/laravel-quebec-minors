<?php

namespace JeffersonGoncalves\QuebecMinors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use JeffersonGoncalves\QuebecMinors\Commands\MinorsCommand;
use JeffersonGoncalves\QuebecMinors\Http\Middleware\EnsureVerifiedAdult;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QuebecMinorsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('quebec-minors')
            ->hasConfigFile()
            ->hasMigrations(['create_quebec_minors_tables'])
            ->hasCommand(MinorsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(MinorsManager::class);
    }

    public function packageBooted(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('quebec.adult', EnsureVerifiedAdult::class);

        Blade::if('quebecProfiling', function (): bool {
            $user = auth()->user();

            return $this->app->make(MinorsManager::class)->allowsProfiling($user instanceof Model ? $user : null);
        });
    }
}
