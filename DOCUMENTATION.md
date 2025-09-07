# Simple Cart - Complete Documentation

## Table of Contents

1. [Installation & Setup](#installation--setup)
2. [Architecture Overview](#architecture-overview)
3. [Core Services](#core-services)
4. [Models](#models)
5. [Configuration](#configuration)
6. [Tax System](#tax-system)
7. [Discount System](#discount-system)
8. [Shipping System](#shipping-system)
9. [Events](#events)
10. [Commands](#commands)
11. [Exceptions](#exceptions)
12. [Usage Examples](#usage-examples)
13. [Best Practices](#best-practices)
14. [Performance Considerations](#performance-considerations)

## Installation & Setup

### Requirements
- PHP 8.1+
- Laravel 10.0+

### Installation Steps

1. Install via Composer:
```bash
composer require andreilungeanu/simple-cart
```

2. Publish and run migrations:
```bash
php artisan vendor:publish --tag="simple-cart-migrations"
php artisan migrate
```

3. Optionally publish configuration:
```bash
php artisan vendor:publish --tag="simple-cart-config"
```

### Package Registration

The package uses Laravel auto-discovery. The service provider `SimpleCartServiceProvider` automatically registers all services.

## Architecture Overview

The Simple Cart package follows a **service-based architecture** with clear separation of concerns:

- **Cart Facade**: Entry point for all cart operations
- **CartService**: Main business logic service
- **Calculator Services**: Specialized calculation logic (Tax, Discount, Shipping)
- **Models**: Data layer with minimal logic (Cart, CartItem)
- **Events**: Event-driven notifications for cart changes
- **Configuration**: Centralized configuration management

### Service Container Bindings

- `CartService`: Main cart operations service
- `TaxCalculator`: Tax calculation logic
- `DiscountCalculator`: Discount calculation logic  
- `ShippingCalculator`: Shipping calculation logic
- `CartConfiguration`: Configuration data object

## Core Services

### CartService

The `CartService` is the main service class that handles all cart operations.

#### Constructor Dependencies

```php
public function __construct(
    private CartConfiguration $config,
    private TaxCalculator $taxCalculator,
    private ShippingCalculator $shippingCalculator,
    private DiscountCalculator $discountCalculator,
) {}
```

#### Core Methods

##### Cart Management

**`create(?int $userId = null, ?string $sessionId = null): Cart`**

Creates a new cart instance.

```php
use AndreiLungeanu\SimpleCart\Facades\Cart;

// Create cart for authenticated user
$cart = Cart::create(userId: auth()->id());

// Create cart for guest with session
$cart = Cart::create(sessionId: session()->getId());

// Create cart with both (user takes precedence)
$cart = Cart::create(userId: 123, sessionId: 'session_abc');
```

**`find(string $cartId): ?Cart`**

Finds a cart by ID, returns null if not found.

```php
$cart = Cart::find('cart-uuid-here');
if ($cart) {
    // Cart found
}
```

**`findOrFail(string $cartId): Cart`**

Finds a cart by ID, throws `CartException` if not found.

```php
try {
    $cart = Cart::findOrFail('cart-uuid-here');
} catch (CartException $e) {
    // Handle cart not found
}
```

**`delete(Cart $cart): void`**

Permanently deletes a cart and triggers events.

```php
Cart::delete($cart);
```

**`clear(Cart $cart): void`**

Removes all items and resets cart data (taxes, discounts, shipping).

```php
Cart::clear($cart);
```

##### Item Management

**`addItem(Cart $cart, array $itemData): CartItem`**

Adds or updates an item in the cart.

```php
$item = Cart::addItem($cart, [
    'product_id' => 'prod_123',
    'name' => 'Premium Laptop',
    'price' => 1299.99,
    'quantity' => 1,           // Optional, defaults to 1
    'category' => 'electronics', // Optional
    'metadata' => [            // Optional
        'type' => 'laptop',
        'brand' => 'TechCorp',
        'sku' => 'TECH-LAP-001'
    ]
]);
```

**Required Fields:**
- `product_id`: Unique product identifier
- `name`: Product display name
- `price`: Unit price (must be >= 0)

**Optional Fields:**
- `quantity`: Item quantity (defaults to 1, must be >= 1)
- `category`: Product category for tax/discount calculations
- `metadata`: Additional product data (array)

**`updateQuantity(Cart $cart, string $productId, int $quantity): void`**

Updates the quantity of an existing item. If quantity <= 0, removes the item.

```php
// Update quantity
Cart::updateQuantity($cart, 'prod_123', 3);

// Remove item (quantity 0)
Cart::updateQuantity($cart, 'prod_123', 0);
```

**`removeItem(Cart $cart, string $productId): void`**

Removes an item from the cart.

```php
Cart::removeItem($cart, 'prod_123');
```

##### Calculation Methods

**`calculateSubtotal(Cart $cart): float`**

Returns the subtotal (sum of all line totals before taxes, shipping, discounts).

```php
$subtotal = Cart::calculateSubtotal($cart); // 1299.99
```

**`calculateShipping(Cart $cart): float`**

Calculates shipping cost considering free shipping thresholds and discounts.

```php
$shipping = Cart::calculateShipping($cart); // 15.99
```

**`calculateTax(Cart $cart): float`**

Calculates total tax based on applied tax configuration.

```php
$tax = Cart::calculateTax($cart); // 104.00
```

**`calculateTotal(Cart $cart): float`**

Calculates the final total (subtotal + shipping + tax - discounts).

```php
$total = Cart::calculateTotal($cart); // 1399.98
```

**`getCartSummary(Cart $cart): array`**

Returns a complete cart summary with all calculations.

```php
$summary = Cart::getCartSummary($cart);
/*
[
    'id' => 'cart-uuid',
    'item_count' => 3,
    'subtotal' => 1299.99,
    'shipping' => 15.99,
    'tax' => 104.00,
    'discounts' => 50.00,
    'total' => 1369.98,
    'status' => 'active',
    'expires_at' => '2025-10-07T12:00:00.000000Z'
]
*/
```

##### Tax Management

**`applyTax(Cart $cart, array $taxData): void`**

Applies tax configuration to the cart.

```php
Cart::applyTax($cart, [
    'code' => 'VAT_UK',
    'rate' => 0.20,
    'apply_to_shipping' => true,
    'conditions' => [
        'rates_per_category' => [
            'books' => 0.05,
            'electronics' => 0.25
        ],
        'rates_per_item' => [
            'prod_123' => 0.15
        ]
    ]
]);
```

**`removeTax(Cart $cart): void`**

Removes all tax configuration from the cart.

```php
Cart::removeTax($cart);
```

**`getAppliedTax(Cart $cart): ?array`**

Returns the currently applied tax configuration.

```php
$taxData = Cart::getAppliedTax($cart);
```

##### Discount Management

**`applyDiscount(Cart $cart, array $discountData): void`**

Applies a discount to the cart.

```php
Cart::applyDiscount($cart, [
    'code' => 'SAVE20',
    'type' => 'percentage',
    'value' => 20,
    'conditions' => [
        'minimum_amount' => 100,
        'category' => 'electronics'
    ]
]);
```

**`removeDiscount(Cart $cart, string $code): void`**

Removes a specific discount by code.

```php
Cart::removeDiscount($cart, 'SAVE20');
```

**`getAppliedDiscounts(Cart $cart): array`**

Returns all applied discounts.

```php
$discounts = Cart::getAppliedDiscounts($cart);
```

##### Shipping Management

**`applyShipping(Cart $cart, array $shippingData): void`**

Applies shipping method to the cart.

```php
Cart::applyShipping($cart, [
    'method_name' => 'Express Shipping',
    'cost' => 15.99,
    'carrier' => 'UPS',
    'estimated_days' => 2,
    'tracking_enabled' => true
]);
```

**`removeShipping(Cart $cart): void`**

Removes shipping method from the cart.

```php
Cart::removeShipping($cart);
```

**`getAppliedShipping(Cart $cart): ?array`**

Returns the applied shipping method.

```php
$shipping = Cart::getAppliedShipping($cart);
```

**`isFreeShippingApplied(Cart $cart): bool`**

Checks if free shipping is currently applied.

```php
$isFreeShipping = Cart::isFreeShippingApplied($cart);
```

## Models

### Cart Model

The `Cart` model represents a shopping cart instance.

#### Relationships

```php
// Get all cart items
$cart->items; // Collection<CartItem>
```

#### Computed Properties

```php
$cart->subtotal;    // float - Sum of all line totals
$cart->item_count;  // int - Total quantity of all items
```

#### Helper Methods

```php
$cart->isExpired(); // bool - Check if cart has expired
$cart->isEmpty();   // bool - Check if cart has no items
```

#### Cart Status Enum

```php
use AndreiLungeanu\SimpleCart\Enums\CartStatusEnum;

CartStatusEnum::Active;     // 'active'
CartStatusEnum::Abandoned;  // 'abandoned'
CartStatusEnum::Converted;  // 'converted'
CartStatusEnum::Expired;    // 'expired'
```

### CartItem Model

The `CartItem` model represents individual items within a cart.

#### Helper Methods

```php
$item->getLineTotal(); // float - price * quantity
```

## Configuration

The package configuration is located in `config/simple-cart.php`.

### Configuration Options

```php
return [
    'storage' => [
        'ttl_days' => env('CART_TTL_DAYS', 30),
        'cleanup_expired' => env('CART_CLEANUP_EXPIRED', true),
    ],

    'shipping' => [
        'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 0.00),
        // Set to null or 0 to disable threshold-based free shipping
    ],

    'discounts' => [
        'allow_stacking' => env('CART_ALLOW_DISCOUNT_STACKING', false),
        'max_discount_codes' => env('CART_MAX_DISCOUNT_CODES', 3),
    ],
];
```

### Environment Variables

```bash
# Cart storage settings
CART_TTL_DAYS=30
CART_CLEANUP_EXPIRED=true

# Free shipping threshold (set to 0 to disable)
CART_FREE_SHIPPING_THRESHOLD=100.00

# Discount settings
CART_ALLOW_DISCOUNT_STACKING=false
CART_MAX_DISCOUNT_CODES=3
```

## Tax System

The Simple Cart package features a **dynamic tax system** that provides complete flexibility for tax calculations.

### Tax Data Structure

```php
[
    'code' => 'TAX_CODE',              // Tax identifier
    'rate' => 0.0825,                  // Default tax rate (8.25%)
    'apply_to_shipping' => true,       // Apply tax to shipping cost
    'shipping_rate' => 0.06,           // Specific shipping tax rate (optional)
    'conditions' => [
        'rates_per_item' => [          // Item-specific rates (highest priority)
            'prod_123' => 0.15,
            'prod_456' => 0.05,
        ],
        'rates_per_category' => [      // Category-specific rates
            'books' => 0.05,
            'electronics' => 0.25,
            'luxury' => 0.30,
        ],
        'rates_per_type' => [          // Type-specific rates (from metadata)
            'digital' => 0.0,
            'physical' => 0.08,
        ]
    ]
]
```

### Tax Priority System

The tax calculator uses a priority-based system:

1. **Item-specific rates** (highest priority) - `rates_per_item[product_id]`
2. **Category-specific rates** - `rates_per_category[category]`
3. **Type-specific rates** - `rates_per_type[metadata.type]`
4. **Default rate** (lowest priority) - `rate`

### Tax Examples

#### Simple Tax Application

```php
Cart::applyTax($cart, [
    'code' => 'STATE_TAX',
    'rate' => 0.0625,
    'apply_to_shipping' => true
]);
```

#### Complex VAT System

```php
Cart::applyTax($cart, [
    'code' => 'EU_VAT_DE',
    'rate' => 0.19,                    // Standard VAT rate
    'apply_to_shipping' => true,
    'conditions' => [
        'rates_per_category' => [
            'books' => 0.07,           // Reduced rate for books
            'food' => 0.07,            // Reduced rate for food
            'luxury' => 0.19,          // Standard rate for luxury items
        ],
        'rates_per_item' => [
            'digital_service_123' => 0.0,  // Exempt digital service
        ],
        'rates_per_type' => [
            'digital' => 0.0,         // No tax on digital products
            'export' => 0.0,          // No tax on exports
        ]
    ]
]);
```

#### US State + Local Tax

```php
Cart::applyTax($cart, [
    'code' => 'US_CA_COMBINED',
    'rate' => 0.0975,                 // 7.25% state + 2.5% local
    'apply_to_shipping' => false,
    'conditions' => [
        'rates_per_category' => [
            'groceries' => 0.0,        // No tax on groceries
            'prepared_food' => 0.0975, // Full tax on prepared food
        ]
    ]
]);
```

#### API-Based Tax Integration

```php
// Example with external tax service
$apiTaxRates = TaxService::calculateRates($cart, $address);

Cart::applyTax($cart, [
    'code' => 'API_CALCULATED',
    'rate' => $apiTaxRates['default_rate'],
    'apply_to_shipping' => $apiTaxRates['tax_shipping'],
    'conditions' => [
        'rates_per_item' => $apiTaxRates['item_rates'] ?? []
    ]
]);
```

### Tax Helper Methods

```php
// Get effective tax rate for specific scenarios
$taxRate = app(TaxCalculator::class)->getEffectiveRate($cart, 'electronics');
$itemRate = app(TaxCalculator::class)->getEffectiveRate($cart, null, 'prod_123');
```

## Discount System

The discount system supports multiple discount types with flexible conditions.

### Discount Data Structure

```php
[
    'code' => 'DISCOUNT_CODE',
    'type' => 'percentage|fixed|free_shipping',
    'value' => 20,                     // 20% or $20 depending on type
    'conditions' => [
        'minimum_amount' => 100,       // Minimum cart subtotal
        'min_items' => 2,              // Minimum item quantity
        'item_id' => 'prod_123',       // Specific product required
        'category' => 'electronics',   // Specific category required
        'min_quantity' => 3,           // Minimum quantity (context-dependent)
    ]
]
```

### Discount Types

#### Percentage Discount

```php
Cart::applyDiscount($cart, [
    'code' => 'SAVE20',
    'type' => 'percentage',
    'value' => 20,                     // 20% off
    'conditions' => [
        'minimum_amount' => 50
    ]
]);
```

#### Fixed Amount Discount

```php
Cart::applyDiscount($cart, [
    'code' => 'SAVE10',
    'type' => 'fixed',
    'value' => 10.00,                  // $10 off
    'conditions' => [
        'min_items' => 2
    ]
]);
```

#### Free Shipping Discount

```php
Cart::applyDiscount($cart, [
    'code' => 'FREESHIP',
    'type' => 'free_shipping',
    'value' => 0,
    'conditions' => [
        'minimum_amount' => 75
    ]
]);
```

### Discount Conditions

#### Minimum Amount

```php
'conditions' => [
    'minimum_amount' => 100        // Cart subtotal must be >= $100
]
```

#### Item Quantity Requirements

```php
// Total cart items must be >= 3
'conditions' => [
    'min_items' => 3
]

// Specific product quantity must be >= 2
'conditions' => [
    'item_id' => 'prod_123',
    'min_quantity' => 2
]

// Category items quantity must be >= 3
'conditions' => [
    'category' => 'electronics',
    'min_quantity' => 3
]
```

#### Category-Specific Discounts

```php
Cart::applyDiscount($cart, [
    'code' => 'ELECTRONICS20',
    'type' => 'percentage',
    'value' => 20,
    'conditions' => [
        'category' => 'electronics',   // Only applies to electronics
        'minimum_amount' => 200
    ]
]);
```

#### Item-Specific Discounts

```php
Cart::applyDiscount($cart, [
    'code' => 'LAPTOP50',
    'type' => 'fixed',
    'value' => 50,
    'conditions' => [
        'item_id' => 'laptop_123',     // Only applies to specific product
        'min_quantity' => 1
    ]
]);
```

### Discount Stacking

Control discount stacking via configuration:

```php
// config/simple-cart.php
'discounts' => [
    'allow_stacking' => true,         // Allow multiple discounts
    'max_discount_codes' => 5,        // Maximum number of discount codes
],
```

## Shipping System

The shipping system provides flexible shipping cost calculation with free shipping support.

### Shipping Data Structure

```php
[
    'method_name' => 'Express Shipping',
    'cost' => 15.99,                   // Required: shipping cost
    'carrier' => 'UPS',                // Optional: carrier name
    'estimated_days' => 2,             // Optional: delivery estimate
    'tracking_enabled' => true,        // Optional: tracking availability
    'service_code' => 'UPS_EXPRESS',   // Optional: service identifier
]
```

### Applying Shipping

```php
Cart::applyShipping($cart, [
    'method_name' => 'Standard Shipping',
    'cost' => 8.99,
    'carrier' => 'USPS',
    'estimated_days' => 5
]);
```

### Free Shipping

Free shipping can be triggered by:

1. **Threshold-based** (configuration)
2. **Discount-based** (free_shipping discount type)

#### Threshold-Based Free Shipping

```php
// config/simple-cart.php
'shipping' => [
    'free_shipping_threshold' => 100.00,  // Free shipping over $100
],
```

#### Discount-Based Free Shipping

```php
Cart::applyDiscount($cart, [
    'code' => 'FREESHIP',
    'type' => 'free_shipping',
    'value' => 0
]);
```

### Checking Free Shipping

```php
$isFreeShipping = Cart::isFreeShippingApplied($cart);
```

### Priority System

Free shipping is applied in this order:
1. **Discount-based free shipping** (highest priority)
2. **Threshold-based free shipping** (lower priority)

## Events

The package dispatches events for all cart operations, enabling you to listen for cart changes.

### CartUpdated Event

```php
namespace AndreiLungeanu\SimpleCart\Events;

class CartUpdated
{
    public function __construct(
        public Cart $cart,
        public string $action = 'updated',
        public array $metadata = [],
    ) {}
}
```

### Event Actions

| Action | Triggered When | Metadata |
|--------|---------------|----------|
| `created` | Cart is created | `[]` |
| `item_added` | Item is added/updated | `['item' => CartItem]` |
| `item_updated` | Item quantity is updated | `['product_id' => string]` |
| `item_removed` | Item is removed | `['product_id' => string]` |
| `discount_applied` | Discount is applied | `['code' => string]` |
| `discount_removed` | Discount is removed | `['code' => string]` |
| `shipping_applied` | Shipping is applied | `['method' => string]` |
| `shipping_removed` | Shipping is removed | `[]` |
| `tax_applied` | Tax is applied | `['tax_data' => array]` |
| `tax_removed` | Tax is removed | `[]` |
| `cleared` | Cart is cleared | `[]` |
| `deleted` | Cart is deleted | `['cart_id' => string]` |

### Listening to Events

#### Using Event Listeners

```php
// app/Listeners/CartEventListener.php
namespace App\Listeners;

use AndreiLungeanu\SimpleCart\Events\CartUpdated;

class CartEventListener
{
    public function handle(CartUpdated $event): void
    {
        $cart = $event->cart;
        $action = $event->action;
        $metadata = $event->metadata;

        switch ($action) {
            case 'created':
                // Handle cart creation
                break;
            case 'item_added':
                // Handle item addition
                $item = $metadata['item'];
                break;
            case 'discount_applied':
                // Handle discount application
                $code = $metadata['code'];
                break;
        }
    }
}
```

#### Register in EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \AndreiLungeanu\SimpleCart\Events\CartUpdated::class => [
        \App\Listeners\CartEventListener::class,
    ],
];
```

#### Using Closures

```php
// In a service provider
Event::listen(CartUpdated::class, function (CartUpdated $event) {
    if ($event->action === 'item_added') {
        // Log item addition
        Log::info('Item added to cart', [
            'cart_id' => $event->cart->id,
            'product_id' => $event->metadata['item']->product_id
        ]);
    }
});
```

### Real-World Event Examples

#### Inventory Management

```php
Event::listen(CartUpdated::class, function (CartUpdated $event) {
    if ($event->action === 'item_added') {
        $item = $event->metadata['item'];
        InventoryService::reserveItem($item->product_id, $item->quantity);
    }
    
    if ($event->action === 'item_removed') {
        $productId = $event->metadata['product_id'];
        InventoryService::releaseItem($productId);
    }
});
```

#### Analytics Tracking

```php
Event::listen(CartUpdated::class, function (CartUpdated $event) {
    Analytics::track('cart_updated', [
        'cart_id' => $event->cart->id,
        'user_id' => $event->cart->user_id,
        'action' => $event->action,
        'cart_value' => Cart::calculateTotal($event->cart),
        'items_count' => $event->cart->item_count,
    ]);
});
```

## Commands

### Cart Cleanup Command

The package includes a command to clean up expired and abandoned carts.

```bash
php artisan simple-cart:cleanup
```

#### Command Options

```bash
# Force cleanup without confirmation
php artisan simple-cart:cleanup --force

# Set custom expiration period (default: 30 days)
php artisan simple-cart:cleanup --days=60

# Set custom status for expired carts
php artisan simple-cart:cleanup --status=abandoned
```

#### Command Behavior

1. **Mark Expired Carts**: Sets status to `expired` for carts past their `expires_at` date
2. **Delete Old Carts**: Permanently deletes carts older than specified days with `expired` or `abandoned` status
3. **Mark Empty Carts**: Sets status to `abandoned` for empty carts older than 1 day

#### Scheduling the Command

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('simple-cart:cleanup --force')
             ->daily()
             ->at('02:00');
}
```

## Exceptions

The package defines custom exceptions for better error handling.

### CartException

Main exception class for all cart-related errors.

#### Static Factory Methods

```php
use AndreiLungeanu\SimpleCart\Exceptions\CartException;

// Cart not found
CartException::cartNotFound($cartId);

// Invalid item data
CartException::invalidItemData($field);

// Invalid price
CartException::invalidPrice();

// Invalid quantity
CartException::invalidQuantity();

// Too many discount codes
CartException::tooManyDiscountCodes($maxCodes);

// Invalid shipping method
CartException::invalidShippingMethod($method);

// Invalid tax zone
CartException::invalidTaxZone($zone);

// Cart expired
CartException::cartExpired($cartId);
```

#### Exception Handling

```php
use AndreiLungeanu\SimpleCart\Exceptions\CartException;

try {
    $cart = Cart::findOrFail($cartId);
    Cart::addItem($cart, $itemData);
} catch (CartException $e) {
    // Handle cart-specific exceptions
    return response()->json(['error' => $e->getMessage()], 400);
} catch (\Exception $e) {
    // Handle general exceptions
    return response()->json(['error' => 'An error occurred'], 500);
}
```

## Usage Examples

### Complete E-commerce Flow

```php
use AndreiLungeanu\SimpleCart\Facades\Cart;

// 1. Create cart for user
$cart = Cart::create(userId: auth()->id());

// 2. Add products to cart
Cart::addItem($cart, [
    'product_id' => 'laptop_123',
    'name' => 'Gaming Laptop',
    'price' => 1299.99,
    'category' => 'electronics',
    'metadata' => ['brand' => 'TechCorp', 'warranty' => '2 years']
]);

Cart::addItem($cart, [
    'product_id' => 'mouse_456',
    'name' => 'Gaming Mouse',
    'price' => 79.99,
    'quantity' => 2,
    'category' => 'accessories'
]);

// 3. Apply tax based on user's location
Cart::applyTax($cart, [
    'code' => 'US_CA_TAX',
    'rate' => 0.0975,
    'apply_to_shipping' => true,
    'conditions' => [
        'rates_per_category' => [
            'electronics' => 0.0975,
            'accessories' => 0.08
        ]
    ]
]);

// 4. Apply discount code
Cart::applyDiscount($cart, [
    'code' => 'WELCOME10',
    'type' => 'fixed',
    'value' => 50,
    'conditions' => ['minimum_amount' => 100]
]);

// 5. Apply shipping method
Cart::applyShipping($cart, [
    'method_name' => 'Express Shipping',
    'cost' => 15.99,
    'carrier' => 'UPS',
    'estimated_days' => 2
]);

// 6. Get final cart summary
$summary = Cart::getCartSummary($cart);

// 7. Process checkout
// ... payment processing ...

// 8. Mark cart as converted
$cart->update(['status' => CartStatusEnum::Converted]);
```

### Multi-Region Tax Setup

```php
// European VAT
function applyEuVat(Cart $cart, string $country): void
{
    $vatRates = [
        'DE' => 0.19,
        'FR' => 0.20,
        'IT' => 0.22,
        'ES' => 0.21,
    ];

    Cart::applyTax($cart, [
        'code' => "EU_VAT_{$country}",
        'rate' => $vatRates[$country] ?? 0.20,
        'apply_to_shipping' => true,
        'conditions' => [
            'rates_per_category' => [
                'books' => 0.05,
                'food' => 0.10,
            ]
        ]
    ]);
}

// US State Tax
function applyUsTax(Cart $cart, string $state, string $county = null): void
{
    $stateTax = match($state) {
        'CA' => 0.0725,
        'NY' => 0.08,
        'TX' => 0.0625,
        default => 0.0
    };

    $localTax = $county ? 0.025 : 0.0; // Example local tax

    Cart::applyTax($cart, [
        'code' => "US_{$state}_TAX",
        'rate' => $stateTax + $localTax,
        'apply_to_shipping' => false,
    ]);
}
```

### Advanced Discount Scenarios

```php
// BOGO (Buy One Get One) Discount
Cart::applyDiscount($cart, [
    'code' => 'BOGO_SHOES',
    'type' => 'percentage',
    'value' => 50, // 50% off when buying 2+
    'conditions' => [
        'category' => 'shoes',
        'min_quantity' => 2
    ]
]);

// Volume Discount
Cart::applyDiscount($cart, [
    'code' => 'BULK_DISCOUNT',
    'type' => 'percentage',
    'value' => 15,
    'conditions' => [
        'min_items' => 10, // 15% off when buying 10+ items
        'minimum_amount' => 500
    ]
]);

// Customer Loyalty Discount
Cart::applyDiscount($cart, [
    'code' => 'VIP_MEMBER',
    'type' => 'percentage',
    'value' => 10,
    'conditions' => [
        'minimum_amount' => 1 // Always applies for VIP members
    ]
]);
```

### Dynamic Shipping Integration

```php
// Integration with shipping API
function calculateShippingRates(Cart $cart, array $address): array
{
    $shippingService = app(ShippingService::class);
    
    $items = $cart->items->map(function ($item) {
        return [
            'weight' => $item->metadata['weight'] ?? 1,
            'dimensions' => $item->metadata['dimensions'] ?? [10, 10, 10],
            'value' => $item->price
        ];
    })->toArray();

    return $shippingService->getRates($items, $address);
}

// Apply selected shipping method
function applySelectedShipping(Cart $cart, string $serviceCode): void
{
    $rates = calculateShippingRates($cart, session('shipping_address'));
    $selectedRate = collect($rates)->firstWhere('service_code', $serviceCode);

    if ($selectedRate) {
        Cart::applyShipping($cart, [
            'method_name' => $selectedRate['name'],
            'cost' => $selectedRate['cost'],
            'carrier' => $selectedRate['carrier'],
            'estimated_days' => $selectedRate['transit_time'],
            'service_code' => $serviceCode
        ]);
    }
}
```

## Best Practices

### Cart Management

1. **Always use the Cart facade** for operations
2. **Pass cart instances** as the first parameter to all methods
3. **Handle exceptions** appropriately
4. **Listen to events** for side effects (analytics, inventory, etc.)
5. **Clean up expired carts** regularly using the provided command


### Error Handling

1. **Use specific exception types**:
   ```php
   try {
       Cart::addItem($cart, $itemData);
   } catch (CartException $e) {
       // Handle cart-specific errors
   } catch (ValidationException $e) {
       // Handle validation errors
   }
   ```

2. **Provide meaningful error messages**:
   ```php
   catch (CartException $e) {
       return response()->json([
           'error' => 'Cart operation failed',
           'message' => $e->getMessage(),
           'code' => 'CART_ERROR'
       ], 400);
   }
   ```


## Performance Considerations

### Caching Strategies

1. **Cache cart summaries**:
   ```php
   public function getCachedSummary(Cart $cart): array
   {
       return Cache::remember("cart_summary_{$cart->id}", 300, function () use ($cart) {
           return Cart::getCartSummary($cart);
       });
   }
   ```

2. **Cache tax and shipping calculations**:
   ```php
   public function getCachedTax(Cart $cart): float
   {
       $key = "cart_tax_{$cart->id}_" . md5(json_encode($cart->tax_data));
       return Cache::remember($key, 600, function () use ($cart) {
           return Cart::calculateTax($cart);
       });
   }
   ```

3. **Invalidate cache on cart updates**:
   ```php
   Event::listen(CartUpdated::class, function (CartUpdated $event) {
       Cache::forget("cart_summary_{$event->cart->id}");
       Cache::forget("cart_tax_{$event->cart->id}_*");
   });
   ```


This documentation provides comprehensive coverage of the Simple Cart package. For additional examples or specific use cases, please refer to the package's test files or create an issue on the GitHub repository.
