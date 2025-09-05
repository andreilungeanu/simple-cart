<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Models;

use AndreiLungeanu\SimpleCart\Enums\CartStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string|null $session_id
 * @property string|null $tax_zone
 * @property string|null $shipping_method
 * @property array $discount_data
 * @property array $metadata
 * @property CartStatusEnum $status
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CartItem> $items
 * @property-read int $items_count
 * @property-read float $subtotal
 * @property-read int $item_count
 */
class Cart extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'session_id',
        'tax_zone',
        'shipping_method',
        'discount_data',
        'metadata',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_data' => 'array',
            'metadata' => 'array',
            'status' => CartStatusEnum::class,
            'expires_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Simple getters - NO business logic/calculations in models
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    // Computed properties for read-only access
    public function getSubtotalAttribute(): float
    {
        return round($this->items->sum(fn ($item) => $item->getLineTotal()), 2);
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }
}
