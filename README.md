# Simple Cart Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)

**Modern Laravel shopping cart package with clean architecture**

## 🎯 Features

- ✅ **Minimal Codebase** - Only essential files with clear separation of concerns
- ✅ **Event-Driven Design** - Comprehensive listeners for cart lifecycle events
- ✅ **Advanced Calculations** - Multi-zone tax, flexible shipping, percentage/fixed discounts
- ✅ **Multiple Cart Instances** - Proper user/session isolation and state management
- ✅ **Fluent Interface** - Chainable API for intuitive cart manipulation
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
use AndreiLungeanu\SimpleCart\Cart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\Cart\Facades\CartOperations;

// Create cart and add items with method chaining
$cart = SimpleCart::create(userId: 'user-123', taxZone: 'US')
    ->addItem([
        'id' => 'prod_1',
        'name' => 'Laptop Pro',
        'price' => 1299.99,
        'quantity' => 1
    ])
    ->addItem([
        'id' => 'prod_2', 
        'name' => 'Wireless Mouse',
        'price' => 25.50,
        'quantity' => 2
    ]);

// Get calculations using stateless operations
$cartId = $cart->getId();
$subtotal = CartOperations::getSubtotal($cartId);  // 1350.99
$total = CartOperations::getTotal($cartId);        // Includes tax, shipping, etc.
$itemCount = CartOperations::getItemCount($cartId); // 3

// Apply discounts and shipping
$cart->applyDiscount([
        'code' => 'SAVE10',
        'type' => 'percentage', 
        'value' => 10.0
    ])
    ->setShippingMethod('express');

echo "Final Total: " . CartOperations::getTotal($cartId);
```

## 🛠️ Advanced Usage

### Tax Zone Configuration

```php
use AndreiLungeanu\SimpleCart\Cart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\Cart\Facades\CartOperations;

// Create cart with Romanian tax zone (19% VAT, 5% for books)
$cart = SimpleCart::create(taxZone: 'RO')
    ->addItem([
        'id' => 'book_123',
        'name' => 'Laravel Guide',
        'price' => 45.00,
        'quantity' => 1,
        'category' => 'books' // 5% VAT rate
    ])
    ->addItem([
        'id' => 'laptop_456',
        'name' => '15" Laptop',
        'price' => 899.99,
        'quantity' => 1 // Default 19% VAT rate
    ]);

$tax = CartOperations::getTaxAmount($cart->getId()); // Calculated per category
$total = CartOperations::getTotal($cart->getId());

// VAT exemption for business customers
$cart->setVatExempt(true);
$taxExempt = CartOperations::getTaxAmount($cart->getId()); // 0.00
```

### Shipping Methods

```php
// Configure shipping with different methods
$cart->setShippingMethod('standard'); // €5.99
$cart->setShippingMethod('express');  // €15.99

// Custom shipping configuration
$cart->setShippingMethod('express', [
    'vat_included' => false,
    'custom_rate' => 0.21
]);

$shipping = CartOperations::getShippingAmount($cart->getId());

// Free shipping threshold (configured at €100 by default)
if (CartOperations::isFreeShippingApplied($cart->getId())) {
    echo "Free shipping applied!";
}
```

### Discount System

```php
use AndreiLungeanu\SimpleCart\Cart\Enums\DiscountType;
use AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO;

// Multiple discount types
$cart->applyDiscount([
        'code' => 'FIXED10', 
        'type' => 'fixed',
        'value' => 10.00
    ])
    ->applyDiscount(new DiscountDTO(
        code: 'PERCENT15',
        type: DiscountType::Percentage,
        value: 15.0
    ));

$discountAmount = CartOperations::getDiscountAmount($cart->getId());
$finalTotal = CartOperations::getTotal($cart->getId());

// Remove specific discount
$cart->removeDiscount('FIXED10');
```

### Extra Costs & Fees

```php
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO;

// Add various extra costs
$cart->addExtraCost([
        'name' => 'Gift Wrapping',
        'amount' => 4.99,
        'type' => 'fixed'
    ])
    ->addExtraCost(new ExtraCostDTO(
        name: 'Insurance',
        amount: 2.5, // 2.5% of subtotal
        type: 'percentage'
    ))
    ->addExtraCost([
        'name' => 'Express Processing',
        'amount' => 12.00,
        'type' => 'fixed',
        'vatRate' => 0.19,
        'vatIncluded' => true
    ]);

$extraCosts = CartOperations::getExtraCostsTotal($cart->getId());
$grandTotal = CartOperations::getTotal($cart->getId());
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
        'settings' => [
            'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 100.00),
            'methods' => [
                'standard' => [
                    'name' => 'Standard Shipping',
                    'cost' => env('CART_STANDARD_SHIPPING_COST', 5.99),
                    'estimated_days' => '5-7',
                    'type' => 'flat',
                ],
                'express' => [
                    'name' => 'Express Shipping',
                    'cost' => env('CART_EXPRESS_SHIPPING_COST', 15.99),
                    'estimated_days' => '1-2',
                    'type' => 'flat',
                ],
            ],
        ],
    ],
    
    'discounts' => [
        'allow_stacking' => env('CART_ALLOW_DISCOUNT_STACKING', false),
        'max_discount_codes' => env('CART_MAX_DISCOUNT_CODES', 3),
    ],
];
```


### Run Tests

```bash
# Full test suite (99 tests, ~4.7s)
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
