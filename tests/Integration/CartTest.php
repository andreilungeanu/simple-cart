<?php

namespace AndreiLungeanu\SimpleCart\Tests\Integration;

use AndreiLungeanu\SimpleCart\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use Illuminate\Support\Facades\Event;

test('cart persistence works with database', function () {
    Event::fake();

    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Item',
            price: 99.99,
            quantity: 1,
            metadata: []
        ))
        ->save();

    $cartId = $cart->get()->id;
    $loadedCart = app(SimpleCart::class)->find($cartId);

    expect($loadedCart->get()->getItems())->toHaveCount(1)
        ->and($loadedCart->total())->toBe(99.99);

    Event::assertDispatched(CartUpdated::class);
});
