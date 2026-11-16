# Quickstart Validation Guide: Role-Based Course Categories, Teacher Self-Enrollment, and Certificate Issuance

**Feature**: `018-course-categories-certificates-enrollment`
**Date**: 2026-11-15

## Prerequisites

- PHP 8.4, Composer, Node.js, PostgreSQL test/dev database.
- Reference documents:
  - [Data Model](file:///Users/jssmy/Documents/projects/open-edu/specs/018-course-categories-certificates-enrollment/data-model.md)
  - [Interface Contracts](file:///Users/jssmy/Documents/projects/open-edu/specs/018-course-categories-certificates-enrollment/contracts/categories-courses-enrollments-certificates.contract.md)

## Setup Commands

```bash
php artisan migrate
php artisan db:seed --class=CourseCategorySeeder
npm run build
```

## End-to-End Validation Scenarios

### 1. Verify Seeded Role-Based Categories & Default Student Role on Creation
1. Run `php artisan db:seed --class=CourseCategorySeeder`.
2. Confirm `"Onboarding"` and `"Capacitación"` have `role_name = 'docente'`, and `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"`, and `"Digital"` have `role_name = 'estudiante'`.
3. In `/courses`, click **"Nueva categoría"**: verify the role selector defaults to **Estudiante** (`estudiante`).

### 2. Create Course Selecting Only Category + Certificate Enabled by Default
1. Visit `/courses/create`.
2. Confirm **"Emitir certificado al finalizar el curso"** is checked (`true`) by default and that only the **Categoría** selector is needed (no separate target audience field).
3. Create and publish a course under `"Capacitación"` (`docente` role).

### 3. Verify Student-Only Categories in `/scheduling` and `/enrollments`
1. Visit `/scheduling` and `/enrollments`.
2. Confirm only courses belonging to `estudiante` categories appear; the `"Capacitación"` (`docente`) course is not shown in either module.

### 4. Verify Teacher Catalog & Self-Enrollment in `/courses` + PDF Certificate Generation
1. Log in as a `docente` user and visit `/courses`.
2. Confirm all published courses from `docente` categories (`"Onboarding"`, `"Capacitación"`) are displayed.
3. Click **"Inscribirme"** on the `"Capacitación"` course to self-enroll (`POST /courses/{course}/enroll`), complete its lessons and any published assessments in `/my/courses/{course}`, and click **"Obtener certificado"** to generate and download the `.pdf` certificate from the storage volume.

## Automated Test Command

```bash
php artisan test tests/Feature/Courses/CourseCategoriesCertificatesEnrollmentTest.php --compact
```
