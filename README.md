# A simple cart for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)

A flexible shopping cart implementation for Laravel with support for multiple discounts, tax calculations, and extra costs.

## Features

- Fluent interface for cart operations
- Support for multiple discount types (fixed, percentage)
- VAT rates based on country zones
- Extra costs handling (gift wrapping, handling fees, etc.)
- Cart persistence with automatic calculation storage
- Event-driven architecture
- Comprehensive test coverage

## Installation

```bash
composer require andreilungeanu/simple-cart
```

You can publish the configuration:
```bash
php artisan vendor:publish --tag="simple-cart-config"
```

## Basic Usage

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

// Create and add items
$cart = SimpleCart::create()
    ->addItem(new CartItemDTO(
        id: '1',
        name: 'Product',
        price: 99.99,
        quantity: 1
    ));

// Get totals
$subtotal = $cart->get()->getSubtotal(); // 99.99
$total = $cart->get()->calculateTotal(); // With tax if applicable
```

## Configuration

```php
return [
    'storage' => [
        'driver' => env('CART_STORAGE_DRIVER', 'database'),
        'ttl' => env('CART_TTL', 30 * 24 * 60 * 60), // 30 days
    ],
    'tax' => [
        'provider' => DefaultTaxProvider::class,
        'settings' => [
            'zones' => [
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
        'provider' => DefaultShippingProvider::class,
        'settings' => [
            'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 100.00),
            'methods' => [
                'standard' => [
                    'cost' => env('CART_STANDARD_SHIPPING_COST', 5.99),
                    'name' => 'Standard Shipping',
                    'vat_included' => false,
                    'vat_rate' => null, // Uses cart's VAT rate
                ],
            ],
        ],
    ],
];
```

## Examples

### Cart Operations

Basic cart with VAT:
```php
$cart = new CartDTO(taxZone: 'RO');
$cart->addItem(new CartItemDTO(
    id: '1',
    name: 'Product',
    price: 100.00,
    quantity: 1
)); // Uses default 19% VAT

$total = $cart->calculateTotal(); // 119.00 (including VAT)
```

B2B cart (VAT exempt):
```php
// Method 1: Set VAT exempt at creation
$cart = new CartDTO(taxZone: 'RO', vatExempt: true);
$cart->addItem(new CartItemDTO(
    id: '1',
    name: 'Product',
    price: 100.00,
    quantity: 1
));

// Method 2: Set VAT exempt after creation
$cart = new CartDTO(taxZone: 'RO');
$cart->setVatExempt(true); // All items become VAT exempt
$cart->addItem(new CartItemDTO(
    id: '2',
    name: 'Product',
    price: 100.00,
    quantity: 1
));

$total = $cart->calculateTotal(); // 200.00 (no VAT for any items)
```

### Shipping Examples

Basic shipping:
```php
$cart->setShippingMethod('standard', [
    'amount' => 5.99,
    'vat_included' => false,
    'vat_rate' => null // Uses cart's VAT rate
]);
```

Shipping with included VAT:
```php
$cart->setShippingMethod('express', [
    'amount' => 15.99,
    'vat_included' => true,
    'vat_rate' => 0.19
]);
```

### Custom Shipping Provider

```php
class CustomShippingProvider implements ShippingRateProvider
{
    public function getRate(CartDTO $cart, string $method): array
    {
        return [
            'amount' => 10.99,
            'vat_rate' => 0.19,
            'vat_included' => false,
        ];
    }

    public function getAvailableMethods(CartDTO $cart): array
    {
        return [
            'standard' => ['name' => 'Standard Delivery'],
            'express' => ['name' => 'Express Delivery'],
        ];
    }
}
```

### Extra Costs

Basic handling fee with default cart VAT:
```php
$cart->addExtraCost(new ExtraCostDTO(
    name: 'Handling',
    amount: 5.00,
    type: 'fixed',
    vatRate: null // Will use cart's default VAT rate
));
```

Extra cost with included VAT:
```php
$cart->addExtraCost(new ExtraCostDTO(
    name: 'Insurance',
    amount: 10.00,
    type: 'fixed',
    vatRate: 0.19,
    vatIncluded: true
));
```

Percentage-based fee with specific VAT:
```php
$cart->addExtraCost(new ExtraCostDTO(
    name: 'Processing Fee',
    amount: 2.5,
    type: 'percentage',
    vatRate: 0.10,
    vatIncluded: false
));
```

### Complete Example

```php
// Create cart with VAT zone
$cart = new CartDTO(taxZone: 'RO');

// Add regular items (19% VAT)
$cart->addItem(new CartItemDTO(
    id: '1',
    name: 'Electronics',
    price: 1000.00,
    quantity: 1
));

// Add reduced VAT items (5%)
$cart->addItem(new CartItemDTO(
    id: '2',
    name: 'Book',
    price: 50.00,
    quantity: 2,
    category: 'books'
));

// Add shipping with included VAT
$cart->setShippingMethod('express', [
    'amount' => 15.99,
    'vat_included' => true,
    'vat_rate' => 0.19
]);

// Add handling fee with cart's default VAT
$cart->addExtraCost(new ExtraCostDTO(
    name: 'Handling',
    amount: 5.00,
    type: 'fixed',
    vatRate: null
));

// Add insurance with specific VAT rate
$cart->addExtraCost(new ExtraCostDTO(
    name: 'Insurance',
    amount: 2,
    type: 'percentage',
    vatRate: 0.19,
    vatIncluded: false
));

// Calculate all components
$subtotal = $cart->getSubtotal();                // 1100.00 (1000 + 2×50)
$shippingCost = $cart->getShippingAmount();      // 15.99 (VAT included)
$extraCosts = $cart->getExtraCostsTotal();       // 27.00 (5.00 handling + 2% of subtotal)
$totalVat = $cart->getTaxAmount();               // 207.73 (items + shipping + extra costs VAT)
$total = $cart->calculateTotal();                // 1350.72

// Individual VAT breakdown:
// - Electronics: 190.00 (19% of 1000)
// - Books: 5.00 (5% of 100)
// - Handling fee: 0.95 (19% of 5)
// - Insurance: 11.78 (19% of 22)
// - Shipping: included in price
```

### Complete B2B Example (VAT Exempt)

```php
// Create VAT exempt cart (e.g., for B2B customers)
$cart = new CartDTO(taxZone: 'RO', vatExempt: true);

// Add items (no VAT will be applied)
$cart->addItem(new CartItemDTO(
    id: '1',
    name: 'Bulk Electronics',
    price: 1000.00,
    quantity: 5
));

$cart->addItem(new CartItemDTO(
    id: '2',
    name: 'Office Supplies',
    price: 100.00,
    quantity: 10,
    category: 'office'
));

// Add shipping (no VAT)
$cart->setShippingMethod('express', [
    'amount' => 25.99,
    'vat_included' => false,
    'vat_rate' => null
]);

// Add extra costs (no VAT)
$cart->addExtraCost(new ExtraCostDTO(
    name: 'Handling',
    amount: 15.00,
    type: 'fixed'
));

$cart->addExtraCost(new ExtraCostDTO(
    name: 'Insurance',
    amount: 1.5,
    type: 'percentage'
));

// Calculate all components
$subtotal = $cart->getSubtotal();           // 6000.00 (5×1000 + 10×100)
$shippingCost = $cart->getShippingAmount(); // 25.99
$extraCosts = $cart->getExtraCostsTotal();  // 105.00 (15.00 + 1.5% of 6000)
$totalVat = $cart->getTaxAmount();          // 0.00 (VAT exempt)
$total = $cart->calculateTotal();           // 6130.99

// Note: No VAT is calculated for any component due to VAT exempt status
```

## Events

The package fires these events:
- CartCreated
- CartUpdated
- CartCleared

## Testing

```bash
composer test
```

## Credits

- [Andrei Lungeanu](https://github.com/andreilungeanu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
