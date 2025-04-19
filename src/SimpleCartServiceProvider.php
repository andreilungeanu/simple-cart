<?php

namespace AndreiLungeanu\SimpleCart;


use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AndreiLungeanu\SimpleCart\Repositories\CartRepository;
use AndreiLungeanu\SimpleCart\Repositories\DatabaseCartRepository;
use AndreiLungeanu\SimpleCart\Listeners\CartEventSubscriber;
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\Services\ShippingCalculator;
use AndreiLungeanu\SimpleCart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\Services\DefaultTaxProvider;
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;

class SimpleCartServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('simple-cart')
            ->hasConfigFile()
            ->hasMigration('create_carts_table');
    }

    public function packageRegistered(): void
    {
        // Register repositories
        $this->app->singleton(CartRepository::class, DatabaseCartRepository::class);

        // Register shipping and tax providers
        $this->app->bind(ShippingRateProvider::class, DefaultShippingProvider::class);
        $this->app->bind(TaxRateProvider::class, DefaultTaxProvider::class);

        // Register calculators
        $this->app->bind(ShippingCalculator::class, function ($app) {
            return new ShippingCalculator($app->make(ShippingRateProvider::class));
        });

        // Register facade
        $this->app->singleton('simple-cart', function ($app) {
            return new SimpleCart($app->make(CartRepository::class));
        });
    }

    public function packageBooted(): void
    {
        $this->app['events']->subscribe(CartEventSubscriber::class);
    }
}
