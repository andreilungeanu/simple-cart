<?php

namespace AndreiLungeanu\SimpleCart\Tests\Feature;

use AndreiLungeanu\SimpleCart\Cart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\Tests\TestCase;

class DiscountShippingTest extends TestCase
{
    public function test_fixed_shipping_discount_is_capped_to_shipping_amount()
    {
        $cart = SimpleCart::create();

        $cart->setShippingMethod('standard', ['vat_included' => false]);

        // Ensure shipping cost is 5.99 as per config in tests environment
        $this->assertSame(5.99, SimpleCart::shippingAmount($cart->getId()));

        $cart->applyDiscount(['code' => 'SHIPFIX', 'type' => 'shipping', 'value' => 10.00]);

        $discount = SimpleCart::discountAmount($cart->getId());

        // Shipping discount should be capped to shipping amount (5.99)
        $this->assertSame(5.99, $discount);
    }

    public function test_percentage_shipping_discount_applies_correctly()
    {
        $cart = SimpleCart::create();

        $cart->setShippingMethod('standard', ['vat_included' => false]);

        $cart->applyDiscount(['code' => 'SHIP10', 'type' => 'shipping', 'value' => 10.0, 'appliesTo' => 'percentage']);

        $discount = SimpleCart::discountAmount($cart->getId());

        // 10% of 5.99 = 0.599 -> rounded to 0.60 by calculator
        $this->assertSame(0.6, $discount);
    }

    public function test_no_shipping_discount_when_free_shipping()
    {
        $cart = SimpleCart::create();

        // Make shipping free by meeting threshold
        // Add items to reach free shipping (threshold 100)
        $cart->addItem(['id' => 'p1', 'name' => 'Expensive', 'price' => 200.00, 'quantity' => 1]);

        $this->assertSame(0.0, SimpleCart::shippingAmount($cart->getId()));

        $cart->applyDiscount(['code' => 'SHIPFIX', 'type' => 'shipping', 'value' => 5.00]);

        $discount = SimpleCart::discountAmount($cart->getId());

        $this->assertSame(0.0, $discount);
    }
}
