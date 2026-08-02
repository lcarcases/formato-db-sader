<?php

namespace App\Core\Domain\Exceptions;

class {{DomainException}} extends \Exception
{
    public function __construct(
        string $message = "Mensaje de error por defecto",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
