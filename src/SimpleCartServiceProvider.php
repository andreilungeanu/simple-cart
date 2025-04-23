<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\Listeners\CartEventSubscriber;
use AndreiLungeanu\SimpleCart\Repositories\CartRepository;
use AndreiLungeanu\SimpleCart\Repositories\DatabaseCartRepository;
use AndreiLungeanu\SimpleCart\Services\CartCalculator; // Import CartCalculator
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\Services\DefaultTaxProvider;
use AndreiLungeanu\SimpleCart\Services\DiscountCalculator; // Import DiscountCalculator
use AndreiLungeanu\SimpleCart\Services\ShippingCalculator;
use AndreiLungeanu\SimpleCart\Services\TaxCalculator; // Import TaxCalculator
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
        // Explicitly register TaxCalculator
        $this->app->bind(TaxCalculator::class, function ($app) {
            return new TaxCalculator($app->make(TaxRateProvider::class));
        });
        // Explicitly register DiscountCalculator (no dependencies)
        $this->app->bind(DiscountCalculator::class, DiscountCalculator::class);

        // Register the new CartCalculator with its dependencies
        $this->app->bind(CartCalculator::class, function ($app) {
            return new CartCalculator(
                $app->make(ShippingCalculator::class),
                $app->make(TaxCalculator::class),
                $app->make(DiscountCalculator::class),
                $app->make(TaxRateProvider::class)
            );
        });

        // Register facade - Inject CartCalculator and Actions now
        $this->app->singleton('simple-cart', function ($app) {
            return new SimpleCart(
                $app->make(CartRepository::class),
                $app->make(CartCalculator::class),
                $app->make(\AndreiLungeanu\SimpleCart\Actions\AddItemToCartAction::class) // Action has no deps now
            );
        });
    }

    public function packageBooted(): void
    {
        $this->app['events']->subscribe(CartEventSubscriber::class);
    }
}
