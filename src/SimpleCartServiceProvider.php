<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\Cart\Actions\AddItemToCartAction;
use AndreiLungeanu\SimpleCart\Cart\CartManager;
use AndreiLungeanu\SimpleCart\Cart\Contracts\AddItemToCartActionInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\CartCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\CartManagerInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\CartRepository;
use AndreiLungeanu\SimpleCart\Cart\Contracts\DiscountCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\Cart\Contracts\TaxCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\Cart\Listeners\CartEventSubscriber;
use AndreiLungeanu\SimpleCart\Cart\Services\Calculation\CartCalculator;
use AndreiLungeanu\SimpleCart\Cart\Services\Calculation\DiscountCalculator;
use AndreiLungeanu\SimpleCart\Cart\Services\Calculation\ShippingCalculator;
use AndreiLungeanu\SimpleCart\Cart\Services\Calculation\TaxCalculator;
use AndreiLungeanu\SimpleCart\Cart\Services\Persistence\DatabaseCartRepository;
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\Services\DefaultTaxProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AndreiLungeanu\SimpleCart\Console\PurgeExpiredCartsCommand;

class SimpleCartServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('simple-cart')
            ->hasConfigFile()
            ->hasMigration('create_carts_table')
            ->hasCommand(PurgeExpiredCartsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CartRepository::class, DatabaseCartRepository::class);

        $this->app->bind(ShippingRateProvider::class, DefaultShippingProvider::class);
        $this->app->bind(TaxRateProvider::class, DefaultTaxProvider::class);

        $this->app->bind(ShippingCalculatorInterface::class, function ($app) {
            return new ShippingCalculator($app->make(ShippingRateProvider::class));
        });
        $this->app->bind(TaxCalculatorInterface::class, function ($app) {
            return new TaxCalculator($app->make(TaxRateProvider::class));
        });
        $this->app->bind(DiscountCalculatorInterface::class, DiscountCalculator::class);

        $this->app->bind(CartCalculatorInterface::class, function ($app) {
            return new CartCalculator(
                $app->make(ShippingCalculatorInterface::class),
                $app->make(TaxCalculatorInterface::class),
                $app->make(DiscountCalculatorInterface::class),
                $app->make(TaxRateProvider::class)
            );
        });

        $this->app->bind(AddItemToCartActionInterface::class, AddItemToCartAction::class);

        $this->app->singleton(CartManagerInterface::class, CartManager::class);

        $this->app->singleton(SimpleCart::class, function ($app) {
            return new SimpleCart($app->make(CartManagerInterface::class));
        });

        $this->app->alias(SimpleCart::class, 'simple-cart');
    }

    public function packageBooted(): void
    {
        $this->app['events']->subscribe(CartEventSubscriber::class);
    }
}
