<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Models;

use AndreiLungeanu\SimpleCart\Enums\CartStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string|null $session_id
 * @property array|null $shipping_data
 * @property array|null $tax_data
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
        'shipping_data',
        'tax_data',
        'discount_data',
        'metadata',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_data' => 'array',
            'tax_data' => 'array',
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

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->items->sum(fn ($item) => $item->getLineTotal()), 2);
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Scope to filter active carts
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', CartStatusEnum::Active);
    }

    /**
     * Scope to filter abandoned carts
     */
    public function scopeAbandoned(Builder $query): void
    {
        $query->where('status', CartStatusEnum::Abandoned);
    }

    /**
     * Scope to filter expired carts
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('status', CartStatusEnum::Expired);
    }

    /**
     * Scope to filter carts that have not expired by status
     */
    public function scopeNotExpired(Builder $query): void
    {
        $query->where('status', '!=', CartStatusEnum::Expired);
    }

    /**
     * Scope to filter carts that have not expired by date yet
     */
    public function scopeNotExpiredByDate(Builder $query): void
    {
        $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to filter carts that expired before a given date
     */
    public function scopeExpiredBefore(Builder $query, $date): void
    {
        $query->where('expires_at', '<', $date);
    }

    /**
     * Scope to filter empty carts (no items)
     */
    public function scopeEmpty(Builder $query): void
    {
        $query->whereDoesntHave('items');
    }

    /**
     * Scope to filter carts for a specific user
     */
    public function scopeForUser(Builder $query, $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Scope to filter carts for a specific session
     */
    public function scopeForSession(Builder $query, $sessionId): void
    {
        $query->where('session_id', $sessionId);
    }

    /**
     * Scope to filter carts older than specified days
     */
    public function scopeOlderThan(Builder $query, int $days): void
    {
        $query->where('updated_at', '<', now()->subDays($days));
    }
}
