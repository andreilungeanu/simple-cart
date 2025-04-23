<?php

namespace AndreiLungeanu\SimpleCart\Repositories;

use AndreiLungeanu\SimpleCart\Models\Cart;
use Illuminate\Support\Str;

class DatabaseCartRepository implements CartRepository
{
    public function find(string $id): ?array
    {
        $cart = Cart::find($id);

        if (! $cart) {
            return null;
        }

        return [
            'id' => $cart->id,
            'items' => $cart->items ?? [],
            'discounts' => $cart->discounts ?? [],
            'notes' => $cart->notes ?? [],
            'extra_costs' => $cart->extra_costs ?? [],
            'user_id' => $cart->user_id,
            'shipping_method' => $cart->shipping_method,
            'tax_zone' => $cart->tax_zone,
        ];
    }

    public function save(array $cartData): string
    {
        $id = $cartData['id'] ?? (string) Str::uuid();

        $dataToSave = [
            'id' => $id,
            'items' => $cartData['items'] ?? [], // Let Eloquent casting handle encoding
            'discounts' => $cartData['discounts'] ?? [], // Let Eloquent casting handle encoding
            'notes' => $cartData['notes'] ?? [], // Let Eloquent casting handle encoding
            'extra_costs' => $cartData['extra_costs'] ?? [], // Let Eloquent casting handle encoding
            'user_id' => $cartData['user_id'] ?? null,
            'shipping_method' => $cartData['shipping_method'] ?? null,
            'tax_zone' => $cartData['tax_zone'] ?? null,
        ];

        Cart::updateOrCreate(['id' => $id], $dataToSave);

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
            ->map(fn(Cart $cart) => [
                'id' => $cart->id,
                'items' => $cart->items ?? [],
                'user_id' => $cart->user_id,
                'discounts' => $cart->discounts ?? [],
                'notes' => $cart->notes ?? [],
                'extra_costs' => $cart->extra_costs ?? [],
                'shipping_method' => $cart->shipping_method,
                'tax_zone' => $cart->tax_zone,
            ])
            ->toArray();
    }
}
