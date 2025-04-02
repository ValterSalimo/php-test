<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception for resource not found errors
 */
class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Resource not found', array $details = [])
    {
        parent::__construct($message, 404, $details);
    }
}
