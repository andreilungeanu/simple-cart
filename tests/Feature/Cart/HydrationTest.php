<?php

use AndreiLungeanu\SimpleCart\Cart\Contracts\CartRepository;
use AndreiLungeanu\SimpleCart\Cart\Facades\SimpleCart as Cart;

test('cart save and load preserves item data when provided as array', function () {
    $cartWrapper = Cart::create();

    $item = [
        'id' => 'prod-rt',
        'name' => 'RoundTrip',
        'price' => 12.34,
        'quantity' => 2,
    ];

    $cartWrapper->addItem($item);

    $id = $cartWrapper->getId();

    $repo = app(CartRepository::class);
    $loaded = $repo->find($id);

    expect($loaded)->not->toBeNull();

    $loadedItem = $loaded->getItems()->first();

    expect($loadedItem->id)->toBe('prod-rt')
        ->and($loadedItem->price)->toBe(12.34)
        ->and($loadedItem->quantity)->toBe(2);
});
