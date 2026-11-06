# Quickstart & Validation Guide: Student Profile Data and Username Authentication

**Feature Branch**: `015-student-data-profile`  
**Date**: 2026-11-05  
**Spec Reference**: `specs/015-student-data-profile/spec.md`  
**Obsidian Note**: `[[REQ-026 - Perfil de Estudiante con Username Documento de Identidad y Fecha de Nacimiento]]`

---

## 1. Prerequisites & Environment Setup

Ensure migrations are up to date and dependencies installed:
```bash
php artisan migrate
npm run build
```

---

## 2. Automated Test Suite Execution

Run targeted Pest feature tests for student registration, profile management, and authentication:
```bash
# Student feature tests (registration, profile, validation, permissions)
php artisan test --filter=Student

# Authentication feature tests (username and email login, rate limiting)
php artisan test --filter=Auth
```

---

## 3. End-to-End Validation Scenarios

### Scenario A: Register Student with Document Identifier, Username, and Birth Date (No Phone/Email)
1. Log in as an Administrator (`admin@openedu.test`).
2. Navigate to `/students`.
3. Click "Nuevo estudiante" to open the creation modal.
4. Verify that:
   - Field **Nombre completo** is present.
   - Field **Documento de Identidad** (`identifier`) is present.
   - Field **Nombre de usuario** (`username`) is present.
   - Field **Fecha de nacimiento** (`birth_date`) is present.
   - Field **Contraseña temporal** is present.
   - **Teléfono** is NOT present.
5. Fill in the fields:
   - Nombre: `Mateo Alcantara`
   - Documento: `87654321`
   - Usuario: `malcantara`
   - Fecha nacimiento: `2011-03-25`
   - Contraseña: `Password123!`
6. Submit form.
7. Verify success toast notification and that Mateo appears in the students list displaying his identifier and username.

### Scenario B: Authenticate using Username
1. Log out or open an incognito session.
2. Go to `/login`.
3. Verify the first field asks for **Usuario o Correo**.
4. Enter `malcantara` in the identifier field and `Password123!` in the password field.
5. Click **Entrar**.
6. Verify student logs in successfully and is redirected to `/my-classes` (student home).

### Scenario C: Authenticate using Email for Existing Users
1. Log out.
2. Go to `/login`.
3. Enter `admin@openedu.test` and the administrator password.
4. Verify authentication succeeds and redirects to admin dashboard.

### Scenario D: Student Profile Verification & Edit
1. As administrator, go to `/students/{studentId}` for Mateo.
2. Verify the student detail card displays:
   - Documento Identificador: `87654321`
   - Fecha de nacimiento: `2011-03-25`
   - Usuario: `malcantara`
   - No phone field or placeholder is rendered.
3. Click "Editar estudiante", update the birth date or identifier, and verify changes persist accurately.

