<?php

declare(strict_types=1);

namespace Tests;

use AndreiLungeanu\SimpleCart\SimpleCartServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load and run migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            SimpleCartServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup test database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup simple cart configuration for testing
        $app['config']->set('simple-cart', [
            'storage' => [
                'ttl_days' => 30,
                'cleanup_expired' => true,
            ],
            'tax' => [
                'default_zone' => 'US',
                'settings' => [
                    'zones' => [
                        'US' => [
                            'name' => 'United States',
                            'default_rate' => 0.0725,
                            'apply_to_shipping' => false,
                            'rates_by_category' => [
                                'digital' => 0.0,
                                'food' => 0.03,
                                'books' => 0.05,
                            ],
                        ],
                        'RO' => [
                            'name' => 'Romania',
                            'default_rate' => 0.19,
                            'apply_to_shipping' => true,
                            'rates_by_category' => [
                                'books' => 0.05,
                                'food' => 0.09,
                            ],
                        ],
                    ],
                ],
            ],
            'shipping' => [
                'settings' => [
                    'free_shipping_threshold' => 100.00,
                    'methods' => [
                        'standard' => [
                            'name' => 'Standard Shipping',
                            'cost' => 5.99,
                            'estimated_days' => '5-7',
                            'type' => 'flat',
                        ],
                        'express' => [
                            'name' => 'Express Shipping',
                            'cost' => 15.99,
                            'estimated_days' => '1-2',
                            'type' => 'flat',
                        ],
                    ],
                ],
            ],
            'discounts' => [
                'allow_stacking' => false,
                'max_discount_codes' => 3,
                'codes' => [
                    'SAVE10' => [
                        'type' => 'fixed',
                        'value' => 10.0,
                        'minimum_amount' => 50.0,
                    ],
                    'SAVE20' => [
                        'type' => 'fixed',
                        'value' => 20.0,
                        'minimum_amount' => 100.0,
                    ],
                    'PERCENT15' => [
                        'type' => 'percentage',
                        'value' => 15.0,
                        'minimum_amount' => 75.0,
                    ],
                    'FREESHIP' => [
                        'type' => 'free_shipping',
                        'value' => 0.0,
                        'minimum_amount' => 50.0,
                    ],
                ],
            ],
        ]);
    }
}
