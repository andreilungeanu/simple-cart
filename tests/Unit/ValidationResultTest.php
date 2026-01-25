<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\ValidationResult;

test('success creates valid result', function () {
    $result = ValidationResult::success();

    expect($result->isValid())->toBeTrue();
    expect($result->hasFailed())->toBeFalse();
    expect($result->getMessage())->toBeNull();
    expect($result->getErrors())->toBeEmpty();
    expect($result->getMetadata())->toBeEmpty();
});

test('success accepts metadata', function () {
    $result = ValidationResult::success(['foo' => 'bar', 'count' => 5]);

    expect($result->isValid())->toBeTrue();
    expect($result->getMetadata())->toBe(['foo' => 'bar', 'count' => 5]);
});

test('failed creates invalid result', function () {
    $result = ValidationResult::failed('Something went wrong');

    expect($result->isValid())->toBeFalse();
    expect($result->hasFailed())->toBeTrue();
    expect($result->getMessage())->toBe('Something went wrong');
    expect($result->getErrors())->toBe(['Something went wrong']);
});

test('failed accepts multiple errors', function () {
    $result = ValidationResult::failed(
        'First error',
        ['First error', 'Second error', 'Third error']
    );

    expect($result->getMessage())->toBe('First error');
    expect($result->getErrors())->toBe(['First error', 'Second error', 'Third error']);
});

test('failed accepts metadata', function () {
    $result = ValidationResult::failed(
        'Error message',
        ['Error message'],
        ['validator' => 'test', 'context' => ['value' => 10]]
    );

    expect($result->getMetadata())->toBe([
        'validator' => 'test',
        'context' => ['value' => 10],
    ]);
});

test('failed defaults errors to message array when not provided', function () {
    $result = ValidationResult::failed('Single error');

    expect($result->getErrors())->toBe(['Single error']);
});

test('failed uses provided errors when given', function () {
    $result = ValidationResult::failed('Primary', ['Error 1', 'Error 2']);

    expect($result->getErrors())->toBe(['Error 1', 'Error 2']);
});

test('toArray returns correct structure for success', function () {
    $result = ValidationResult::success(['validators_count' => 3]);

    expect($result->toArray())->toBe([
        'is_valid' => true,
        'message' => null,
        'errors' => [],
        'metadata' => ['validators_count' => 3],
    ]);
});

test('toArray returns correct structure for failed', function () {
    $result = ValidationResult::failed(
        'Validation failed',
        ['Error 1', 'Error 2'],
        ['key' => 'value']
    );

    expect($result->toArray())->toBe([
        'is_valid' => false,
        'message' => 'Validation failed',
        'errors' => ['Error 1', 'Error 2'],
        'metadata' => ['key' => 'value'],
    ]);
});

test('constructor allows direct instantiation', function () {
    $result = new ValidationResult(
        isValid: false,
        message: 'Custom message',
        errors: ['Error 1'],
        metadata: ['custom' => true]
    );

    expect($result->isValid())->toBeFalse();
    expect($result->getMessage())->toBe('Custom message');
    expect($result->getErrors())->toBe(['Error 1']);
    expect($result->getMetadata())->toBe(['custom' => true]);
});

test('implements arrayable contract', function () {
    $result = ValidationResult::success();

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Support\Arrayable::class);
});
