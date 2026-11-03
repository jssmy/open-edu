<?php

namespace App\Shared\Audit;

interface Auditor
{
    /** @param array<string, mixed> $metadata */
    public function log(string $event, ?int $userId = null, ?string $subject = null, array $metadata = []): void;
}
