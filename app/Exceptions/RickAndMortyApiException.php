<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class RickAndMortyApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
