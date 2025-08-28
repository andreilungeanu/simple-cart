<?php

namespace AndreiLungeanu\SimpleCart\Cart\Services\Calculation;

use AndreiLungeanu\SimpleCart\Cart\Contracts\CartCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\DiscountCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\TaxCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\CartInstance;

class CartCalculator implements CartCalculatorInterface
{
    public function __construct(
        protected ShippingCalculatorInterface $shippingCalculator,
        protected TaxCalculatorInterface $taxCalculator,
        protected DiscountCalculatorInterface $discountCalculator,
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
                fn (CartItemDTO $item) => $item->price * $item->quantity
            )
        );
    }

    public function getItemCount(CartInstance $cart): int
    {
        return $cart->getItems()->sum(fn (CartItemDTO $item) => $item->quantity);
    }

    public function getShippingAmount(CartInstance $cart): float
    {
        return $this->shippingCalculator->calculate($cart);
    }

    public function getTaxAmount(CartInstance $cart): float
    {
        $tax = 0.0;

        if ($cart->isVatExempt()) {
            return $tax;
        }

        $itemsTax = $this->taxCalculator->calculate($cart);

        $shippingInfo = $cart->getShippingVatInfo();
        $shippingTax = $cart->getShippingMethod() && ! $shippingInfo['included']
            ? $this->calculateShippingVat($cart)
            : 0.0;

        $extraCostsTax = $this->getExtraCostsTax($cart);

        $tax = $itemsTax + $shippingTax + $extraCostsTax;

        return $this->round($tax);
    }

    public function getDiscountAmount(CartInstance $cart): float
    {
        $subtotal = $this->getSubtotal($cart);

        $shippingAmount = $this->getShippingAmount($cart);

        return $this->discountCalculator->calculate($cart, $subtotal, $shippingAmount);
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
        $tax = 0.0;

        if ($cart->isVatExempt()) {
            return $tax;
        }

        foreach ($cart->getExtraCosts() as $cost) {
            $amount = $cost->type === 'percentage'
                ? ($this->getSubtotal($cart) * $cost->amount) / 100
                : $cost->amount;

            $rate = $cost->vatRate ?? $this->defaultVatRate($cart);

            if ($rate == null || $rate <= 0) {
                continue;
            }

            // If VAT is already included in the cost amount, we do not add tax here
            if ($cost->vatIncluded) {
                continue;
            }

            $tax += $amount * $rate;
        }

        return $this->round($tax);
    }

    public function calculateShippingVat(CartInstance $cart): float
    {
        $shippingVatInfo = $cart->getShippingVatInfo();

        if ($cart->isVatExempt() || ! $cart->getShippingMethod() || $shippingVatInfo['included']) {
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
     */
    public function isFreeShippingApplied(CartInstance $cart): bool
    {
        return $cart->getShippingMethod() !== null && $this->getShippingAmount($cart) === 0.0;
    }
}
