<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Database\Factories;

use AndreiLungeanu\SimpleCart\Enums\CartStatusEnum;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\AndreiLungeanu\SimpleCart\Models\Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toString(),
            'user_id' => fake()->randomNumber(),
            'session_id' => fake()->uuid(),
            'status' => CartStatusEnum::Active,
            'expires_at' => now()->addDays(30),
            'discount_data' => [],
            'shipping_data' => null,
            'tax_data' => null,
            'metadata' => [],
        ];
    }

    /**
     * Create a cart for a guest user (no user_id)
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'session_id' => fake()->uuid(),
        ]);
    }

    /**
     * Create an expired cart
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CartStatusEnum::Expired,
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Create an abandoned cart
     */
    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CartStatusEnum::Abandoned,
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Create a cart for a specific user
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Create a cart with predefined discount data
     */
    public function withDiscounts(array $discounts = []): static
    {
        $discountData = [];

        // Default discount configurations
        $defaultDiscounts = [
            'SAVE10' => [
                'code' => 'SAVE10',
                'type' => 'fixed',
                'value' => 10.0,
                'conditions' => ['minimum_amount' => 50.0],
            ],
            'SAVE20' => [
                'code' => 'SAVE20',
                'type' => 'fixed',
                'value' => 20.0,
                'conditions' => ['minimum_amount' => 100.0],
            ],
            'PERCENT15' => [
                'code' => 'PERCENT15',
                'type' => 'percentage',
                'value' => 15.0,
                'conditions' => ['minimum_amount' => 75.0],
            ],
            'FREESHIP' => [
                'code' => 'FREESHIP',
                'type' => 'free_shipping',
                'value' => 0.0,
                'conditions' => [],
            ],
            'BOOKS20' => [
                'code' => 'BOOKS20',
                'type' => 'percentage',
                'value' => 20.0,
                'conditions' => [
                    'category' => 'books',
                    'minimum_amount' => 30.0,
                ],
            ],
            'LAPTOP_BULK' => [
                'code' => 'LAPTOP_BULK',
                'type' => 'fixed',
                'value' => 50.0,
                'conditions' => [
                    'item_id' => 'laptop_pro',
                    'min_quantity' => 2,
                ],
            ],
        ];

        foreach ($discounts as $discount) {
            if (is_string($discount) && isset($defaultDiscounts[$discount])) {
                $discountData[$discount] = $defaultDiscounts[$discount];
            } elseif (is_array($discount) && isset($discount['code'])) {
                $discountData[$discount['code']] = $discount;
            }
        }

        return $this->state(fn (array $attributes) => [
            'discount_data' => $discountData,
        ]);
    }

    /**
     * Create a cart with shipping data
     */
    public function withShipping(array $shippingData = []): static
    {
        $defaultShipping = [
            'method' => 'standard',
            'cost' => 9.99,
            'address' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->randomElement(['CA', 'NY', 'TX', 'FL', 'IL', 'PA', 'OH']),
                'zip' => fake()->postcode(),
                'country' => 'US',
            ],
        ];

        return $this->state(fn (array $attributes) => [
            'shipping_data' => array_merge($defaultShipping, $shippingData),
        ]);
    }

    /**
     * Create a cart with tax data
     */
    public function withTax(array $taxData = []): static
    {
        $defaultTax = [
            'rate' => 0.08,
            'amount' => 0.0,
            'zone' => 'US',
        ];

        return $this->state(fn (array $attributes) => [
            'tax_data' => array_merge($defaultTax, $taxData),
        ]);
    }

    /**
     * Create a cart with metadata
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a cart with predefined test items (replicates old createTestCartWithItems helper)
     */
    public function withTestItems(): static
    {
        return $this->afterCreating(function (Cart $cart) {
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_id' => 'PROD-1',
                'name' => 'Test Product 1',
                'price' => 29.99,
                'quantity' => 2,
                'category' => 'electronics',
            ]);

            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_id' => 'PROD-2',
                'name' => 'Test Product 2',
                'price' => 15.00,
                'quantity' => 1,
                'category' => 'books',
            ]);
        });
    }
}
