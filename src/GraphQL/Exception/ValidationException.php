<?php

namespace App\GraphQL\Exception;

use GraphQL\Error\ClientAware;

class ValidationException extends \RuntimeException implements ClientAware
{
    public function __construct(
        string $message,
        private readonly array $fieldErrors = [],
        private readonly string $errorCode = 'validation_error'
    ) {
        parent::__construct($message);
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'validation';
    }

    public function getFieldErrors(): array
    {
        return $this->fieldErrors;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
