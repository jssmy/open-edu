# Interface Contracts: Student Profile Data and Username Authentication

**Feature Branch**: `015-student-data-profile`  
**Date**: 2026-11-05  
**Spec Reference**: `specs/015-student-data-profile/spec.md`  
**Obsidian Note**: `[[REQ-026 - Perfil de Estudiante con Username Documento de Identidad y Fecha de Nacimiento]]`

---

## 1. Authentication Endpoint: `POST /login`

### Request Payload
```json
{
  "login": "ana_estudiante",
  "password": "Password123!"
}
```

### Flow
- `AuthController` validates `login` and `password`.
- If `login` contains an `@` and is a valid email, it authenticates against `email`.
- Otherwise, it authenticates against `username`.
- Rate limiting key: `strtolower($login).'|'.$request->ip()`.

---

## 2. Student Creation: `POST /students`

### Request Payload
```json
{
  "name": "Carlos Gomez",
  "identifier": "78912345",
  "username": "cgomez",
  "birth_date": "2010-08-20",
  "temporary_password": "Password123!"
}
```
*(No email or phone fields).*

### Response
- Redirects to `/students` with success flash message.

---

## 3. Student Update: `PUT /students/{id}`

### Request Payload
```json
{
  "name": "Carlos Gomez Actualizado",
  "identifier": "78912345",
  "username": "cgomez",
  "birth_date": "2010-08-20"
}
```
*(No email or phone fields).*

### Response
- Redirects to `/students/{id}` with success flash message.
