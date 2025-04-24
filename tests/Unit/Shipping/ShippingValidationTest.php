<?php

namespace Tests\Unit\Shipping;

use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart;
use InvalidArgumentException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('validates vat rate is between 0 and 1 in setShippingMethod', function () {
    $cart = Cart::create();
    $cartId = $cart->getId();

    expect(fn() => Cart::setShippingMethod($cartId, 'test-upper', [
        'vat_rate' => 1.5,
    ]))->toThrow(InvalidArgumentException::class, 'VAT rate must be between 0 and 1');

    expect(fn() => Cart::setShippingMethod($cartId, 'test-lower', [
        'vat_rate' => -0.1,
    ]))->toThrow(InvalidArgumentException::class, 'VAT rate must be between 0 and 1');

    expect(fn() => Cart::setShippingMethod($cartId, 'test-valid', [
        'vat_rate' => 0.19,
    ]))->not->toThrow(InvalidArgumentException::class);

    expect(fn() => Cart::setShippingMethod($cartId, 'test-null', [
        'vat_rate' => null,
    ]))->not->toThrow(InvalidArgumentException::class);

    expect(fn() => Cart::setShippingMethod($cartId, 'test-missing', []))
        ->not->toThrow(InvalidArgumentException::class);
});
