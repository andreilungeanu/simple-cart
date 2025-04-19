<?php

namespace AndreiLungeanu\SimpleCart;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AndreiLungeanu\SimpleCart\Commands\SimpleCartCommand;

class SimpleCartServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('simple-cart')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_simple_cart_table')
            ->hasCommand(SimpleCartCommand::class);
    }
}
