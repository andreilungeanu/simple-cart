# Simple Cart Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)

**Modern Laravel shopping cart package with clean architecture**

> **Note**: This package uses a **service-based API** accessed through the `Cart` facade. All cart operations require passing the cart instance as the first parameter.

## 🎯 Features
- ✅ **Event-Driven Design** - Comprehensive listeners for cart lifecycle events
- ✅ **Advanced Calculations** - Multi-zone tax, flexible shipping, comprehensive discount system
- ✅ **Multiple Cart Instances** - Proper user/session isolation and state management
- ✅ **Service-Based API** - Clean service layer for cart operations
- ✅ **Database Persistence** - Reliable storage with automatic expiration handling


## 📦 Installation

Install via Composer:
```bash
composer require andreilungeanu/simple-cart
```

Publish and run migrations:
```bash
php artisan vendor:publish --tag="simple-cart-migrations"
php artisan migrate
```

Optionally publish the configuration:
```bash
php artisan vendor:publish --tag="simple-cart-config"
```

## 🚀 Quick Start

```php
use AndreiLungeanu\SimpleCart\Facades\Cart;

// Create cart and add items
$cart = Cart::create(userId: 123);

// Add items to cart
Cart::addItem($cart, [
    'product_id' => 'prod_1',
    'name' => 'Laptop Pro', 
    'price' => 1299.99,
    'quantity' => 1
]);

Cart::addItem($cart, [
    'product_id' => 'prod_2',
    'name' => 'Wireless Mouse',
    'price' => 25.50,
    'quantity' => 2
]);

// Get calculations
$subtotal = Cart::calculateSubtotal($cart);  // 1350.99
$shipping = Cart::calculateShipping($cart);
$tax = Cart::calculateTax($cart);
$discounts = Cart::calculateDiscounts($cart); // Applied discount amount
$total = Cart::calculateTotal($cart);        // Includes tax, shipping, discounts, etc.

// Apply discounts with dynamic data
$discountData = [
    'code' => 'SAVE50',
    'type' => 'fixed',
    'value' => 50,
    'conditions' => ['minimum_amount' => 100]
];
Cart::applyDiscount($cart, $discountData);

// Apply shipping
Cart::applyShipping($cart, [
    'method_name' => 'Express Shipping',
    'cost' => 15.99,
    'carrier' => 'UPS'
]);

Cart::setTaxZone($cart, 'US');

echo "Final Total: " . Cart::calculateTotal($cart);
```

## 🛠️ Advanced Usage

### Tax Zone Configuration

```php
use AndreiLungeanu\SimpleCart\Facades\Cart;

// Create cart with Romanian tax zone (19% VAT, 5% for books)
$cart = Cart::create(userId: 123);
Cart::setTaxZone($cart, 'RO');

Cart::addItem($cart, [
    'product_id' => 'book_123',
    'name' => 'Laravel Guide',
    'price' => 45.00,
    'quantity' => 1,
    'category' => 'books' // 5% VAT rate
]);

Cart::addItem($cart, [
    'product_id' => 'laptop_456',
    'name' => '15" Laptop',
    'price' => 899.99,
    'quantity' => 1 // Default 19% VAT rate
]);

$tax = Cart::calculateTax($cart); // Calculated per category
$total = Cart::calculateTotal($cart);

// Note: VAT exemption functionality not yet implemented
```

### Dynamic Shipping System

The Simple Cart package uses a **dynamic shipping system** where your application provides complete shipping data instead of relying on pre-configured shipping methods. This gives you full control over shipping rates, carriers, and delivery options.

```php
use AndreiLungeanu\SimpleCart\Facades\Cart;

// Create cart
$cart = Cart::create(userId: 123);

// Apply shipping with required data from your app (API, database, etc.)
Cart::applyShipping($cart, [
    'method_name' => 'Ground Shipping',
    'cost' => 12.99,
    // Optional: any additional data you want to store
    'carrier' => 'Local Courier'
]);

$shipping = Cart::calculateShipping($cart); // Returns 0.00 if free shipping threshold met

// Check if free shipping was automatically applied
if (Cart::isFreeShippingApplied($cart)) {
    echo "Free shipping applied! (subtotal over threshold)";
} else {
    echo "Shipping cost: $" . $shipping;
}

// To disable free shipping completely, set threshold to null in config:
// 'free_shipping_threshold' => null,  // Completely disable free shipping

// Note: Free shipping can be disabled by setting the threshold to null or 0 in config:
// 'free_shipping_threshold' => null,  // Completely disable free shipping
```

#### Simple Shipping Integration

```php
// Example: Your application provides shipping options from any source
$shippingOptions = [
    [
        'method_name' => 'Standard Shipping',
        'cost' => 9.99
    ],
    [
        'method_name' => 'Express Shipping', 
        'cost' => 19.99
    ]
];

// Let user select shipping option
$selectedOption = $shippingOptions[1]; // User's choice

// Apply selected shipping to cart
Cart::applyShipping($cart, $selectedOption);
```

#### Shipping Management

```php
// Get currently applied shipping
$appliedShipping = Cart::getAppliedShipping($cart);
if ($appliedShipping) {
    echo "Shipping: {$appliedShipping['method_name']} - \${$appliedShipping['cost']}";
}

// Remove shipping
Cart::removeShipping($cart);

// Apply different shipping (e.g., user changed selection)
Cart::applyShipping($cart, [
    'method_name' => 'Overnight Express',
    'cost' => 29.99
]);
```

#### Advanced Shipping Examples

```php
// Free shipping promotion
Cart::applyShipping($cart, [
    'method_name' => 'Free Standard Shipping',
    'cost' => 0
]);

// Express shipping
Cart::applyShipping($cart, [
    'method_name' => 'Express Delivery',
    'cost' => 25.99
]);

// Local pickup
Cart::applyShipping($cart, [
    'method_name' => 'Store Pickup',
    'cost' => 0
]);

// Store any metadata your app needs - all gets saved to shipping_data
Cart::applyShipping($cart, [
    'method_name' => 'Premium Delivery',
    'cost' => 75.00,
    'carrier' => 'Premium Logistics',
    'estimated_delivery' => '2-3 business days',
    'tracking_available' => true,
    'signature_required' => true,
    'insurance_included' => true,
    'notes' => 'White glove service with setup'
]);

// Later retrieve all stored shipping data
$shippingData = Cart::getAppliedShipping($cart);
// Returns complete array with all metadata:
// [
//     'method_name' => 'Premium Delivery',
//     'cost' => 75.00,
//     'carrier' => 'Premium Logistics', 
//     'estimated_delivery' => '2-3 business days',
//     'tracking_available' => true,
//     'signature_required' => true,
//     'insurance_included' => true,
//     'notes' => 'White glove service with setup'
// ]
```

**Shipping System Benefits:**
- ✅ **Simple requirements** - only needs method name and cost  
- ✅ **Complete flexibility** - any shipping method your app supports
- ✅ **Dynamic pricing** - promotional rates, time-sensitive pricing
- ✅ **Optional metadata** - store any additional data you need
- ✅ **Free shipping logic** - automatic free shipping when threshold met
- ✅ **No external dependencies** - works with your existing shipping logic

### Discount System

The Simple Cart package uses a **dynamic discount system** where your application provides complete discount data instead of relying on pre-configured discount codes. This gives you full control over discount logic, validation, and conditions.

```php
use AndreiLungeanu\SimpleCart\Facades\Cart;

// Create cart and add items
$cart = Cart::create(userId: 123);
Cart::addItem($cart, [
    'product_id' => 'prod_1',
    'name' => 'Gaming Laptop',
    'price' => 1200.00,
    'quantity' => 1,
    'category' => 'electronics'
]);

Cart::addItem($cart, [
    'product_id' => 'prod_2',
    'name' => 'Programming Book',
    'price' => 45.00,
    'quantity' => 2,
    'category' => 'books'
]);

// Apply discounts by providing complete discount data
$discountData = [
    'code' => 'SAVE10',
    'type' => 'fixed',
    'value' => 10,
    'conditions' => [
        'minimum_amount' => 50
    ]
];
Cart::applyDiscount($cart, $discountData);

// Apply percentage discount with category condition
$categoryDiscount = [
    'code' => 'ELECTRONICS20',
    'type' => 'percentage',
    'value' => 20,
    'conditions' => [
        'category' => 'electronics',
        'minimum_amount' => 1000
    ]
];
Cart::applyDiscount($cart, $categoryDiscount);

// Apply free shipping discount
$freeShippingDiscount = [
    'code' => 'FREESHIP',
    'type' => 'free_shipping',
    'value' => 0,
    'conditions' => [
        'minimum_amount' => 100
    ]
];
Cart::applyDiscount($cart, $freeShippingDiscount);

// Calculate totals with discounts applied
$subtotal = Cart::calculateSubtotal($cart);   // 1290.00
$discounts = Cart::calculateDiscounts($cart); // Calculated discount amount
$total = Cart::calculateTotal($cart);         // Final total with all discounts

// Remove specific discount
Cart::removeDiscount($cart, 'SAVE10');

// Get all applied discounts
$appliedDiscounts = Cart::getAppliedDiscounts($cart);
foreach ($appliedDiscounts as $code => $discountData) {
    echo "Discount {$code}: {$discountData['type']} - {$discountData['value']}\n";
}
```

### Advanced Discount Conditions

The package supports sophisticated discount conditions:

```php
// Item-specific discount (targets specific product)
$itemDiscount = [
    'code' => 'LAPTOP50',
    'type' => 'fixed',
    'value' => 50,
    'conditions' => [
        'item_id' => 'prod_1',          // Apply only to this product
        'min_quantity' => 1             // Minimum quantity required
    ]
];

// Category-based discount with quantity requirement
$categoryDiscount = [
    'code' => 'BOOKS15',
    'type' => 'percentage',
    'value' => 15,
    'conditions' => [
        'category' => 'books',          // Apply only to books category
        'min_quantity' => 3,            // Need at least 3 books
        'minimum_amount' => 75          // Minimum cart value
    ]
];

// Cart-wide discount with multiple conditions
$cartDiscount = [
    'code' => 'BULK25',
    'type' => 'percentage',
    'value' => 25,
    'conditions' => [
        'min_items' => 5,               // Minimum total items in cart
        'minimum_amount' => 200         // Minimum cart subtotal
    ]
];

Cart::applyDiscount($cart, $itemDiscount);
Cart::applyDiscount($cart, $categoryDiscount);
Cart::applyDiscount($cart, $cartDiscount);
```

**Supported Discount Types:**
- **Fixed Amount**: `$10 off`, `$50 off` - removes fixed dollar amount
- **Percentage**: `15% off`, `20% off` - percentage-based discount  
- **Free Shipping**: Eliminates shipping costs regardless of cart total

**Available Conditions:**
- **`minimum_amount`**: Minimum cart subtotal required
- **`min_items`**: Minimum total quantity of items in cart
- **`min_quantity`**: Minimum quantity for category/item-specific discounts
- **`category`**: Apply discount only to items in specific category
- **`item_id`**: Apply discount only to specific product ID

**Discount Priority Rules:**
1. **Item-specific discounts** (highest priority) - target individual products
2. **Category-specific discounts** (medium priority) - target product categories
3. **Cart-wide discounts** (lowest priority) - apply to entire cart

**Discount Rules:**
- ✅ **Dynamic discount data** - Your app provides complete discount information
- ✅ **Sophisticated conditions** - Category, item, quantity, and amount validation
- ✅ **Priority-based application** - Item → Category → Cart-wide precedence
- ✅ **Safety caps** - Discounts cannot exceed applicable item totals
- ✅ **Free shipping integration** - Returns actual shipping cost as discount amount
- ✅ **Stacking support** - Multiple discounts can be applied (configurable)

### Discount API Methods

```php
// Apply a discount with complete data
Cart::applyDiscount(Cart $cart, array $discountData): void

// Remove a discount by code
Cart::removeDiscount(Cart $cart, string $code): void

// Get all currently applied discounts
Cart::getAppliedDiscounts(Cart $cart): array

// Calculate total discount amount for cart
Cart::calculateDiscounts(Cart $cart): float
```

### Shipping API Methods

```php
// Apply shipping with complete data
Cart::applyShipping(Cart $cart, array $shippingData): void

// Remove shipping from cart
Cart::removeShipping(Cart $cart): void

// Get currently applied shipping data
Cart::getAppliedShipping(Cart $cart): ?array

// Calculate shipping cost for cart
Cart::calculateShipping(Cart $cart): float
```

### Cart Management

```php
use AndreiLungeanu\SimpleCart\Facades\Cart;

// Create and manage carts
$cart = Cart::create(userId: 123, sessionId: 'optional-session-id');

// Add items
Cart::addItem($cart, [
    'product_id' => 'prod_1',
    'name' => 'Test Product',
    'price' => 29.99,
    'quantity' => 2,
    'category' => 'electronics',
    'metadata' => ['color' => 'blue']
]);

// Update item quantities
Cart::updateQuantity($cart, 'prod_1', 5);

// Remove items
Cart::removeItem($cart, 'prod_1');

// Get cart summary
$summary = Cart::getCartSummary($cart);

// Clear all items
Cart::clear($cart);

// Delete cart
Cart::delete($cart);
```

## 🎯 Events

The package dispatches events for cart lifecycle management:

### CartUpdated Event

```php
use AndreiLungeanu\SimpleCart\Events\CartUpdated;

// Listen for cart events
Event::listen(CartUpdated::class, function (CartUpdated $event) {
    $cart = $event->cart;
    $action = $event->action; // 'created', 'updated', 'cleared', 'deleted'
    $metadata = $event->metadata; // Additional context data
    
    // Custom logic based on action
    match ($action) {
        'created' => Log::info("Cart {$cart->id} was created"),
        'updated' => Log::info("Cart {$cart->id} was updated"),
        'cleared' => Log::info("Cart {$cart->id} was cleared"),
        'deleted' => Log::info("Cart {$cart->id} was deleted"),
        default => Log::info("Cart {$cart->id} action: {$action}")
    };
});

// Or create a listener class
class CartEventListener
{
    public function handle(CartUpdated $event): void
    {
        // Handle cart analytics, notifications, etc.
        Analytics::track('cart_' . $event->action, [
            'cart_id' => $event->cart->id,
            'user_id' => $event->cart->user_id,
            'metadata' => $event->metadata,
        ]);
    }
}

## ⚙️ Configuration

The package is highly configurable. Publish the config file to customize:

```bash
php artisan vendor:publish --tag="simple-cart-config"
```

### Key Configuration Options

```php
// config/simple-cart.php
return [
    'storage' => [
        'ttl_days' => env('CART_TTL_DAYS', 30),
        'cleanup_expired' => env('CART_CLEANUP_EXPIRED', true),
    ],
    
    'tax' => [
        'default_zone' => env('CART_DEFAULT_TAX_ZONE', 'US'),
        'settings' => [
            'zones' => [
                'US' => [
                    'name' => 'United States',
                    'default_rate' => env('CART_US_TAX_RATE', 0.0725),
                    'apply_to_shipping' => false,
                    'rates_by_category' => [
                        'digital' => 0.0,
                        'food' => 0.03,
                    ],
                ],
                'RO' => [
                    'name' => 'Romania',
                    'default_rate' => env('CART_RO_TAX_RATE', 0.19),
                    'apply_to_shipping' => true,
                    'rates_by_category' => [
                        'books' => 0.05,
                        'food' => 0.09,
                    ],
                ],
            ],
        ],
    ],
    
    'shipping' => [
        'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 100.00),
        // Set to null or 0 to disable free shipping completely
        // 'free_shipping_threshold' => null,
        // Note: The package uses a dynamic shipping system where your application
        // provides complete shipping data via Cart::applyShipping() method.
        // This gives you full control over shipping rates, carriers, and delivery options.
    ],
    
    'discounts' => [
        'allow_stacking' => env('CART_ALLOW_DISCOUNT_STACKING', false),
        'max_discount_codes' => env('CART_MAX_DISCOUNT_CODES', 3),
        // Note: Discount codes are no longer configured here.
        // The package now uses a dynamic discount system where your application
        // provides complete discount data via Cart::applyDiscount() method.
        // This gives you full control over discount logic, validation, and conditions.
    ],
];
```


### Run Tests

```bash
# Full test suite (121 tests, ~3.4s)
composer test

# Parallel execution for faster results  
./vendor/bin/pest --parallel

# Performance profiling
./vendor/bin/pest tests/Performance/ --profile
```

## 🔧 Maintenance & Expiry

### Automatic Cart Cleanup

```bash
# Clean up expired and abandoned carts
php artisan simple-cart:cleanup

# Force cleanup without confirmation
php artisan simple-cart:cleanup --force

# Custom expiry period (default: 30 days)
php artisan simple-cart:cleanup --days=60

# Set status before deletion (default: abandoned)
php artisan simple-cart:cleanup --status=expired

# Add to scheduler for automatic cleanup
$schedule->command('simple-cart:cleanup --force')->daily();
```

### TTL Configuration

```php
// Carts expire after 30 days by default
'ttl_days' => 30,

// Custom expiry period
'ttl_days' => 60, // 60 days

// Or use environment variable
'ttl_days' => env('CART_TTL_DAYS', 30),
```

## 📄 License

The MIT License (MIT). See [License File](LICENSE.md) for more information.

## 👨‍💻 Credits

- **[Andrei Lungeanu](https://github.com/andreilungeanu)** - Package author
- **[Contributors](../../contributors)** - Community contributions

---

<div align="center">

**⭐ Star this repo if it helped you build amazing cart functionality! ⭐**

[![GitHub stars](https://img.shields.io/github/stars/andreilungeanu/simple-cart?style=social)](https://github.com/andreilungeanu/simple-cart/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/andreilungeanu/simple-cart?style=social)](https://github.com/andreilungeanu/simple-cart/network)

</div>
