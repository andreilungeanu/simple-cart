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
        'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 0.00),
        // Set to null or 0 to disable free shipping completely
    ],

    'discounts' => [
        'allow_stacking' => env('CART_ALLOW_DISCOUNT_STACKING', false),
        'max_discount_codes' => env('CART_MAX_DISCOUNT_CODES', 3),
    ],
];
