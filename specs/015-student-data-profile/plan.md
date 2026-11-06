# Implementation Plan: Student Profile Data and Username Authentication

**Branch**: `015-student-data-profile` | **Date**: 2026-11-05 | **Spec**: [specs/015-student-data-profile/spec.md](file:///Users/jssmy/Documents/projects/open-edu/specs/015-student-data-profile/spec.md)  
**Obsidian Note**: `[[REQ-026 - Perfil de Estudiante con Username Documento de Identidad y Fecha de Nacimiento]]`

## Summary

Manage student identity and access in Open-Edu by strictly capturing **Nombre completo**, **Documento / Identificador**, **Nombre de usuario (`username`)**, **Fecha de nacimiento**, and **Contraseña inicial**, without collecting or displaying email or phone fields. Student authentication is conducted via `username` and password on the login screen, maintaining dual login capability for staff accounts with emails.

---

## Technical Context

**Language/Version**: PHP 8.4, TypeScript 5.x, Vue 3.5  
**Primary Dependencies**: Laravel 12 (monolith), Inertia.js v3 (`@inertiajs/vue3`), Tailwind CSS v4, Spatie Permission  
**Storage**: PostgreSQL / SQLite (testing), Eloquent ORM  
**Testing**: Pest PHP (`vendor/bin/pest`), Vue Test Utils  
**Target Platform**: Web application (Desktop/Mobile responsive)  
**Project Type**: Monolithic modular web application with Hexagonal Architecture  
**Performance Goals**: Fast login resolution (< 50ms), sub-second student listing with indexed search  
**Constraints**: Zero downtime/loss for existing users; email and phone completely eliminated from student UI; clean separation of concerns without leaking framework facades into Domain.  
**Scale/Scope**: Students module, Identity module, Login view, Student views.

---

## Constitution Check

- **Principle I: Monolito Modular con Arquitectura Hexagonal**: PASS.
- **Principle II & III: SOLID, DRY, KISS, YAGNI**: PASS.
- **Principle IV: Clean Code**: PASS.
- **Principle V & VI: Vue 3 Composition API & Design System**: PASS.
- **Principle VII: Calidad y Pruebas**: PASS.
- **Principle VIII: Seguridad**: PASS.

---

## Project Structure

### Documentation (this feature)

```text
specs/015-student-data-profile/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── contracts/
│   └── student-profile.contract.md
├── quickstart.md
└── checklists/
    └── requirements.md
```

### Source Code Implemented & Verified

```text
database/migrations/
├── 2026_11_05_000001_add_username_to_users_table.php
├── 2026_11_05_000002_add_birth_date_to_students_table.php
└── 2026_11_05_000003_populate_usernames_for_existing_users.php

app/
├── Models/User.php
├── Modules/Identity/Infrastructure/Http/AuthController.php
└── Modules/Students/
    ├── Domain/
    │   ├── Student.php
    │   └── StudentRepository.php
    ├── Application/
    │   ├── RegisterStudent.php
    │   └── UpdateStudent.php
    └── Infrastructure/
        ├── Models/Student.php
        ├── EloquentStudentRepository.php
        ├── StudentListQuery.php
        └── Http/
            ├── Requests/SaveStudentRequest.php
            └── StudentController.php

resources/js/pages/
├── Auth/Login.vue
└── Students/
    ├── Index.vue
    └── Show.vue
```
