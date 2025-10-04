<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Services\Calculators;

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Models\Cart;

class DiscountCalculator
{
    public function __construct(
        private CartConfiguration $config
    ) {}

    public function calculate(Cart $cart, float $subtotal): float
    {
        $discounts = $cart->discount_data ?? [];

        if (empty($discounts)) {
            return 0.0;
        }

        $totalDiscount = 0.0;
        $appliedDiscounts = 0;

        foreach ($discounts as $code => $discountData) {
            if (! $this->validateDiscountConditions($discountData, $cart, $subtotal)) {
                continue;
            }

            $discountAmount = $this->calculateDiscountAmount($discountData, $cart, $subtotal);
            $totalDiscount += $discountAmount;
            $appliedDiscounts++;

            // If stacking is disabled, stop after first valid discount
            if (! $this->config->allowDiscountStacking) {
                break;
            }
        }

        return min($totalDiscount, $subtotal);
    }

    private function validateDiscountConditions(array $discountData, Cart $cart, float $subtotal): bool
    {
        $conditions = $discountData['conditions'] ?? [];

        // Minimum amount requirement
        if (isset($conditions['minimum_amount'])) {
            $minimumAmount = (float) $conditions['minimum_amount'];
            if ($subtotal < $minimumAmount) {
                return false;
            }
        }

        // Minimum items requirement
        if (isset($conditions['min_items'])) {
            $totalItems = $cart->items->sum('quantity');
            if ($totalItems < $conditions['min_items']) {
                return false;
            }
        }

        return $this->validateItemConditions($conditions, $cart);
    }

    private function validateItemConditions(array $conditions, Cart $cart): bool
    {
        // Specific item
        if (isset($conditions['item_id'])) {
            $item = $cart->items->where('product_id', $conditions['item_id'])->first();

            if (! $item) {
                return false;
            }

            // Quantity requirement for specific item
            if (isset($conditions['min_quantity']) && $item->quantity < $conditions['min_quantity']) {
                return false;
            }
        }
        // Category (no item_id)
        elseif (isset($conditions['category'])) {
            $categoryItems = $cart->items->where('category', $conditions['category']);

            if ($categoryItems->isEmpty()) {
                return false;
            }

            // Quantity requirement for category items
            if (isset($conditions['min_quantity'])) {
                $categoryItemCount = $categoryItems->sum('quantity');
                if ($categoryItemCount < $conditions['min_quantity']) {
                    return false;
                }
            }
        }
        // Only min_quantity specified
        elseif (isset($conditions['min_quantity'])) {
            $totalItems = $cart->items->sum('quantity');
            if ($totalItems < $conditions['min_quantity']) {
                return false;
            }
        }

        return true;
    }

    private function calculateDiscountAmount(array $discountData, Cart $cart, float $subtotal): float
    {
        $type = $discountData['type'] ?? 'fixed';
        $value = (float) ($discountData['value'] ?? 0.0);
        $conditions = $discountData['conditions'] ?? [];

        if ($type === 'free_shipping') {
            return 0.0;
        }

        $targetItems = $this->getTargetItems($cart, $conditions);
        $targetSubtotal = $targetItems->sum(function ($item) {
            return $item->getLineTotal();
        });

        return match ($type) {
            'fixed' => min($value, $targetSubtotal),
            'percentage' => $targetSubtotal * ($value / 100),
            default => 0.0,
        };
    }

    private function getTargetItems(Cart $cart, array $conditions)
    {
        if (isset($conditions['item_id'])) {
            return $cart->items->where('product_id', $conditions['item_id']);
        }

        if (isset($conditions['category'])) {
            return $cart->items->where('category', $conditions['category']);
        }

        return $cart->items;
    }
}
