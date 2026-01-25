<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Exceptions\CartValidationException;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Services\CartService;

beforeEach(function () {
    $this->cartService = app(CartService::class);
    $this->cart = Cart::factory()->create();
});

test('validation passes with no validators', function () {
    $result = $this->cartService->validateForCheckout($this->cart, []);

    expect($result->isValid())->toBeTrue();
    expect($result->getMetadata()['validators_count'])->toBe(0);
});

test('validation passes with empty validators array', function () {
    $result = $this->cartService->validateForCheckout($this->cart);

    expect($result->isValid())->toBeTrue();
});

test('validation passes with single passing validator', function () {
    $validator = fn (Cart $cart, $next) => $next($cart);

    $result = $this->cartService->validateForCheckout($this->cart, [$validator]);

    expect($result->isValid())->toBeTrue();
    expect($result->getMetadata()['validators_run'])->toBe(1);
    expect($result->getMetadata()['validators_passed'])->toHaveCount(1);
});

test('validation passes with multiple passing validators', function () {
    $validator1 = fn (Cart $cart, $next) => $next($cart);
    $validator2 = fn (Cart $cart, $next) => $next($cart);
    $validator3 = fn (Cart $cart, $next) => $next($cart);

    $result = $this->cartService->validateForCheckout($this->cart, [
        $validator1,
        $validator2,
        $validator3,
    ]);

    expect($result->isValid())->toBeTrue();
    expect($result->getMetadata()['validators_run'])->toBe(3);
    expect($result->getMetadata()['validators_passed'])->toHaveCount(3);
});

test('validation fails with single failing validator', function () {
    $validator = function (Cart $cart, $next) {
        throw new CartValidationException('Cart is invalid', 'test_validator');
    };

    $result = $this->cartService->validateForCheckout($this->cart, [$validator]);

    expect($result->isValid())->toBeFalse();
    expect($result->getMessage())->toBe('Cart is invalid');
    expect($result->getErrors())->toBe(['Cart is invalid']);
    expect($result->getMetadata()['validators_failed'])->toBe(['test_validator']);
});

test('validation collects all errors from multiple failing validators', function () {
    $validator1 = function (Cart $cart, $next) {
        throw new CartValidationException('Error 1', 'validator_1');
    };

    $validator2 = function (Cart $cart, $next) {
        throw new CartValidationException('Error 2', 'validator_2');
    };

    $result = $this->cartService->validateForCheckout($this->cart, [
        $validator1,
        $validator2,
    ]);

    expect($result->isValid())->toBeFalse();
    expect($result->getErrors())->toBe(['Error 1', 'Error 2']);
    expect($result->getMetadata()['validators_failed'])->toBe(['validator_1', 'validator_2']);
});

test('validation tracks both passed and failed validators', function () {
    $passingValidator = fn (Cart $cart, $next) => $next($cart);

    $failingValidator = function (Cart $cart, $next) {
        throw new CartValidationException('Failed', 'failing_one');
    };

    $result = $this->cartService->validateForCheckout($this->cart, [
        $passingValidator,
        $failingValidator,
    ]);

    expect($result->isValid())->toBeFalse();
    expect($result->getMetadata()['validators_passed'])->toHaveCount(1);
    expect($result->getMetadata()['validators_failed'])->toBe(['failing_one']);
});

test('validation resolves class names from container', function () {
    // Bind a test validator
    app()->bind('TestPassingValidator', fn () => new class
    {
        public function __invoke(Cart $cart, $next)
        {
            return $next($cart);
        }

        public function getName(): string
        {
            return 'test_passing';
        }
    });

    $result = $this->cartService->validateForCheckout($this->cart, ['TestPassingValidator']);

    expect($result->isValid())->toBeTrue();
    expect($result->getMetadata()['validators_passed'])->toBe(['test_passing']);
});

test('validation supports handle method in validators', function () {
    $validator = new class
    {
        public function handle(Cart $cart, $next)
        {
            return $next($cart);
        }
    };

    $result = $this->cartService->validateForCheckout($this->cart, [$validator]);

    expect($result->isValid())->toBeTrue();
});

test('validation supports invokable validators', function () {
    $validator = new class
    {
        public function __invoke(Cart $cart, $next)
        {
            return $next($cart);
        }
    };

    $result = $this->cartService->validateForCheckout($this->cart, [$validator]);

    expect($result->isValid())->toBeTrue();
});

test('validation uses getName method when available', function () {
    $validator = new class
    {
        public function __invoke(Cart $cart, $next)
        {
            return $next($cart);
        }

        public function getName(): string
        {
            return 'custom_validator_name';
        }
    };

    $result = $this->cartService->validateForCheckout($this->cart, [$validator]);

    expect($result->getMetadata()['validators_passed'])->toBe(['custom_validator_name']);
});

test('validation includes error context in metadata', function () {
    $validator = function (Cart $cart, $next) {
        throw new CartValidationException(
            'Minimum order is $25',
            'minimum_order',
            ['current' => 10, 'required' => 25]
        );
    };

    $result = $this->cartService->validateForCheckout($this->cart, [$validator]);

    $errorDetails = $result->getMetadata()['error_details'];
    expect($errorDetails)->toHaveCount(1);
    expect($errorDetails[0]['validator'])->toBe('minimum_order');
    expect($errorDetails[0]['message'])->toBe('Minimum order is $25');
    expect($errorDetails[0]['context'])->toBe(['current' => 10, 'required' => 25]);
});

test('validation can access cart data in validators', function () {
    // Add an item to the cart
    $this->cartService->addItem($this->cart, [
        'product_id' => 'test-product',
        'name' => 'Test Product',
        'price' => 15.00,
        'quantity' => 2,
    ]);

    $this->cart->refresh();

    $validator = function (Cart $cart, $next) {
        if ($cart->subtotal < 50) {
            throw new CartValidationException(
                "Minimum order is \$50. Current: \${$cart->subtotal}",
                'minimum_order'
            );
        }

        return $next($cart);
    };

    $result = $this->cartService->validateForCheckout($this->cart, [$validator]);

    expect($result->isValid())->toBeFalse();
    expect($result->getMessage())->toContain('Minimum order is $50');
});

test('validation passes when cart meets requirements', function () {
    // Add items to meet minimum
    $this->cartService->addItem($this->cart, [
        'product_id' => 'test-product',
        'name' => 'Expensive Product',
        'price' => 100.00,
        'quantity' => 1,
    ]);

    $this->cart->refresh();

    $minimumValidator = function (Cart $cart, $next) {
        if ($cart->subtotal < 50) {
            throw new CartValidationException('Minimum order is $50', 'minimum_order');
        }

        return $next($cart);
    };

    $result = $this->cartService->validateForCheckout($this->cart, [$minimumValidator]);

    expect($result->isValid())->toBeTrue();
});

test('validation with mixed class and closure validators', function () {
    app()->bind('ClassValidator', fn () => new class
    {
        public function __invoke(Cart $cart, $next)
        {
            return $next($cart);
        }
    });

    $closureValidator = fn (Cart $cart, $next) => $next($cart);

    $result = $this->cartService->validateForCheckout($this->cart, [
        'ClassValidator',
        $closureValidator,
    ]);

    expect($result->isValid())->toBeTrue();
    expect($result->getMetadata()['validators_run'])->toBe(2);
});

test('validation first error message is used as primary message', function () {
    $validator1 = function (Cart $cart, $next) {
        throw new CartValidationException('First error message', 'validator_1');
    };

    $validator2 = function (Cart $cart, $next) {
        throw new CartValidationException('Second error message', 'validator_2');
    };

    $result = $this->cartService->validateForCheckout($this->cart, [
        $validator1,
        $validator2,
    ]);

    expect($result->getMessage())->toBe('First error message');
    expect($result->getErrors())->toContain('Second error message');
});
