<?php

namespace AndreiLungeanu\SimpleCart\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids; // Import the HasUuids trait if using Laravel 9+
use Illuminate\Database\Eloquent\Factories\HasFactory; // Optional: Add if using factories

class Cart extends Model
{
    // Optional: Add HasFactory if needed
    // use HasFactory;

    // If using Laravel 9+, the HasUuids trait is the easiest way
    // use HasUuids;
    // If using older Laravel or not using the trait, configure manually:
    public $incrementing = false; // Tell Eloquent the ID is not auto-incrementing
    protected $keyType = 'string'; // Tell Eloquent the key type is string

    protected $guarded = [];

    protected $casts = [
        // Add 'id' => 'string' if not using HasUuids trait to ensure it's treated as string
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
