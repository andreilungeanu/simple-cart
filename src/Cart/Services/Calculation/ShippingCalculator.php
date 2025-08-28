<?php

namespace AndreiLungeanu\SimpleCart\Cart\Services\Calculation;

use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingRateProviderInterface;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingRateDTO;
use AndreiLungeanu\SimpleCart\CartInstance;

class ShippingCalculator implements ShippingCalculatorInterface
{
    public function __construct(
        protected ShippingRateProviderInterface $provider
    ) {}

    public function calculate(CartInstance $cart): float
    {
        $shippingMethod = $cart->getShippingMethod();
        if (! $shippingMethod) {
            return 0.0;
        }

        $subtotal = $cart->getItems()->sum(
            fn (CartItemDTO $item) => $item->price * $item->quantity
        );

        $threshold = config('simple-cart.shipping.settings.free_shipping_threshold', null);
        if ($threshold !== null && $subtotal >= $threshold) {
            return 0.0;
        }

        $info = $this->provider->getRate($cart, $shippingMethod);

        if (! $info instanceof ShippingRateDTO) {
            return 0.0;
        }

        return round($info->amount ?? 0.0, 2);
    }

    public function getShippingInfo(CartInstance $cart): ?ShippingRateDTO
    {
        if (! $cart->getShippingMethod()) {
            return null;
        }

        $info = $this->provider->getRate($cart, $cart->getShippingMethod());

        if (! $info instanceof ShippingRateDTO) {
            return null;
        }

        if ($info->vatRate !== null && ($info->vatRate < 0 || $info->vatRate > 1)) {
            throw new \InvalidArgumentException('VAT rate must be between 0 and 1');
        }

        if ($cart->isVatExempt()) {
            $info = new ShippingRateDTO($info->amount, 0.0, false);
        }

        return $info;
    }
}
