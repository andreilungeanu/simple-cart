<?php

namespace AndreiLungeanu\SimpleCart\Cart\DTOs;

readonly class CartItemDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public float $price,
        public int $quantity,
        public ?string $category = null,
        public array $metadata = [],
    ) {
        if ($price < 0 || $price > 999999.99) {
            throw new \InvalidArgumentException('Price must be between 0 and 999,999.99');
        }

        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    }

    public function withQuantity(int $quantity): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            price: $this->price,
            quantity: $quantity,
            category: $this->category,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new DTO instance from an array.
     *
     * @param array $data Associative array with item data.
     * @return self
     * @throws \InvalidArgumentException If required keys are missing or data is invalid.
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['id'], $data['name'], $data['price'], $data['quantity'])) {
            throw new \InvalidArgumentException('Item array must contain id, name, price, and quantity.');
        }

        return new self(
            id: $data['id'],
            name: $data['name'],
            price: (float) $data['price'],
            quantity: (int) $data['quantity'],
            category: $data['category'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert the DTO instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'category' => $this->category,
            'metadata' => $this->metadata,
        ];
    }
}
