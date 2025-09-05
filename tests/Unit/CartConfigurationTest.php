<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;

describe('CartConfiguration', function () {

    it('creates from config array', function () {
        $configArray = [
            'storage' => ['ttl_days' => 45],
            'shipping' => [
                'settings' => [
                    'free_shipping_threshold' => 75.0,
                    'methods' => ['standard' => ['cost' => 9.99]],
                ],
            ],
            'tax' => [
                'default_zone' => 'RO',
                'settings' => ['zones' => ['RO' => ['default_rate' => 0.19]]],
            ],
            'discounts' => [
                'allow_stacking' => true,
                'max_discount_codes' => 5,
            ],
        ];

        $config = CartConfiguration::fromConfig($configArray);

        expect($config->ttlDays)->toBe(45)
            ->and($config->freeShippingThreshold)->toBe(75.0)
            ->and($config->defaultTaxZone)->toBe('RO')
            ->and($config->allowDiscountStacking)->toBeTrue()
            ->and($config->maxDiscountCodes)->toBe(5);
    });

    it('uses default values for missing config', function () {
        $config = CartConfiguration::fromConfig([]);

        expect($config->ttlDays)->toBe(30)
            ->and($config->freeShippingThreshold)->toBe(100.0)
            ->and($config->defaultTaxZone)->toBe('US')
            ->and($config->allowDiscountStacking)->toBeFalse()
            ->and($config->maxDiscountCodes)->toBe(3);
    });

    it('gets tax settings for zone', function () {
        $configArray = [
            'tax' => [
                'settings' => [
                    'zones' => [
                        'US' => ['default_rate' => 0.0725],
                        'RO' => ['default_rate' => 0.19],
                    ],
                ],
            ],
        ];

        $config = CartConfiguration::fromConfig($configArray);

        $usSettings = $config->getTaxSettings('US');
        $roSettings = $config->getTaxSettings('RO');
        $unknownSettings = $config->getTaxSettings('UNKNOWN');

        expect($usSettings['default_rate'])->toBe(0.0725)
            ->and($roSettings['default_rate'])->toBe(0.19)
            ->and($unknownSettings)->toBeNull();
    });

    it('gets shipping method settings', function () {
        $configArray = [
            'shipping' => [
                'settings' => [
                    'methods' => [
                        'standard' => ['cost' => 5.99, 'name' => 'Standard'],
                        'express' => ['cost' => 15.99, 'name' => 'Express'],
                    ],
                ],
            ],
        ];

        $config = CartConfiguration::fromConfig($configArray);

        $standardMethod = $config->getShippingMethod('standard');
        $expressMethod = $config->getShippingMethod('express');
        $unknownMethod = $config->getShippingMethod('unknown');

        expect($standardMethod['cost'])->toBe(5.99)
            ->and($standardMethod['name'])->toBe('Standard')
            ->and($expressMethod['cost'])->toBe(15.99)
            ->and($expressMethod['name'])->toBe('Express')
            ->and($unknownMethod)->toBeNull();
    });

    it('gets all shipping methods', function () {
        $configArray = [
            'shipping' => [
                'settings' => [
                    'methods' => [
                        'standard' => ['cost' => 5.99],
                        'express' => ['cost' => 15.99],
                    ],
                ],
            ],
        ];

        $config = CartConfiguration::fromConfig($configArray);

        $methods = $config->getShippingMethods();

        expect($methods)->toHaveKey('standard')
            ->and($methods)->toHaveKey('express')
            ->and($methods['standard']['cost'])->toBe(5.99)
            ->and($methods['express']['cost'])->toBe(15.99);
    });

    it('is readonly', function () {
        $config = CartConfiguration::fromConfig([]);

        // This should cause a fatal error in PHP 8.1+ if the class wasn't readonly
        // Since we can't easily test fatal errors, we'll just verify the class is marked as readonly
        $reflection = new ReflectionClass(CartConfiguration::class);

        expect($reflection->getModifiers() & ReflectionClass::IS_READONLY)->toBeGreaterThan(0);
    });

});
