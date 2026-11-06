# Research & Technical Decisions: Admin Password Reset and Public Recovery Removal

**Feature Branch**: `017-admin-password-reset`  
**Date**: 2026-11-05  
**Spec Reference**: `specs/017-admin-password-reset/spec.md`  
**Obsidian Note**: `[[REQ-028 - Restablecimiento de Contraseña Exclusivo por Administrador y Eliminación de Recuperacion en Login]]`

---

## Decision 1: Complete Deactivation of Public Recovery Routes and Login Link

### Context
Public self-service password recovery (`/forgot-password`, `/reset-password/{token}`) sends recovery links via email. However, students do not possess email accounts, and institutional security requires centralized credential control. Leaving the recovery link on `/login` causes confusion and security vulnerabilities.

### Decision
1. In `resources/js/pages/Auth/Login.vue`:
   - Remove the `<Link href="/forgot-password">¿Olvidaste tu contraseña?</Link>` element entirely.
2. In `routes/web.php`:
   - Disable or remove the public recovery routes (`/forgot-password`, `/reset-password`).
   - If an unauthenticated user attempts to visit `/forgot-password` or `/reset-password`, return HTTP 404 or redirect to `/login`.
3. In `PasswordController.php`:
   - Keep authenticated password change routes (`/password/change` and `password.change.update`) so users forced to change their password or already logged in can still manage their own passwords.

### Rationale
- Completely fulfills User Story 1 and requirement FR-001 / FR-002.
- Avoids dead-end recovery flows for users without emails.

---

## Decision 2: Administrator Password Reset Mechanism for Students

### Context
In `SaveStudentRequest`, `temporary_password` is currently validated as:
```php
'temporary_password' => [$this->isMethod('post') ? 'required' : 'sometimes', Password::min(8)],
```
However, in `StudentController::update`, `temporary_password` is not currently extracted or passed down to `UpdateStudent`, and `EloquentStudentRepository::update` only updates `name`, `username`, `email`, `identifier`, `birth_date`.

### Decision
1. Update `SaveStudentRequest`:
   - When updating (`PUT`), allow optional `password` (or `temporary_password`): `['nullable', 'string', Password::min(8)]`.
2. Update `UpdateStudent` use case:
   - Accept optional `?string $password = null`.
   - If provided, hash password with `PasswordHasher` and update `User` record setting `must_change_password = true`, clearing `remember_token`.
3. Update `EloquentStudentRepository::update`:
   - Accept `?string $passwordHash = null`. If present, update `users.password`, `users.must_change_password = true`, `users.remember_token = null`.
4. Update `resources/js/pages/Students/Show.vue`:
   - Add field "Nueva contraseña (opcional)" to the edit form in the modal/card.
   - If left blank, password remains untouched.
5. Audit log:
   - When a student's password is reset by an admin, log `student.password_reset` or `user.password_reset_by_admin`.

### Rationale
- Follows Hexagonal Architecture principles.
- Clean and non-breaking: omitting the password keeps existing password intact.

---

## Decision 3: Administrator Password Reset Mechanism for Teachers and Staff

### Context
In `UserController::update` and `UpdateUserRequest`:
- Currently only `name`, `email`, and `role` are updated.
- There is no password field in `Users/Edit.vue` or `UpdateUserRequest`.

### Decision
1. Update `UpdateUserRequest`:
   - Add `'password' => ['nullable', 'string', Password::min(8)]`.
2. Update `UpdateUser` use case:
   - Accept `?string $password = null`.
   - If `$password` is provided, hash with `PasswordHasher`, update `User` setting `must_change_password = true` and `remember_token = null`.
3. Update `UserRepository` and `EloquentUserRepository::update`:
   - Support optional password hash update and `must_change_password` toggle.
4. Update `resources/js/pages/Users/Edit.vue`:
   - Add `FormField id="password" label="Nueva contraseña (dejar en blanco para no cambiar)"` with password input.
5. Audit log:
   - Log `user.password_reset_by_admin` with actor ID and target user ID.

### Rationale
- Direct, unified experience for administrators managing teachers and staff.
- Guarantees `must_change_password = true` so teachers set their own private password on first login.

