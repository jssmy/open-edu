<?php

namespace App\Shared\Exceptions;

use RuntimeException;

/** Violación de una regla de negocio; se responde como 422 (validación). */
class DomainException extends RuntimeException
{
    public function __construct(string $message, private readonly string $field = 'general')
    {
        parent::__construct($message);
    }

    public function field(): string
    {
        return $this->field;
    }
}
