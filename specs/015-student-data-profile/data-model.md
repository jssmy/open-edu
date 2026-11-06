# Data Model: Student Profile Data and Username Authentication

**Feature Branch**: `015-student-data-profile`  
**Date**: 2026-11-05  
**Spec Reference**: `specs/015-student-data-profile/spec.md`  
**Obsidian Note**: `[[REQ-026 - Perfil de Estudiante con Username Documento de Identidad y Fecha de Nacimiento]]`

---

## 1. Schema Modifications

### `users` Table

| Column | Type | Modifiers | Description |
|---|---|---|---|
| `username` | `VARCHAR(50)` | `NOT NULL / UNIQUE` | Unique login handle for user/student authentication |
| `email` | `VARCHAR(255)` | `NULLABLE / UNIQUE` | Nullable; `null` for students, populated for staff/administrators |

### `students` Table

| Column | Type | Modifiers | Description |
|---|---|---|---|
| `identifier` | `VARCHAR(50)` | `NOT NULL / UNIQUE` | Official document identifier (DNI, CE, Passport) |
| `birth_date` | `DATE` | `NULLABLE` | Student date of birth (mandatory on registration, nullable for legacy DB records) |
| `phone` | `VARCHAR(50)` | `NULLABLE` | Deprecated; not collected or displayed anywhere in student flows |

---

## 2. Domain & Application Contracts

### Domain Entity: `App\Modules\Students\Domain\Student`
```php
final readonly class Student
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public ?string $username,
        public ?string $email,
        public ?string $identifier,
        public ?string $birthDate,
        public ?string $phone,
        public bool $isActive,
    ) {}
}
```

### Form Request Validation: `SaveStudentRequest`
- `name`: `['required', 'string', 'max:255']`
- `identifier`: `['required', 'string', 'max:50', Rule::unique('students', 'identifier')->ignore($studentId)]`
- `username`: `['required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($userId)]`
- `birth_date`: `['required', 'date', 'before:today']`
- `temporary_password`: `[$isPost ? 'required' : 'sometimes', Password::min(8)]`
- *No email field accepted or requested for students.*
