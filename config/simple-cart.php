<?php

// config for AndreiLungeanu/SimpleCart

use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\Services\DefaultTaxProvider;

return [
    'storage' => [
        'driver' => env('CART_STORAGE_DRIVER', 'database'),
        'ttl' => env('CART_TTL', 30 * 24 * 60 * 60), // 30 days in seconds
    ],

    'tax' => [
        'provider' => DefaultTaxProvider::class,
        'default_zone' => env('CART_DEFAULT_TAX_ZONE', 'US'),
        'settings' => [
            'zones' => [
                'US' => [
                    'name' => 'United States',
                    'default_rate' => env('CART_US_TAX_RATE', 0.0725),
                    'apply_to_shipping' => false,
                    'rates_by_category' => [
                        'digital' => 0.0,  // Digital goods might be tax-free
                        'food' => 0.03,    // Reduced rate for food
                    ],
                ],
                'RO' => [
                    'name' => 'Romania',
                    'default_rate' => env('CART_RO_TAX_RATE', 0.19),
                    'apply_to_shipping' => true,
                    'rates_by_category' => [
                        'books' => 0.05,   // Reduced VAT for books
                        'food' => 0.09,    // Reduced VAT for food
                    ],
                ],
            ],
        ],
    ],

    'shipping' => [
        'provider' => DefaultShippingProvider::class,
        'settings' => [
            'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 100.00),
            'methods' => [
                'standard' => [
                    'cost' => env('CART_STANDARD_SHIPPING_COST', 5.99),
                    'name' => 'Standard Shipping',
                    'vat_included' => false,
                    'vat_rate' => null, // null = use cart VAT rate
                ],
                'express' => [
                    'cost' => env('CART_EXPRESS_SHIPPING_COST', 15.99),
                    'name' => 'Express Shipping',
                    'vat_included' => false,
                    'vat_rate' => null,
                ],
            ],
        ],
    ],
];
