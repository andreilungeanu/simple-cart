<?php

namespace AndreiLungeanu\SimpleCart\Tests\Feature;

use AndreiLungeanu\SimpleCart\Cart\Events\CartDeleted;
use AndreiLungeanu\SimpleCart\Cart\Models\Cart as CartModel;
use AndreiLungeanu\SimpleCart\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class CartDeletedEventTest extends TestCase
{
    public function test_cart_deleted_event_dispatched_after_destroy()
    {
        Event::fake();

        $cart = CartModel::create([
            'id' => 'to-delete',
            'items' => [],
            'discounts' => [],
            'notes' => [],
            'extra_costs' => [],
        ]);

        $manager = $this->app->make(\AndreiLungeanu\SimpleCart\Cart\CartManager::class);

        $deleted = $manager->destroy($cart->id);

        $this->assertTrue($deleted);

        Event::assertDispatched(CartDeleted::class, function (CartDeleted $event) use ($cart) {
            return $event->cartId === $cart->id;
        });
    }
}
