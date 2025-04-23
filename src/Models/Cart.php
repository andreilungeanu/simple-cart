<?php

namespace AndreiLungeanu\SimpleCart\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids; // Import the HasUuids trait if using Laravel 9+
use Illuminate\Database\Eloquent\Factories\HasFactory; // Optional: Add if using factories

/**
 * @property string $id
 * @property ?string $user_id
 * @property array $items
 * @property array $discounts
 * @property array $notes
 * @property array $extra_costs
 * @property ?string $shipping_method
 * @property ?string $tax_zone
 * @property bool $vat_exempt
 * @property ?float $tax_amount
 * @property ?float $shipping_amount
 * @property ?float $discount_amount
 * @property ?float $subtotal_amount
 * @property ?float $total_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Cart extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'id' => 'string',
        'items' => 'array',
        'discounts' => 'array',
        'notes' => 'array',
        'extra_costs' => 'array',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];
}
