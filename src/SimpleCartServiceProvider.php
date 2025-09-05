<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\Console\Commands\PurgeCartsCommand;
use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Services\Calculators\ShippingCalculator;
use AndreiLungeanu\SimpleCart\Services\Calculators\TaxCalculator;
use AndreiLungeanu\SimpleCart\Services\CartService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SimpleCartServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('simple-cart')
            ->hasConfigFile()
            ->hasMigrations([
                'create_carts_table',
                'create_cart_items_table',
            ])
            ->hasCommands([
                PurgeCartsCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // Register Configuration
        $this->app->singleton(CartConfiguration::class, fn () => CartConfiguration::fromConfig(config('simple-cart', []))
        );

        // Register Services
        $this->app->singleton(CartService::class);
        $this->app->singleton(TaxCalculator::class);
        $this->app->singleton(ShippingCalculator::class);
    }
}
