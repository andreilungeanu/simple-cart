<?php

namespace AndreiLungeanu\SimpleCart\Cart\Services\Calculation;

use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\CartInstance;

class ShippingCalculator implements ShippingCalculatorInterface
{
    public function __construct(
        protected ShippingRateProvider $provider
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

        return round($info['amount'] ?? 0.0, 2);
    }

    public function getShippingInfo(CartInstance $cart): ?array
    {
        if (! $cart->getShippingMethod()) {
            return null;
        }

        $info = $this->provider->getRate($cart, $cart->getShippingMethod());

        if ($info['vat_rate'] !== null && ($info['vat_rate'] < 0 || $info['vat_rate'] > 1)) {
            throw new \InvalidArgumentException('VAT rate must be between 0 and 1');
        }

        if ($cart->isVatExempt()) {
            $info['vat_rate'] = 0.0;
            $info['vat_included'] = false;
        }

        return $info;
    }
}
