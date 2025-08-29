<?php

namespace AndreiLungeanu\SimpleCart\Cart\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property ?string $user_id
 * @property array $items
 * @property array $discounts
 * @property array $notes
 * @property array $extra_costs
 * @property ?string $shipping_method
 * @property ?float $shipping_vat_rate
 * @property bool $shipping_vat_included
 * @property ?string $tax_zone
 * @property bool $vat_exempt
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
        'user_id' => 'string',
        'items' => 'array',
        'discounts' => 'array',
        'notes' => 'array',
        'extra_costs' => 'array',
        'shipping_method' => 'string',
        'shipping_vat_rate' => 'decimal:4',
        'shipping_vat_included' => 'boolean',
        'tax_zone' => 'string',
        'vat_exempt' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
