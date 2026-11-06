# Interface Contracts: Admin Password Reset and Public Recovery Removal

**Feature Branch**: `017-admin-password-reset`  
**Date**: 2026-11-05  
**Spec Reference**: `specs/017-admin-password-reset/spec.md`  
**Obsidian Note**: `[[REQ-028 - Restablecimiento de Contraseña Exclusivo por Administrador y Eliminación de Recuperacion en Login]]`

---

## 1. Route Disablement Contract

### Deactivated Public Endpoints
- `GET /forgot-password` → Returns `404 Not Found`
- `POST /forgot-password` → Returns `404 Not Found`
- `GET /reset-password/{token}` → Returns `404 Not Found`
- `POST /reset-password` → Returns `404 Not Found`

### Login View
- `GET /login`:
  - Does NOT include link to password recovery.

---

## 2. Student Password Reset Contract

### `PUT /students/{id}`
Updates student details and optionally resets their password.

#### Authorization
Requires permission: `students.manage`.

#### Request Payload
```json
{
  "name": "Carlos Gomez",
  "identifier": "78912345",
  "username": "cgomez",
  "birth_date": "2010-08-20",
  "temporary_password": "NewSecretPassword123!"
}
```

#### Validation Rules
- `name`: `['required', 'string', 'max:255']`
- `identifier`: `['required', 'string', 'max:50', Rule::unique('students', 'identifier')->ignore($studentId)]`
- `username`: `['required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($userId)]`
- `birth_date`: `['required', 'date', 'before:today']`
- `temporary_password`: `['nullable', 'string', Password::min(8)]`

#### Outcomes
- If `temporary_password` is provided, user's password is updated, `must_change_password` is set to `true`, and audit event `student.password_reset` is logged.

---

## 3. Teacher/User Password Reset Contract

### `PUT /users/{id}`
Updates user details and optionally resets their password.

#### Authorization
Requires permission: `users.manage`.

#### Request Payload
```json
{
  "name": "Prof. Juan Ramirez",
  "email": "docente01@openedu.test",
  "role": "docente",
  "password": "NewSecretPassword123!"
}
```

#### Validation Rules
- `name`: `['required', 'string', 'max:255']`
- `email`: `['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)]`
- `role`: `['required', Rule::exists('roles', 'name')->where('is_active', true)]`
- `password`: `['nullable', 'string', Password::min(8)]`

#### Outcomes
- If `password` is provided, user's password is updated, `must_change_password` is set to `true`, and audit event `user.password_reset_by_admin` is logged.

