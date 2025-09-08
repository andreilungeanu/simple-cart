<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Database\Factories;

use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\AndreiLungeanu\SimpleCart\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_id' => 'PROD-'.fake()->unique()->randomNumber(4),
            'name' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 1, 999),
            'quantity' => fake()->numberBetween(1, 5),
            'category' => fake()->randomElement(['electronics', 'books', 'clothing', 'home', 'sports']),
            'metadata' => [],
        ];
    }

    /**
     * Create an electronics item
     */
    public function electronics(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'electronics',
            'name' => fake()->randomElement([
                'Wireless Headphones',
                'Smartphone',
                'Laptop',
                'Tablet',
                'Smart Watch',
                'Gaming Console',
            ]),
            'price' => fake()->randomFloat(2, 50, 2000),
        ]);
    }

    /**
     * Create a books item
     */
    public function books(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'books',
            'name' => fake()->sentence(3).' Book',
            'price' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    /**
     * Create a clothing item
     */
    public function clothing(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'clothing',
            'name' => fake()->randomElement([
                'T-Shirt',
                'Jeans',
                'Sweater',
                'Jacket',
                'Sneakers',
                'Dress',
            ]),
            'price' => fake()->randomFloat(2, 20, 200),
        ]);
    }

    /**
     * Create an expensive item
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 500, 5000),
        ]);
    }

    /**
     * Create a cheap item
     */
    public function cheap(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 1, 20),
        ]);
    }

    /**
     * Create an item with high quantity
     */
    public function bulk(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(10, 50),
        ]);
    }

    /**
     * Create a specific product
     */
    public function product(string $productId, string $name, float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
            'name' => $name,
            'price' => $price,
        ]);
    }

    /**
     * Create an item with metadata
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create predefined test products
     */
    public function testProduct1(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => 'PROD-1',
            'name' => 'Test Product 1',
            'price' => 29.99,
            'quantity' => 2,
            'category' => 'electronics',
        ]);
    }

    public function testProduct2(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => 'PROD-2',
            'name' => 'Test Product 2',
            'price' => 15.00,
            'quantity' => 1,
            'category' => 'books',
        ]);
    }

    /**
     * Create items for a specific cart
     */
    public function forCart(Cart $cart): static
    {
        return $this->state(fn (array $attributes) => [
            'cart_id' => $cart->id,
        ]);
    }
}
