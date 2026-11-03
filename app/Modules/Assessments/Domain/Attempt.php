<?php

namespace App\Modules\Assessments\Domain;

use App\Shared\Exceptions\DomainException;

final readonly class Attempt
{
    public function __construct(
        public int $attemptNumber,
        public int $maxAttempts,
    ) {
        if ($attemptNumber < 1 || $maxAttempts < 1 || $attemptNumber > $maxAttempts) {
            throw new DomainException('No quedan intentos disponibles.', 'assessment');
        }
    }
}
