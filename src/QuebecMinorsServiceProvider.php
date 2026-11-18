<?php

namespace JeffersonGoncalves\QuebecMinors;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QuebecMinorsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-quebec-minors')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
