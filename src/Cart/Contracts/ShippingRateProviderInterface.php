<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingMethodDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingRateDTO;
use AndreiLungeanu\SimpleCart\CartInstance;

/**
 * Interface for services that provide shipping rates and methods based on a CartInstance.
 */
interface ShippingRateProviderInterface
{
    public function getRate(CartInstance $cart, string $method): ?ShippingRateDTO;

    /**
     * @return ShippingMethodDTO[]
     */
    public function getAvailableMethods(CartInstance $cart): array;
}
