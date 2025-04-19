<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Events\CartCleared;
use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use AndreiLungeanu\SimpleCart\Repositories\CartRepository;
use Illuminate\Support\Str;

class SimpleCart
{
    protected ?CartDTO $cart = null;

    public function __construct(
        protected readonly CartRepository $repository,
    ) {}

    public function create(): static
    {
        $this->cart = new CartDTO;
        event(new CartCreated($this->cart));

        return $this;
    }

    public function addItem(CartItemDTO $item): static
    {
        if (! $this->cart) {
            $this->create();
        }

        $this->cart->addItem($item);
        event(new CartUpdated($this->cart));

        return $this;
    }

    public function removeItem(string $itemId): static
    {
        // Implementation
        return $this;
    }

    public function clear(): static
    {
        $this->cart = null;
        event(new CartCleared);

        return $this;
    }

    public function get(): ?CartDTO
    {
        return $this->cart;
    }

    public function updateQuantity(string $itemId, int $quantity): static
    {
        if (! $this->cart) {
            throw new CartException('Cart not found');
        }

        $this->cart->updateItemQuantity($itemId, $quantity);
        event(new CartUpdated($this->cart));

        return $this;
    }

    public function applyDiscount(string $code): static
    {
        if (! $this->cart) {
            throw new CartException('Cart not found');
        }

        $this->cart->applyDiscount($code);
        event(new CartUpdated($this->cart));

        return $this;
    }

    public function addNote(string $note): static
    {
        if (! $this->cart) {
            throw new CartException('Cart not found');
        }

        $this->cart->addNote($note);
        event(new CartUpdated($this->cart));

        return $this;
    }

    public function save(): static
    {
        if (! $this->cart) {
            throw new CartException('Cart not found');
        }

        if (! $this->cart->id) {
            $this->cart = new CartDTO(
                id: (string) Str::uuid(),
                items: $this->cart->getItems()->toArray(),
                userId: $this->cart->userId,
                discounts: $this->cart->getDiscounts()->toArray(),
                notes: $this->cart->getNotes()->toArray(),
                extraCosts: $this->cart->getExtraCosts()->toArray(),
                shippingMethod: $this->cart->getShippingMethod(),
                taxZone: $this->cart->taxZone,
            );
        }

        $id = $this->repository->save($this->cart);

        return $this;
    }

    public function find(string $id): static
    {
        $this->cart = $this->repository->find($id);

        return $this;
    }

    public function total(): float
    {
        if (! $this->cart) {
            return 0.0;
        }

        return $this->cart->calculateTotal();
    }

    public function clone(): static
    {
        if (! $this->cart) {
            throw CartException::cartNotFound();
        }

        $newCart = new CartDTO(
            items: $this->cart->getItems()->toArray(),
            discounts: $this->cart->getDiscounts()->toArray(),
            shippingMethod: $this->cart->getShippingMethod(),
            taxZone: $this->cart->taxZone,
        );

        $this->cart = $newCart;

        return $this;
    }

    public function merge(CartDTO $otherCart): static
    {
        if (! $this->cart) {
            $this->create();
        }

        foreach ($otherCart->getItems() as $item) {
            $this->addItem($item);
        }

        foreach ($otherCart->getDiscounts() as $discount) {
            $this->cart->applyDiscount($discount->code);
        }

        event(new CartUpdated($this->cart));

        return $this;
    }

    protected function findItem(string $itemId): ?CartItemDTO
    {
        return $this->cart?->getItems()->first(fn ($item) => $item->id === $itemId);
    }
}
