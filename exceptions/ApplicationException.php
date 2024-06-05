<?php

namespace WebApiCore\Exceptions;

use Exception;
use Throwable;

class ApplicationException extends Exception
{
    private array $innerErrors;
    public function __construct(string $message, private readonly int $httpStatusCode = 500, int $code = 100, array $innerErrors = [], Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->innerErrors = $innerErrors;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getInnerErrors(): array
    {
        return $this->innerErrors;
    }
}
