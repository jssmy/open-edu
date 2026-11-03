<?php

namespace App\Shared\Audit;

use Illuminate\Support\Facades\DB;

/** Registra eventos de auditoría; descarta cualquier dato sensible de los metadatos. */
class AuditLogger implements Auditor
{
    private const SENSITIVE = [
        'access_token',
        'api_key',
        'authorization',
        'current_password',
        'email',
        'new_password',
        'password',
        'password_confirmation',
        'refresh_token',
        'remember_token',
        'secret',
        'token',
    ];

    /** @param array<string, mixed> $metadata */
    public function log(string $event, ?int $userId = null, ?string $subject = null, array $metadata = []): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $userId,
            'event' => $event,
            'subject' => $subject,
            'metadata' => json_encode($this->sanitize($metadata)),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE, true)) {
                continue;
            }
            $clean[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $clean;
    }
}
