<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Services\Calculators;

use AndreiLungeanu\SimpleCart\Models\Cart;

class TaxCalculator
{
    public function calculate(Cart $cart, float $subtotal, float $shipping = 0): float
    {
        $taxData = $cart->tax_data;
        if (! $taxData) {
            return 0.0;
        }

        // Item tax with priority-based rates
        $itemTax = $cart->items->sum(function ($item) use ($taxData) {
            $rate = $this->resolveItemTaxRate($item, $taxData);

            return ($item->price * $item->quantity) * $rate;
        });

        // Shipping tax if applicable
        $shippingTax = 0;
        if (($taxData['apply_to_shipping'] ?? false) && $shipping > 0) {
            $shippingRate = $taxData['shipping_rate'] ?? $taxData['rate'] ?? 0;
            $shippingTax = $shipping * $shippingRate;
        }

        return round($itemTax + $shippingTax, 2);
    }

    public function getEffectiveRate(Cart $cart, ?string $category = null, ?string $productId = null): float
    {
        $taxData = $cart->tax_data;
        if (! $taxData) {
            return 0.0;
        }

        // Specific product ID
        if ($productId) {
            return $taxData['conditions']['rates_per_item'][$productId] ?? $taxData['rate'] ?? 0.0;
        }

        // Category
        if ($category) {
            return $taxData['conditions']['rates_per_category'][$category] ?? $taxData['rate'] ?? 0.0;
        }

        return $taxData['rate'] ?? 0.0;
    }

    private function resolveItemTaxRate($item, array $taxData): float
    {
        $conditions = $taxData['conditions'] ?? [];

        // Priority 1: Item-specific
        if (isset($conditions['rates_per_item'][$item->product_id])) {
            return $conditions['rates_per_item'][$item->product_id];
        }

        // Priority 2: Category-specific
        if ($item->category && isset($conditions['rates_per_category'][$item->category])) {
            return $conditions['rates_per_category'][$item->category];
        }

        // Priority 3: Type-specific (from metadata)
        if (isset($item->metadata['type']) && isset($conditions['rates_per_type'][$item->metadata['type']])) {
            return $conditions['rates_per_type'][$item->metadata['type']];
        }

        // Priority 4: Default
        return $taxData['rate'] ?? 0.0;
    }
}
