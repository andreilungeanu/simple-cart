<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;

/**
 * Interface for services that provide shipping rates and methods based on a CartInstance.
 */
interface ShippingRateProvider
{
    /**
     * Get the shipping rate details for a specific method and cart instance.
     * Expected return keys: 'amount', 'vat_rate' (optional, float 0-1), 'vat_included' (optional, bool)
     *
     * @param  CartInstance  $cart  The cart instance.
     * @param  string  $method  The chosen shipping method identifier.
     * @return array Associative array with shipping rate details.
     */
    public function getRate(CartInstance $cart, string $method): array;

    /**
     * Get all available shipping methods for the given cart instance.
     * Expected return format: ['method_id' => ['name' => 'Display Name', 'description' => '...'], ...]
     *
     * @param  CartInstance  $cart  The cart instance.
     * @return array Associative array of available shipping methods.
     */
    public function getAvailableMethods(CartInstance $cart): array;
}
