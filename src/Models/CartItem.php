<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $cart_id
 * @property string $product_id
 * @property string $name
 * @property float $price
 * @property int $quantity
 * @property string|null $category
 * @property array $metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Cart $cart
 */
class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'name',
        'price',
        'quantity',
        'category',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function getLineTotal(): float
    {
        return round((float) $this->price * $this->quantity, 2);
    }
}
