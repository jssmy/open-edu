# Research & Technical Decisions: Student Profile Data and Username Authentication

**Feature Branch**: `015-student-data-profile`  
**Date**: 2026-11-05  
**Spec Reference**: `specs/015-student-data-profile/spec.md`  
**Obsidian Note**: `[[REQ-026 - Perfil de Estudiante con Username Documento de Identidad y Fecha de Nacimiento]]`

---

## Decision 1: Complete Elimination of Email and Phone from Student Interfaces

### Context
In Open-Edu, students do not possess nor use email accounts or personal telephone lines. Requiring, displaying, or collecting an email or phone for students introduces confusing inputs, fake placeholder data, and broken flows.

### Decision
- **Student Registration**: Form captures strictly:
  - `name` (Nombre completo)
  - `identifier` (Documento / Identificador)
  - `username` (Nombre de usuario para inicio de sesión)
  - `birth_date` (Fecha de nacimiento)
  - `temporary_password` (Contraseña inicial)
- **Eliminations**:
  - The email field and phone field are completely removed from the student registration modal (`Index.vue`) and student edit form / profile card (`Show.vue`).
  - No email or phone subtitle or column is displayed in `/students` table.
- **Persistence**:
  - `users.email` is stored as `null` for new student accounts.
  - Existing student records keep their legacy data in DB without error, but the UI ignores email and displays identity by `username` and `identifier`.

### Rationale
- Aligns 100% with real operational institutional constraints and the requirements note [[REQ-026 - Perfil de Estudiante con Username Documento de Identidad y Fecha de Nacimiento]].
- Eliminates unnecessary and confusing user inputs.

---

## Decision 2: Student Authentication via Username

### Context
Because students have no email addresses, they must log into the platform using their unique `username`. Meanwhile, existing teachers and administrators log in with their email or username.

### Decision
- In `resources/js/pages/Auth/Login.vue`:
  - Field label: "Usuario o Correo" (input `login`), type `text`, `autocomplete="username"`.
- In `AuthController::store`:
  - Detect input type: if `filter_var($login, FILTER_VALIDATE_EMAIL)` is true, resolve as `email`; otherwise resolve as `username`.
  - Rate limiting key: `strtolower($login).'|'.$request->ip()`.
  - On success, redirect to `/my/courses` for students or `/dashboard` for staff.

### Rationale
- Completely seamless experience: students use `username`, staff can use `email` or `username`.
- Retains brute-force protection through Laravel's `RateLimiter`.

---

## Decision 3: Population of Existing Records

### Context
Previous database seeds left students and users with `username = null`.

### Decision
- Created and executed migration `2026_11_05_000003_populate_usernames_for_existing_users.php` to generate unique `username` values for all existing users and students.
- Ensures zero null `username` entries remain in the system.
