<?php

namespace AndreiLungeanu\SimpleCart\Tests\Feature;

use AndreiLungeanu\SimpleCart\Cart\Models\Cart as CartModel;
use AndreiLungeanu\SimpleCart\Cart\Services\Persistence\DatabaseCartRepository;
use AndreiLungeanu\SimpleCart\Tests\TestCase;

class PurgeExpiredCartsTest extends TestCase
{
    public function test_purge_expired_deletes_only_expired()
    {
        // Create three carts: one expired, two valid
        CartModel::create([
            'id' => 'expired-1',
            'items' => [],
            'discounts' => [],
            'notes' => [],
            'extra_costs' => [],
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->subDay(),
        ]);

        CartModel::create([
            'id' => 'valid-1',
            'items' => [],
            'discounts' => [],
            'notes' => [],
            'extra_costs' => [],
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        CartModel::create([
            'id' => 'valid-2',
            'items' => [],
            'discounts' => [],
            'notes' => [],
            'extra_costs' => [],
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => null,
        ]);

        $repo = new DatabaseCartRepository;

        $deleted = $repo->purgeExpired();

        $this->assertSame(1, $deleted);
        $this->assertNull(CartModel::find('expired-1'));
        $this->assertNotNull(CartModel::find('valid-1'));
        $this->assertNotNull(CartModel::find('valid-2'));
    }

    public function test_command_purge_expired_outputs_and_deletes()
    {
        CartModel::create([
            'id' => 'expired-cmd',
            'items' => [],
            'discounts' => [],
            'notes' => [],
            'extra_costs' => [],
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('cart:purge-expired')
            ->expectsOutput('Purged 1 expired cart(s).')
            ->assertExitCode(0);

        $this->assertNull(CartModel::find('expired-cmd'));
    }
}
