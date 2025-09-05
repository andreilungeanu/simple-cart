<?php

return [
    'storage' => [
        'ttl_days' => env('CART_TTL_DAYS', 30),
        'cleanup_expired' => env('CART_CLEANUP_EXPIRED', true),
    ],

    'tax' => [
        'default_zone' => env('CART_DEFAULT_TAX_ZONE', 'US'),
        'settings' => [
            'zones' => [
                'US' => [
                    'name' => 'United States',
                    'default_rate' => env('CART_US_TAX_RATE', 0.0725),
                    'apply_to_shipping' => false,
                    'rates_by_category' => [
                        'digital' => 0.0,
                        'food' => 0.03,
                    ],
                ],
                'RO' => [
                    'name' => 'Romania',
                    'default_rate' => env('CART_RO_TAX_RATE', 0.19),
                    'apply_to_shipping' => true,
                    'rates_by_category' => [
                        'books' => 0.05,
                        'food' => 0.09,
                    ],
                ],
                'EU' => [
                    'name' => 'European Union',
                    'default_rate' => env('CART_EU_TAX_RATE', 0.20),
                    'apply_to_shipping' => true,
                    'rates_by_category' => [],
                ],
            ],
        ],
    ],

    'shipping' => [
        'settings' => [
            'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 100.00),
            'methods' => [
                'standard' => [
                    'name' => 'Standard Shipping',
                    'cost' => env('CART_STANDARD_SHIPPING_COST', 5.99),
                    'estimated_days' => '5-7',
                    'type' => 'flat',
                ],
                'express' => [
                    'name' => 'Express Shipping',
                    'cost' => env('CART_EXPRESS_SHIPPING_COST', 15.99),
                    'estimated_days' => '1-2',
                    'type' => 'flat',
                ],
                'overnight' => [
                    'name' => 'Overnight Shipping',
                    'cost' => env('CART_OVERNIGHT_SHIPPING_COST', 29.99),
                    'estimated_days' => '1',
                    'type' => 'flat',
                ],
            ],
        ],
    ],

    'discounts' => [
        'allow_stacking' => env('CART_ALLOW_DISCOUNT_STACKING', false),
        'max_discount_codes' => env('CART_MAX_DISCOUNT_CODES', 3),
    ],
];
