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

            // If stacking is not allowed, take only the first valid discount
            if (! $this->config->allowDiscountStacking) {
                break;
            }

            // Respect max discount codes limit
            if ($appliedDiscounts >= $this->config->maxDiscountCodes) {
                break;
            }
        }

        return min($totalDiscount, $subtotal);
    }

    private function validateDiscountConditions(array $discountData, Cart $cart, float $subtotal): bool
    {
        $conditions = $discountData['conditions'] ?? [];

        // Check minimum amount requirement
        if (isset($conditions['minimum_amount'])) {
            $minimumAmount = (float) $conditions['minimum_amount'];
            if ($subtotal < $minimumAmount) {
                return false;
            }
        }

        // Check minimum items requirement
        if (isset($conditions['min_items'])) {
            $totalItems = $cart->items->sum('quantity');
            if ($totalItems < $conditions['min_items']) {
                return false;
            }
        }

        // Validate item/category specific conditions
        return $this->validateItemConditions($conditions, $cart);
    }

    private function validateItemConditions(array $conditions, Cart $cart): bool
    {
        // If item_id specified, validate that specific item
        if (isset($conditions['item_id'])) {
            $item = $cart->items->where('product_id', $conditions['item_id'])->first();

            if (! $item) {
                return false; // Item not in cart
            }

            // Check quantity requirement for specific item
            if (isset($conditions['min_quantity']) && $item->quantity < $conditions['min_quantity']) {
                return false;
            }
        }
        // If category specified (and no item_id), validate category items
        elseif (isset($conditions['category'])) {
            $categoryItems = $cart->items->where('category', $conditions['category']);

            if ($categoryItems->isEmpty()) {
                return false; // No items in specified category
            }

            // Check quantity requirement for category items
            if (isset($conditions['min_quantity'])) {
                $categoryItemCount = $categoryItems->sum('quantity');
                if ($categoryItemCount < $conditions['min_quantity']) {
                    return false;
                }
            }
        }
        // If only min_quantity specified (no item_id or category), check total cart
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

        // For free shipping, return 0.0 since ShippingCalculator handles the logic
        if ($type === 'free_shipping') {
            return 0.0;
        }

        // Get target items based on conditions
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
        // Priority 1: Item ID specified
        if (isset($conditions['item_id'])) {
            return $cart->items->where('product_id', $conditions['item_id']);
        }

        // Priority 2: Category specified (only if no item_id)
        if (isset($conditions['category'])) {
            return $cart->items->where('category', $conditions['category']);
        }

        // Priority 3: Entire cart
        return $cart->items;
    }
}
