# Implementation Plan: Admin Password Reset and Public Recovery Removal

**Branch**: `017-admin-password-reset` | **Date**: 2026-11-05 | **Spec**: [specs/017-admin-password-reset/spec.md](file:///Users/jssmy/Documents/projects/open-edu/specs/017-admin-password-reset/spec.md)  
**Obsidian Note**: `[[REQ-028 - Restablecimiento de Contraseña Exclusivo por Administrador y Eliminación de Recuperacion en Login]]`

## Summary

Centralize password reset capabilities exclusively to administrative users while removing public self-service password recovery from the application. Specifically: (1) remove the recovery link from `Login.vue` and disable public `/forgot-password` and `/reset-password` endpoints, (2) enable administrators to reset student passwords from the student management view (`/students/{id}`), and (3) enable administrators to reset teacher and staff passwords from the user management view (`/users/{id}`). In both reset workflows, newly assigned credentials force a password update (`must_change_password = true`) on the target user's next login.

---

## Technical Context

**Language/Version**: PHP 8.4, TypeScript 5.x, Vue 3.5  
**Primary Dependencies**: Laravel 12, Inertia.js v3 (`@inertiajs/vue3`), Tailwind CSS v4  
**Storage**: PostgreSQL / SQLite (testing), Eloquent ORM  
**Testing**: Pest PHP (`vendor/bin/pest`), Vue Test Utils  
**Target Platform**: Web application (Desktop/Mobile responsive)  
**Project Type**: Monolithic modular web application with Hexagonal Architecture  
**Performance Goals**: Instant credential hashing and persistence (< 50ms)  
**Constraints**: Keep authenticated password change (`/password/change`) functional for users forced to set new passwords; strict authorization checks (`students.manage`, `users.manage`).  
**Scale/Scope**: Modules: `Identity`, `Students`, routes, and views (`Login.vue`, `Users/Edit.vue`, `Students/Show.vue`).

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principle I: Monolito Modular con Arquitectura Hexagonal**: PASS. Changes to student and user credentials go through their respective application use cases (`UpdateStudent`, `UpdateUser`) and domain repository interfaces (`StudentRepository`, `UserRepository`).
- **Principle II & III: SOLID, DRY, KISS, YAGNI**: PASS. Reuses existing `PasswordHasher`, `Auditor`, and `EnsurePasswordChanged` middleware.
- **Principle IV: Clean Code**: PASS. Descriptive names, strict validation rules, and Pint compliance.
- **Principle V & VI: Vue 3 Composition API & Design System**: PASS. Consistent use of Design System tokens (`FormField`, `Input`, `Button`).
- **Principle VII: Calidad y Pruebas**: PASS. Tests for login screen, disabled recovery routes, admin student reset, and admin teacher reset.
- **Principle VIII: Seguridad**: PASS. Eliminates unauthenticated token-based email resets; strictly checks permissions and enforces 8-character minimums.

---

## Project Structure

### Documentation (this feature)

```text
specs/017-admin-password-reset/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── contracts/
│   └── admin-password-reset.contract.md
├── quickstart.md
└── checklists/
    └── requirements.md
```

### Source Code Changes Planned

```text
routes/
└── web.php                                                # Disable /forgot-password & /reset-password routes

resources/js/pages/
├── Auth/Login.vue                                         # Remove "¿Olvidaste tu contraseña?" link
├── Students/Show.vue                                      # Add password reset field to student edit card
└── Users/Edit.vue                                         # Add password reset field to user edit form

app/Modules/
├── Identity/
│   ├── Application/UpdateUser.php                         # Accept optional password, hash, set must_change_password
│   ├── Domain/UserRepository.php                          # Signature update for password update
│   └── Infrastructure/
│       ├── EloquentUserRepository.php                     # Update password hash & must_change_password
│       └── Http/
│           ├── Requests/UpdateUserRequest.php             # Validate optional password (min 8)
│           └── UserController.php                         # Pass password to UpdateUser
└── Students/
    ├── Application/UpdateStudent.php                      # Accept optional password, hash, set must_change_password
    ├── Domain/StudentRepository.php                       # Signature update for password update
    └── Infrastructure/
        ├── EloquentStudentRepository.php                  # Update password hash & must_change_password
        └── Http/
            ├── Requests/SaveStudentRequest.php            # Validate optional temporary_password (min 8)
            └── StudentController.php                      # Pass temporary_password to UpdateStudent

tests/Feature/
├── Identity/
│   ├── PublicPasswordRecoveryDisabledTest.php             # Tests for 404 on recovery routes & no login link
│   └── UserManagementTest.php                             # Tests for teacher password reset by admin
└── Students/
    └── StudentManagementTest.php                          # Tests for student password reset by admin
```

---

## Re-evaluation of Constitution Check Post-Design

- **Hexagonal Architecture**: Preserved. Use cases handle the password hashing and audit logging; repositories perform the atomic database updates.
- **Frontend Consistency**: Preserved. Reusing `FormField` and `Input` from `@/design-system/`.
- **All Gates Passed**: Ready for `/speckit-tasks`.
