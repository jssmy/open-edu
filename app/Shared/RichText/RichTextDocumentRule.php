<?php

namespace App\Shared\RichText;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class RichTextDocumentRule implements ValidationRule
{
    public function __construct(private ?int $courseId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! RichTextDocumentValidator::isValid($value, $this->courseId)) {
            $fail('El contenido enriquecido tiene una estructura o un enlace no permitido.');
        }
    }
}
