<?php

return [
    'storage' => [
        'ttl_days' => env('CART_TTL_DAYS', 30),
        'cleanup_expired' => env('CART_CLEANUP_EXPIRED', true),
    ],

    'shipping' => [
        'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 0.00),
        // Set to null or 0 to disable free shipping based on threshold completely
    ],

    'discounts' => [
        //
        'allow_stacking' => env('CART_ALLOW_DISCOUNT_STACKING', false),
        'max_discount_codes' => env('CART_MAX_DISCOUNT_CODES', 3),
   ],

   // Behavior when a user logs in and both a guest cart (session) and a user cart exist.
   // Options: 'merge' (default), 'guest' (keep guest cart), 'user' (keep user cart)
   'login_cart_strategy' => env('CART_ON_LOGIN_CART_STRATEGY', 'merge'),
];
