<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;

describe('CartConfiguration', function () {

    it('creates from config array', function () {
        $configArray = [
            'storage' => ['ttl_days' => 45],
            'shipping' => [
                'free_shipping_threshold' => 75.0,
            ],
            'discounts' => [
                'allow_stacking' => true,
                'max_discount_codes' => 5,
            ],
        ];

        $config = CartConfiguration::fromConfig($configArray);

        expect($config->ttlDays)->toBe(45)
            ->and($config->freeShippingThreshold)->toBe(75.0)
            ->and($config->allowDiscountStacking)->toBeTrue()
            ->and($config->maxDiscountCodes)->toBe(5);
    });

    it('uses default values for missing config', function () {
        $config = CartConfiguration::fromConfig([]);

        expect($config->ttlDays)->toBe(30)
            ->and($config->freeShippingThreshold)->toBe(100.0)
            ->and($config->allowDiscountStacking)->toBeFalse()
            ->and($config->maxDiscountCodes)->toBe(3);
    });

    it('handles free shipping threshold edge cases', function () {
        // Test null threshold (disables free shipping)
        $config1 = CartConfiguration::fromConfig([
            'shipping' => ['free_shipping_threshold' => null],
        ]);
        expect($config1->freeShippingThreshold)->toBeNull();

        // Test zero threshold (disables free shipping)
        $config2 = CartConfiguration::fromConfig([
            'shipping' => ['free_shipping_threshold' => 0],
        ]);
        expect($config2->freeShippingThreshold)->toBeNull();

        // Test valid threshold
        $config3 = CartConfiguration::fromConfig([
            'shipping' => ['free_shipping_threshold' => 50.0],
        ]);
        expect($config3->freeShippingThreshold)->toBe(50.0);
    });

    it('is readonly', function () {
        $config = CartConfiguration::fromConfig([]);

        // This should cause a fatal error in PHP 8.1+ if the class wasn't readonly
        // Since we can't easily test fatal errors, we'll just verify the class is marked as readonly
        $reflection = new ReflectionClass(CartConfiguration::class);

        expect($reflection->getModifiers() & ReflectionClass::IS_READONLY)->toBeGreaterThan(0);
    });

});
