<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents the result of cart validation.
 *
 * @implements Arrayable<string, mixed>
 */
class ValidationResult implements Arrayable
{
    /**
     * @param  bool  $isValid  Whether the validation passed
     * @param  string|null  $message  Primary error message (null if valid)
     * @param  array<int, string>  $errors  All error messages
     * @param  array<string, mixed>  $metadata  Additional context (passed/failed validators, etc.)
     */
    public function __construct(
        private bool $isValid,
        private ?string $message = null,
        private array $errors = [],
        private array $metadata = []
    ) {}

    /**
     * Check if the validation passed.
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Check if the validation failed.
     */
    public function hasFailed(): bool
    {
        return ! $this->isValid;
    }

    /**
     * Get the primary error message.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get all error messages.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Create a successful validation result.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function success(array $metadata = []): self
    {
        return new self(isValid: true, metadata: $metadata);
    }

    /**
     * Create a failed validation result.
     *
     * @param  string  $message  Primary error message
     * @param  array<int, string>  $errors  All error messages (defaults to [$message])
     * @param  array<string, mixed>  $metadata  Additional context
     */
    public static function failed(string $message, array $errors = [], array $metadata = []): self
    {
        return new self(
            isValid: false,
            message: $message,
            errors: $errors ?: [$message],
            metadata: $metadata
        );
    }

    /**
     * Convert the validation result to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'message' => $this->message,
            'errors' => $this->errors,
            'metadata' => $this->metadata,
        ];
    }
}
