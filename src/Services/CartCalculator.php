<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\SimpleCart;

class CartCalculator
{
    public function __construct(
        protected ShippingCalculator $shippingCalculator,
        protected TaxCalculator $taxCalculator,
        protected DiscountCalculator $discountCalculator,
        protected TaxRateProvider $taxRateProvider // Needed for defaultVatRate
    ) {}

    // --- Calculation methods moved from SimpleCart ---

    private function round(float $amount): float
    {
        return round($amount, 2);
    }

    public function getSubtotal(SimpleCart $cart): float
    {
        return $this->round(
            $cart->getItems()->sum(
                fn(CartItemDTO $item) => $item->price * $item->quantity
            )
        );
    }

    public function getItemCount(SimpleCart $cart): int
    {
        return $cart->getItems()->sum(fn(CartItemDTO $item) => $item->quantity);
    }

    // Note: getShippingCost was identical to getShippingAmount, removed duplication
    public function getShippingAmount(SimpleCart $cart): float
    {
        // Delegate to the injected ShippingCalculator
        return $this->shippingCalculator->calculate($cart);
    }

    public function getTaxAmount(SimpleCart $cart): float
    {
        // Delegate to the injected TaxCalculator for item tax
        // Keep shipping and extra cost tax calculation here for now, or move to TaxCalculator?
        // Let's keep it here for now as it depends on other calculated values.
        if ($cart->isVatExempt()) {
            return 0.0;
        }

        $itemsTax = $this->taxCalculator->calculate($cart); // Delegate item tax calculation
        $shippingTax = $cart->getShippingMethod() && !$cart->getShippingVatInfo()['included']
            ? $this->calculateShippingVat($cart)
            : 0.0;
        $extraCostsTax = $this->getExtraCostsTax($cart);

        return $this->round($itemsTax + $shippingTax + $extraCostsTax);
    }

    public function getDiscountAmount(SimpleCart $cart): float
    {
        // Delegate to the injected DiscountCalculator
        return $this->discountCalculator->calculate($cart);
    }

    public function getTotal(SimpleCart $cart): float
    {
        // Calculate total using methods within this service
        return $this->round(
            $this->getSubtotal($cart) +
                $this->getShippingAmount($cart) +
                $this->getTaxAmount($cart) +
                $this->getExtraCostsTotal($cart) -
                $this->getDiscountAmount($cart)
        );
    }

    private function calculateExtraCosts(SimpleCart $cart): float
    {
        return $cart->getExtraCosts()->sum(function (ExtraCostDTO $cost) use ($cart) {
            if ($cost->type === 'percentage') {
                // Use $this->getSubtotal()
                return ($this->getSubtotal($cart) * $cost->amount) / 100;
            }
            return $cost->amount;
        });
    }

    public function getExtraCostsTotal(SimpleCart $cart): float
    {
        return $this->round($this->calculateExtraCosts($cart));
    }

    private function getExtraCostsTax(SimpleCart $cart): float
    {
        if ($cart->isVatExempt()) {
            return 0.0;
        }
        $rate = $this->defaultVatRate($cart);
        return $this->round($this->getExtraCostsTotal($cart) * $rate);
    }

    public function calculateShippingVat(SimpleCart $cart): float
    {
        if ($cart->isVatExempt() || !$cart->getShippingMethod()) {
            return 0.0;
        }
        $shippingVatInfo = $cart->getShippingVatInfo();
        $rate = $shippingVatInfo['rate'] ?? $this->defaultVatRate($cart);
        return $this->round($this->getShippingAmount($cart) * $rate);
    }

    protected function defaultVatRate(SimpleCart $cart): float
    {
        // Delegate to the injected TaxRateProvider
        return $this->taxRateProvider->getRate($cart);
    }
}
