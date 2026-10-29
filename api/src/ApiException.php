<?php
declare(strict_types=1);

class ApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly string $errorCode = 'ERROR'
    ) {
        parent::__construct($message);
    }
}