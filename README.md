# A simple cart for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)

A flexible shopping cart implementation for Laravel with support for multiple discounts, tax calculations, shipping, and extra costs.

## Features

- Fluent interface for easy cart management.
- Flexible tax calculation supporting multiple zones, categories, and VAT exemption.
- Configurable shipping methods and costs, including free shipping thresholds.
- Support for various discount types (e.g., fixed amount, percentage).
- Ability to add extra costs (e.g., gift wrapping, handling fees).
- Multiple persistence drivers (database, session - database default).
- Automatic calculation and storage of cart totals.
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

Publish the migration file (if using the default 'database' driver):
```bash
php artisan vendor:publish --tag="simple-cart-migrations"
php artisan migrate
```

## Configuration (`config/simple-cart.php`)

```php
<?php

use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\Services\DefaultTaxProvider;

return [
    // Persistence settings
    'storage' => [
        'driver' => env('CART_STORAGE_DRIVER', 'database'), // e.g., 'database', 'session'
        'ttl' => env('CART_TTL', 30 * 24 * 60 * 60), // 30 days in seconds
    ],

    // Tax calculation settings
    'tax' => [
        'provider' => DefaultTaxProvider::class, // Implement custom provider if needed
        'default_zone' => env('CART_DEFAULT_TAX_ZONE', 'US'), // Default tax zone if not specified
        'settings' => [
            'zones' => [
                // Example: United States configuration
                'US' => [
                    'name' => 'United States',
                    'default_rate' => env('CART_US_TAX_RATE', 0.0725), // Default rate for US
                    'apply_to_shipping' => false, // Apply tax to shipping cost?
                    'rates_by_category' => [ // Category-specific rates
                        'digital' => 0.0,
                        'food' => 0.03,
                    ],
                ],
                // Example: Romania configuration
                'RO' => [
                    'name' => 'Romania',
                    'default_rate' => env('CART_RO_TAX_RATE', 0.19),
                    'apply_to_shipping' => true,
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
                    'vat_included' => false, // Is the cost inclusive of VAT?
                    'vat_rate' => null, // null = use cart's default VAT rate, specific rate otherwise
                ],
                // Example: Express shipping method
                'express' => [
                    'cost' => env('CART_EXPRESS_SHIPPING_COST', 15.99),
                    'name' => 'Express Shipping',
                    'vat_included' => false,
                    'vat_rate' => null,
                ],
                // Add more methods as needed...
            ],
        ],
    ],
];

```

## Usage Examples

All examples use the `SimpleCart` facade, providing a fluent interface. The cart state is automatically persisted based on your configuration.

### Basic Operations

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

// Get the current cart (or create a new one if none exists)
$cart = SimpleCart::get(); // Returns the Cart model instance

// Add an item using the DTO
SimpleCart::addItem(new CartItemDTO(
    id: 'prod_123',
    name: 'Laptop Pro',
    price: 1299.99,
    quantity: 1
));

// Add another item using an associative array (alternative syntax)
SimpleCart::addItem([
    'id' => 'prod_456',
    'name' => 'Wireless Mouse',
    price: 25.50,
    quantity: 1
));

// Update an item's quantity
SimpleCart::updateItem('prod_123', ['quantity' => 2]);

// Remove an item
SimpleCart::removeItem('prod_456');

// Get calculated totals
$totals = SimpleCart::getTotals(); // Returns a TotalsDTO object
echo "Subtotal: " . $totals->subtotal;
echo "Total: " . $totals->total;

// Get the full cart details (items, totals, etc.)
$cartDetails = SimpleCart::get(); // Returns the Cart model instance with relations loaded
// Access items: $cartDetails->items
// Access totals: $cartDetails->totals (calculated on the fly if needed)

// Clear the cart
SimpleCart::clear();
```

### Tax Handling

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

// Set tax zone (e.g., based on user's country) - uses 'default_zone' from config if not set
SimpleCart::setTaxZone('RO'); // Use Romanian tax rules

// Add items with different tax categories (defined in config)
SimpleCart::addItem(new CartItemDTO(
    id: 'book_abc',
    name: 'Programming Guide',
    price: 45.00,
    quantity: 1,
    category: 'books' // Uses 5% VAT in RO zone
));
SimpleCart::addItem(new CartItemDTO(
    id: 'elec_xyz',
    name: 'Monitor',
    price: 300.00,
    quantity: 1 // Uses default 19% VAT in RO zone
));

// Mark the cart as VAT exempt (e.g., for B2B)
SimpleCart::setVatExempt(true);
// Recalculate totals if needed, or it happens automatically on getTotals() / get()

// Get totals (VAT will be 0 if exempt)
$totals = SimpleCart::getTotals();
echo "Tax Amount: " . $totals->taxAmount; // 0.00 if VAT exempt

// Unset VAT exemption
SimpleCart::setVatExempt(false);
```

### Shipping

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;

// Add items first...
// SimpleCart::addItem(...);

// Set the desired shipping method (key from config)
SimpleCart::setShippingMethod('express'); // Uses 'express' settings from config

// Get totals including shipping
$totals = SimpleCart::getTotals();
echo "Shipping Cost: " . $totals->shippingAmount;
echo "Total: " . $totals->total;

// Check if free shipping applies (based on 'free_shipping_threshold' in config)
// Add items until subtotal exceeds threshold...
$totals = SimpleCart::getTotals();
if ($totals->shippingAmount == 0) {
    echo "Free shipping applied!";
}

// Remove shipping selection
SimpleCart::removeShippingMethod();
```

### Discounts

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;

// Add items first...
// SimpleCart::addItem(...);

// Apply a fixed amount discount using the DTO
SimpleCart::applyDiscount(new DiscountDTO(
    code: 'WELCOME10',
    type: 'fixed', // 'fixed' or 'percentage'
    amount: 10.00,
    description: 'Welcome Offer'
));

// Apply a percentage discount using an array (alternative syntax)
SimpleCart::applyDiscount([
    'code' => 'SUMMER20',
    'type' => 'percentage',
    'amount' => 20.0, // 20%
    'description' => 'Summer Sale'
]);

// Get totals including discounts
$totals = SimpleCart::getTotals();
echo "Discount Amount: " . $totals->discountAmount;
echo "Total: " . $totals->total;

// Remove a specific discount
SimpleCart::removeDiscount('WELCOME10');

// Clear all discounts
SimpleCart::clearDiscounts();
```

### Extra Costs

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;

// Add items first...
// SimpleCart::addItem(...);

// Add a fixed handling fee using the DTO (VAT applied based on cart's tax settings)
SimpleCart::addExtraCost(new ExtraCostDTO(
    name: 'Handling Fee',
    amount: 5.00,
    type: 'fixed' // 'fixed' or 'percentage'
));

// Add a percentage-based insurance cost using an array (alternative syntax)
SimpleCart::addExtraCost([
    'name' => 'Insurance',
    'amount' => 1.5, // 1.5% of subtotal
    'type' => 'percentage'
]);

// Add gift wrapping using an array with specific VAT details (overrides cart default)
SimpleCart::addExtraCost([
    'name' => 'Gift Wrapping',
    'amount' => 7.50,
    'type' => 'fixed',
    vatRate: 0.19, // Specific VAT rate for this cost
    vatIncluded: true // Indicates the 7.50 already includes 19% VAT
));


// Get totals including extra costs
$totals = SimpleCart::getTotals();
echo "Extra Costs Total: " . $totals->extraCostsAmount;
echo "Total: " . $totals->total;

// Remove a specific extra cost by name
SimpleCart::removeExtraCost('Handling Fee');

// Clear all extra costs
SimpleCart::clearExtraCosts();
```

## Events

The package fires the following events, allowing you to hook into the cart lifecycle:

- `AndreiLungeanu\SimpleCart\Events\CartCreated`: Fired when a new cart is created.
- `AndreiLungeanu\SimpleCart\Events\CartUpdated`: Fired when items, discounts, shipping, etc., are modified.
- `AndreiLungeanu\SimpleCart\Events\CartCleared`: Fired when the cart is cleared.

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
