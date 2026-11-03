<?php

namespace App\Modules\Assessments\Domain;

use App\Shared\Exceptions\DomainException;

final readonly class Question
{
    /**
     * @param  list<array{text: string, is_correct: bool}>  $choices
     */
    public function __construct(
        public string $statement,
        public int $points,
        public array $choices,
    ) {
        if (trim($statement) === '') {
            throw new DomainException('Cada pregunta debe tener un enunciado.', 'questions');
        }

        if ($points < 1) {
            throw new DomainException('El puntaje de cada pregunta debe ser mayor que cero.', 'questions');
        }

        if (count($choices) < 2) {
            throw new DomainException('Cada pregunta debe tener al menos dos alternativas.', 'questions');
        }

        foreach ($choices as $choice) {
            new Choice($choice['text'], $choice['is_correct']);
        }

        if (count(array_filter($choices, fn (array $choice): bool => $choice['is_correct'])) !== 1) {
            throw new DomainException('Cada pregunta debe tener exactamente una alternativa correcta.', 'questions');
        }
    }
}
