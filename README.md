# A simple cart for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)

A flexible shopping cart implementation for Laravel with support for multiple discounts, tax calculations, shipping, and extra costs.

## Features

- Stateless manager accessed via `SimpleCart` Facade for safe interaction.
- **Fluent interface** for easy cart modification after creating or finding a cart.
- Manages cart state via `CartInstance` objects (used internally).
- Flexible tax calculation supporting multiple zones, categories, and VAT exemption.
- Configurable shipping methods and costs, including free shipping thresholds.
- Support for various discount types (e.g., fixed amount, percentage).
- Ability to add extra costs (e.g., gift wrapping, handling fees).
- Database persistence driver.
- Calculation of cart totals via dedicated manager methods.
- Event-driven architecture (`CartCreated`, `CartUpdated`, `CartCleared`).
- Comprehensive test suite.

## Installation

Install the package via composer:
```bash
composer require andreilungeanu/simple-cart
```

Publish the configuration file (optional):
```bash
php artisan vendor:publish --tag="simple-cart-config"
```
This will create `config/simple-cart.php`.

Publish and run the migration file:
```bash
php artisan vendor:publish --tag="simple-cart-migrations"
php artisan migrate
```

## Usage Examples

Use the `SimpleCart` facade to `create` or `find` a cart. These methods return a cart object that allows you to chain modification methods fluently. Calculation methods (`total`, `subtotal`, etc.) are called on the main `SimpleCart` Facade, passing the specific cart ID.

### Basic Operations

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

// Create a new cart instance (returns an object for chaining)
$cart = SimpleCart::create(userId: 'user-123', taxZone: 'RO');
$cartId = $cart->getId(); // Get the unique ID

// Chain methods on the cart object to modify it
$cart->addItem([ // Add item using an array
        'id' => 'prod_456',
        'name' => 'Wireless Mouse',
        'price' => 25.50,
        'quantity' => 1
     ])
     ->addItem(new CartItemDTO( // Add item using DTO
        id: 'prod_123',
        name: 'Laptop Pro',
        price: 1299.99,
        quantity: 1
     ))
     ->updateQuantity('prod_123', 2) // Update quantity
     ->removeItem('prod_456');

// Get calculated values using the Facade and cart ID
$subtotal = SimpleCart::subtotal($cartId);
$total = SimpleCart::total($cartId);
$itemCount = SimpleCart::itemCount($cartId);
echo "Item Count: " . $itemCount;
echo "Subtotal: " . $subtotal;
echo "Total: " . $total;

// Retrieve the cart object later
$loadedCart = SimpleCart::find($cartId); // Returns cart object or null
if ($loadedCart) {
    echo "Cart found: " . $loadedCart->getId();
    // $cartInstance = $loadedCart->getInstance(); // Get underlying data if needed
}

// Clear the cart's contents
$loadedCart?->clear();

// Delete the cart entirely via the Facade
SimpleCart::destroy($cartId);
```

### Tax Handling

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

// Create cart with a specific tax zone
$cart = SimpleCart::create(taxZone: 'RO');

// Add items
$cart->addItem([
        'id' => 'book_abc',
        'name' => 'Programming Guide',
        'price' => 45.00,
        'quantity' => 1,
        'category' => 'books' // Uses 5% VAT in RO zone
     ])
     ->addItem([
        'id' => 'elec_xyz',
        'name' => 'Monitor',
        'price' => 300.00,
        'quantity' => 1 // Uses default 19% VAT in RO zone
     ]);

// Get tax amount using Facade and ID
$tax = SimpleCart::taxAmount($cart->getId());
echo "Tax Amount: " . $tax;

// Mark the cart as VAT exempt
$cart->setVatExempt(true);

// Get tax amount again
$taxAfterExempt = SimpleCart::taxAmount($cart->getId());
echo "Tax Amount (Exempt): " . $taxAfterExempt; // 0.00

// Unset VAT exemption
$cart->setVatExempt(false);
```

### Shipping

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;

// Create cart and add items first...
$cart = SimpleCart::create(taxZone: 'RO');
// $cart->addItem(...);

// Set the desired shipping method
$cart->setShippingMethod('express', ['vat_included' => false]);

// Get calculated shipping cost
$shippingCost = SimpleCart::shippingAmount($cart->getId());
echo "Shipping Cost: " . $shippingCost;

// Get total including shipping
$total = SimpleCart::total($cart->getId());
echo "Total: " . $total;

// Check if free shipping was applied
if (SimpleCart::isFreeShippingApplied($cart->getId())) {
    echo "Free shipping applied!";
}

// To remove shipping selection, set method to an empty string or null
$cart->setShippingMethod('', []);
```

### Discounts

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;

// Create cart and add items first...
$cart = SimpleCart::create();
// $cart->addItem(...);

// Apply discounts
$cart->applyDiscount([ // Apply using array
        'code' => 'WELCOME10',
        'type' => 'fixed',
        'value' => 10.00, // Use 'value'
     ])
     ->applyDiscount(new DiscountDTO( // Apply using DTO
        code: 'SUMMER20',
        type: 'percentage',
        value: 20.0 // 20%
     ));

// Get calculated discount amount using Facade and ID
$discountAmount = SimpleCart::discountAmount($cart->getId());
echo "Discount Amount: " . $discountAmount;

// Get total including discounts
$total = SimpleCart::total($cart->getId());
echo "Total: " . $total;

// Remove a discount by its code
$cart->removeDiscount('WELCOME10');

// Recalculate total
$totalAfterRemove = SimpleCart::total($cart->getId());
echo "Total after removing WELCOME10: " . $totalAfterRemove;
```

### Extra Costs

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;

// Create cart and add items first...
$cart = Cart::create(taxZone: 'RO');
// $cart->addItem(...);

// Add extra costs
$cart->addExtraCost([ // Add using array
        'name' => 'Handling Fee',
        'amount' => 5.00,
        'type' => 'fixed'
     ])
     ->addExtraCost(new ExtraCostDTO( // Add using DTO
        name: 'Insurance',
        amount: 1.5, // 1.5% of subtotal
        type: 'percentage'
     ))
     ->addExtraCost([ // Add with specific VAT details
        'name' => 'Gift Wrapping',
        'amount' => 7.50,
        'type' => 'fixed',
        'vatRate' => 0.19,
        'vatIncluded' => true
     ]);

// Get total extra costs amount using Facade and ID
$extraCostsTotal = SimpleCart::extraCostsTotal($cart->getId());
echo "Extra Costs Total: " . $extraCostsTotal;

// Get total including extra costs
$total = SimpleCart::total($cart->getId());
echo "Total: " . $total;

// Remove an extra cost by name
$cart->removeExtraCost('Handling Fee');

// Recalculate total
$totalAfterRemove = SimpleCart::total($cart->getId());
echo "Total after removing Handling Fee: " . $totalAfterRemove;
```

## Configuration (`config/simple-cart.php`)

```php
<?php

use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\Services\DefaultTaxProvider;
use AndreiLungeanu\SimpleCart\Repositories\DatabaseCartRepository; // Default repository

return [
    // Persistence settings
    'storage' => [
        // 'driver' => 'database', // Only database driver is currently implemented via CartRepository binding
        'repository' => DatabaseCartRepository::class, // Specify the repository implementation
        'ttl' => env('CART_TTL', 30 * 24 * 60 * 60), // 30 days in seconds (Note: TTL logic needs implementation in repository/cleanup job)
    ],

    // Tax calculation settings
    'tax' => [
        'provider' => DefaultTaxProvider::class, // Implement custom provider if needed
        'default_zone' => env('CART_DEFAULT_TAX_ZONE', 'US'), // Default tax zone if not specified via create()
        'settings' => [
            'zones' => [
                // Example: United States configuration
                'US' => [
                    'name' => 'United States',
                    'default_rate' => env('CART_US_TAX_RATE', 0.0725), // Default rate for US
                    // 'apply_to_shipping' => false, // This logic is now handled within CartCalculator
                    'rates_by_category' => [ // Category-specific rates
                        'digital' => 0.0,
                        'food' => 0.03,
                    ],
                ],
                // Example: Romania configuration
                'RO' => [
                    'name' => 'Romania',
                    'default_rate' => env('CART_RO_TAX_RATE', 0.19),
                    // 'apply_to_shipping' => true,
                    'rates_by_category' => [
                        'books' => 0.05,
                        'food' => 0.09,
                    ],
                ],
                // Add more zones as needed...
            ],
        ],
    ],

    // Shipping calculation settings
    'shipping' => [
        'provider' => DefaultShippingProvider::class, // Implement custom provider if needed
        'settings' => [
            'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 100.00), // Subtotal threshold for free shipping
            'methods' => [
                // Example: Standard shipping method
                'standard' => [
                    'cost' => env('CART_STANDARD_SHIPPING_COST', 5.99),
                    'name' => 'Standard Shipping',
                    // 'vat_included' => false, // This is now passed via setShippingMethod()
                    'vat_rate' => null, // null = use cart's default VAT rate, specific rate otherwise (used by DefaultShippingProvider)
                ],
                // Example: Express shipping method
                'express' => [
                    'cost' => env('CART_EXPRESS_SHIPPING_COST', 15.99),
                    'name' => 'Express Shipping',
                    // 'vat_included' => false,
                    'vat_rate' => null,
                ],
                // Add more methods as needed...
            ],
        ],
    ],
];

```

## Events

The package fires the following events, allowing you to hook into the cart lifecycle:

- `AndreiLungeanu\SimpleCart\Events\CartCreated`: Fired when a new cart is created. Contains the `CartInstance`.
- `AndreiLungeanu\SimpleCart\Events\CartUpdated`: Fired when items, discounts, shipping, etc., are modified. Contains the updated `CartInstance`.
- `AndreiLungeanu\SimpleCart\Events\CartCleared`: Fired when the cart is cleared. Contains the `cartId`.

You can create listeners for these events as per standard Laravel event handling.

## Testing

Run the test suite using Pest:
```bash
composer test
```

## Credits

- [Andrei Lungeanu](https://github.com/andreilungeanu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
