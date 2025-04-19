<?php

namespace AndreiLungeanu\SimpleCart\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $guarded = [];

    protected $casts = [
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
