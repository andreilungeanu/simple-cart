<?php

namespace AndreiLungeanu\SimpleCart\Repositories;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Models\Cart;
use Illuminate\Support\Str;

class DatabaseCartRepository implements CartRepository
{
    public function find(string $id): ?CartDTO
    {
        $cart = Cart::find($id);

        if (! $cart) {
            return null;
        }

        // Handle JSON data properly
        $items = is_string($cart->items) ? json_decode($cart->items, true) : ($cart->items ?? []);
        $discounts = is_string($cart->discounts) ? json_decode($cart->discounts, true) : ($cart->discounts ?? []);
        $notes = is_string($cart->notes) ? json_decode($cart->notes, true) : ($cart->notes ?? []);
        $extraCosts = is_string($cart->extra_costs) ? json_decode($cart->extra_costs, true) : ($cart->extra_costs ?? []);

        // Convert items to DTOs
        $items = collect($items)
            ->map(fn ($item) => new CartItemDTO(
                id: $item['id'],
                name: $item['name'],
                price: $item['price'],
                quantity: $item['quantity'],
                category: $item['category'] ?? null,
                metadata: $item['metadata'] ?? []
            ))
            ->toArray();

        return new CartDTO(
            id: $cart->id,
            items: $items,
            userId: $cart->user_id,
            discounts: $discounts,
            notes: $notes,
            extraCosts: $extraCosts,
            shippingMethod: $cart->shipping_method,
            taxZone: $cart->tax_zone,
        );
    }

    public function save(CartDTO $cart): string
    {
        $id = $cart->id ?: (string) Str::uuid();

        $data = [
            'id' => $id,
            'items' => json_encode($cart->getItems()->toArray()),
            'discounts' => json_encode($cart->getDiscounts()->toArray()),
            'notes' => json_encode($cart->getNotes()->toArray()),
            'extra_costs' => json_encode($cart->getExtraCosts()->toArray()),
            'user_id' => $cart->userId,
            'shipping_method' => $cart->getShippingMethod(),
            'tax_zone' => $cart->taxZone,
            'tax_amount' => $cart->getTaxAmount(),
            'shipping_amount' => $cart->getShippingAmount(),
            'discount_amount' => $cart->getDiscountAmount(),
            'subtotal_amount' => $cart->getSubtotal(),
            'total_amount' => $cart->calculateTotal(),

        ];

        Cart::updateOrCreate(['id' => $id], $data);

        return $id;
    }

    public function delete(string $id): void
    {
        Cart::destroy($id);
    }

    public function findByUser(string $userId): array
    {
        return Cart::where('user_id', $userId)
            ->get()
            ->map(fn ($cart) => new CartDTO(
                id: $cart->id,
                items: $cart->items ?? [],
                userId: $cart->user_id,
                discounts: $cart->discounts ?? [],
                notes: $cart->notes ?? [],
                extraCosts: $cart->extra_costs ?? [],
                shippingMethod: $cart->shipping_method,
                taxZone: $cart->tax_zone,
            ))
            ->toArray();
    }
}
