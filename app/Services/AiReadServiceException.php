<?php

namespace App\Services;

use RuntimeException;

class AiReadServiceException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 500)
    {
        parent::__construct($message);
    }
}
