<?php

namespace Tests\Unit\Shipping;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use InvalidArgumentException;

test('validates vat rate is between 0 and 1', function () {
    $cart = new CartDTO(taxZone: 'RO');

    expect(fn () => $cart->setShippingMethod('test', [
        'amount' => 10,
        'vat_rate' => 1.5,
        'vat_included' => false,
    ]))->toThrow(InvalidArgumentException::class);
});
