<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Services\Calculators;

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Models\Cart;

class TaxCalculator
{
    public function __construct(
        private CartConfiguration $config
    ) {}

    public function calculate(Cart $cart, float $subtotal, float $shipping = 0): float
    {
        if (! $cart->tax_zone) {
            return 0.0;
        }

        $taxSettings = $this->config->getTaxSettings($cart->tax_zone);
        if (! $taxSettings) {
            return 0.0;
        }

        // Calculate item tax with category-specific rates
        $itemTax = $cart->items->sum(function ($item) use ($taxSettings) {
            $rate = $taxSettings['rates_by_category'][$item->category] ?? $taxSettings['default_rate'];

            return ($item->price * $item->quantity) * $rate;
        });

        // Add shipping tax if applicable
        $shippingTax = 0;
        if ($taxSettings['apply_to_shipping'] && $shipping > 0) {
            $shippingTax = $shipping * $taxSettings['default_rate'];
        }

        return round($itemTax + $shippingTax, 2);
    }

    public function getEffectiveRate(Cart $cart, ?string $category = null): float
    {
        if (! $cart->tax_zone) {
            return 0.0;
        }

        $taxSettings = $this->config->getTaxSettings($cart->tax_zone);
        if (! $taxSettings) {
            return 0.0;
        }

        return $taxSettings['rates_by_category'][$category] ?? $taxSettings['default_rate'];
    }
}
