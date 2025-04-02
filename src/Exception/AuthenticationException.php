<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception for authentication failures
 */
class AuthenticationException extends ApiException
{
    public function __construct(string $message = 'Authentication failed', array $details = [])
    {
        parent::__construct($message, 401, $details);
    }
}
