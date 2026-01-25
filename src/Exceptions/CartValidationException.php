<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Exceptions;

use Exception;

/**
 * Exception thrown when cart validation fails.
 *
 * Validators should throw this exception to signal validation failure.
 */
class CartValidationException extends Exception
{
    /**
     * @param  string  $message  The validation error message
     * @param  string  $validator  The name of the validator that failed
     * @param  array<string, mixed>  $context  Additional context about the failure
     */
    public function __construct(
        string $message,
        public readonly string $validator = 'unknown',
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }

    /**
     * Convert the exception to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'validator' => $this->validator,
            'context' => $this->context,
        ];
    }
}
