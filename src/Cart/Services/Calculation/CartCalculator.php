<?php

namespace AndreiLungeanu\SimpleCart\Cart\Services\Calculation;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Cart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Cart\Contracts\CartCalculatorInterface; // Add interface
use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingCalculatorInterface; // Use interface for dependency
use AndreiLungeanu\SimpleCart\Cart\Contracts\TaxCalculatorInterface; // Use interface for dependency
use AndreiLungeanu\SimpleCart\Cart\Contracts\DiscountCalculatorInterface; // Use interface for dependency

class CartCalculator implements CartCalculatorInterface // Implement interface
{
    public function __construct(
        protected ShippingCalculatorInterface $shippingCalculator, // Type hint interface
        protected TaxCalculatorInterface $taxCalculator, // Type hint interface
        protected DiscountCalculatorInterface $discountCalculator, // Type hint interface
        protected TaxRateProvider $taxRateProvider
    ) {}

    private function round(float $amount): float
    {
        return round($amount, 2);
    }

    public function getSubtotal(CartInstance $cart): float
    {
        return $this->round(
            $cart->getItems()->sum(
                fn(CartItemDTO $item) => $item->price * $item->quantity
            )
        );
    }

    public function getItemCount(CartInstance $cart): int
    {
        return $cart->getItems()->sum(fn(CartItemDTO $item) => $item->quantity);
    }

    public function getShippingAmount(CartInstance $cart): float
    {
        return $this->shippingCalculator->calculate($cart);
    }

    public function getTaxAmount(CartInstance $cart): float
    {
        if ($cart->isVatExempt()) {
            return 0.0;
        }

        $itemsTax = $this->taxCalculator->calculate($cart);

        $shippingInfo = $cart->getShippingVatInfo();
        $shippingTax = $cart->getShippingMethod() && !$shippingInfo['included']
            ? $this->calculateShippingVat($cart)
            : 0.0;

        $extraCostsTax = $this->getExtraCostsTax($cart);

        return $this->round($itemsTax + $shippingTax + $extraCostsTax);
    }

    public function getDiscountAmount(CartInstance $cart): float
    {
        $subtotal = $this->getSubtotal($cart);
        return $this->discountCalculator->calculate($cart, $subtotal);
    }

    public function getTotal(CartInstance $cart): float
    {
        return $this->round(
            $this->getSubtotal($cart) +
                $this->getShippingAmount($cart) +
                $this->getTaxAmount($cart) +
                $this->getExtraCostsTotal($cart) -
                $this->getDiscountAmount($cart)
        );
    }

    private function calculateExtraCosts(CartInstance $cart): float
    {
        return $cart->getExtraCosts()->sum(function (ExtraCostDTO $cost) use ($cart) {
            if ($cost->type === 'percentage') {
                return ($this->getSubtotal($cart) * $cost->amount) / 100;
            }
            return $cost->amount;
        });
    }

    public function getExtraCostsTotal(CartInstance $cart): float
    {
        return $this->round($this->calculateExtraCosts($cart));
    }

    private function getExtraCostsTax(CartInstance $cart): float
    {
        if ($cart->isVatExempt()) {
            return 0.0;
        }
        $rate = $this->defaultVatRate($cart);
        return $this->round($this->getExtraCostsTotal($cart) * $rate);
    }

    public function calculateShippingVat(CartInstance $cart): float
    {
        $shippingVatInfo = $cart->getShippingVatInfo();

        if ($cart->isVatExempt() || !$cart->getShippingMethod() || $shippingVatInfo['included']) {
            return 0.0;
        }

        $rate = $shippingVatInfo['rate'] ?? $this->defaultVatRate($cart);
        return $rate > 0 ? $this->round($this->getShippingAmount($cart) * $rate) : 0.0;
    }

    protected function defaultVatRate(CartInstance $cart): float
    {
        return $this->taxRateProvider->getRate($cart);
    }

    /**
     * Check if free shipping is currently applied to the cart.
     * This is typically true if a shipping method is selected AND
     * the calculated shipping cost is zero (e.g., due to meeting a threshold).
     *
     * @param CartInstance $cart
     * @return bool
     */
    public function isFreeShippingApplied(CartInstance $cart): bool
    {
        // Free shipping requires a method to be set and the cost to be zero
        return $cart->getShippingMethod() !== null && $this->getShippingAmount($cart) === 0.0;
    }
}
