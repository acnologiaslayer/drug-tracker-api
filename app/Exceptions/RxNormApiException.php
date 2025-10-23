<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class RxNormApiException extends Exception
{
    public function __construct(string $message, private readonly int $statusCode = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
