<?php

namespace App\Modules\Assessments\Domain;

use App\Shared\Exceptions\DomainException;

final readonly class Choice
{
    public function __construct(
        public string $text,
        public bool $isCorrect,
    ) {
        if (trim($text) === '') {
            throw new DomainException('Cada alternativa debe tener texto.', 'choices');
        }
    }
}
