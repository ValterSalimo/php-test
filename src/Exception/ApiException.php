<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * Base exception class for API-related errors
 */
class ApiException extends Exception
{
    protected array $details = [];

    public function __construct(string $message, int $code = 400, array $details = [])
    {
        parent::__construct($message, $code);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
