<?php

namespace App\Modules\Scheduling\Domain;

use App\Shared\Exceptions\DomainException;

/** Violación de una regla de programación académica, asociada a un campo del formulario. */
class SchedulingRuleViolation extends DomainException
{
    public function __construct(string $field, string $message)
    {
        parent::__construct($message, $field);
    }
}
