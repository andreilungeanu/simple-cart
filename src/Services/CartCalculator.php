<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\CartInstance; // Use CartInstance
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
// use AndreiLungeanu\SimpleCart\SimpleCart; // No longer used

class CartCalculator
{
    public function __construct(
        protected ShippingCalculator $shippingCalculator,
        protected TaxCalculator $taxCalculator,
        protected DiscountCalculator $discountCalculator,
        protected TaxRateProvider $taxRateProvider
    ) {}

    private function round(float $amount): float
    {
        return round($amount, 2);
    }

    public function getSubtotal(CartInstance $cart): float
    {
        return $this->round(
            $cart->getItems()->sum( // Assumes CartInstance has getItems()
                fn(CartItemDTO $item) => $item->price * $item->quantity
            )
        );
    }

    public function getItemCount(CartInstance $cart): int
    {
        return $cart->getItems()->sum(fn(CartItemDTO $item) => $item->quantity); // Assumes CartInstance has getItems()
    }

    // Assuming ShippingCalculator also needs update to accept CartInstance
    public function getShippingAmount(CartInstance $cart): float
    {
        return $this->shippingCalculator->calculate($cart); // Needs update in ShippingCalculator
    }

    // Assuming TaxCalculator also needs update to accept CartInstance
    public function getTaxAmount(CartInstance $cart): float
    {
        if ($cart->isVatExempt()) { // Assumes CartInstance has isVatExempt()
            return 0.0;
        }

        $itemsTax = $this->taxCalculator->calculate($cart); // Needs update in TaxCalculator

        // Calculate shipping tax only if a method is set AND VAT is NOT already included in the shipping amount
        $shippingInfo = $cart->getShippingVatInfo(); // Assumes CartInstance has getShippingVatInfo()
        $shippingTax = $cart->getShippingMethod() && !$shippingInfo['included']
            ? $this->calculateShippingVat($cart) // Calculate VAT based on shipping amount and rate
            : 0.0;

        $extraCostsTax = $this->getExtraCostsTax($cart);

        return $this->round($itemsTax + $shippingTax + $extraCostsTax);
    }

    // Pass pre-calculated subtotal to DiscountCalculator
    public function getDiscountAmount(CartInstance $cart): float
    {
        $subtotal = $this->getSubtotal($cart); // Calculate subtotal first
        return $this->discountCalculator->calculate($cart, $subtotal); // Pass cart and subtotal
    }

    public function getTotal(CartInstance $cart): float
    {
        return $this->round(
            $this->getSubtotal($cart) + // Uses updated methods
                $this->getShippingAmount($cart) +
                $this->getTaxAmount($cart) +
                $this->getExtraCostsTotal($cart) -
                $this->getDiscountAmount($cart) // Uses updated methods
        );
    }

    private function calculateExtraCosts(CartInstance $cart): float
    {
        return $cart->getExtraCosts()->sum(function (ExtraCostDTO $cost) use ($cart) { // Assumes CartInstance has getExtraCosts()
            if ($cost->type === 'percentage') {
                return ($this->getSubtotal($cart) * $cost->amount) / 100; // Uses updated getSubtotal
            }
            return $cost->amount;
        });
    }

    public function getExtraCostsTotal(CartInstance $cart): float
    {
        return $this->round($this->calculateExtraCosts($cart)); // Uses updated calculateExtraCosts
    }

    private function getExtraCostsTax(CartInstance $cart): float
    {
        if ($cart->isVatExempt()) { // Assumes CartInstance has isVatExempt()
            return 0.0;
        }
        $rate = $this->defaultVatRate($cart); // Uses updated defaultVatRate
        return $this->round($this->getExtraCostsTotal($cart) * $rate); // Uses updated getExtraCostsTotal
    }

    public function calculateShippingVat(CartInstance $cart): float
    {
        $shippingVatInfo = $cart->getShippingVatInfo(); // Assumes CartInstance has getShippingVatInfo()

        // Explicitly return 0 if VAT is exempt, no method is set, OR if VAT is already included
        if ($cart->isVatExempt() || !$cart->getShippingMethod() || $shippingVatInfo['included']) {
            return 0.0;
        }

        $rate = $shippingVatInfo['rate'] ?? $this->defaultVatRate($cart); // Uses updated defaultVatRate
        // Calculate VAT only on the base shipping amount if rate is present
        return $rate > 0 ? $this->round($this->getShippingAmount($cart) * $rate) : 0.0; // Uses updated getShippingAmount
    }

    // Assuming TaxRateProvider also needs update to accept CartInstance
    protected function defaultVatRate(CartInstance $cart): float
    {
        return $this->taxRateProvider->getRate($cart); // Needs update in TaxRateProvider contract/implementation
    }
}
