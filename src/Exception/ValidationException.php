<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception for validation errors
 */
class ValidationException extends ApiException
{
    public function __construct(string $message = 'Validation failed', array $details = [])
    {
        parent::__construct($message, 400, $details);
    }
}
