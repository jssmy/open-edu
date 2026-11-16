# Implementation Plan: Role-Based Course Categories, Teacher Self-Enrollment, and Certificate Issuance

**Branch**: `018-course-categories-certificates-enrollment` | **Date**: 2026-11-15 | **Spec**: [spec.md](file:///Users/jssmy/Documents/projects/open-edu/specs/018-course-categories-certificates-enrollment/spec.md)
**Input**: Feature specification from `/specs/018-course-categories-certificates-enrollment/spec.md` (`[[REQ-034 - Categorias de Cursos Audiencia Docente-Estudiante y Emision de Certificados]]`)

## Summary

Update the course categories, course creation, scheduling, enrollment, and study workflows so that:
1. **Role-Bound Categories (`course_categories.role_name`)**: When creating a category (`POST /course-categories`), the administrator selects the role for which the category is available (`role_name`, defaulting to `'estudiante'`). `CourseCategorySeeder` seeds `"Onboarding"` and `"Capacitación"` with `role_name = 'docente'`, and `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"`, and `"Digital"` with `role_name = 'estudiante'`.
2. **Course Creation Selecting Only Category + Default Certificate Toggle + Clean `CourseCard`**: In `/courses/create` and `/courses/{course}/edit`, the administrator selects only the category (`category_id`, which determines the target role) and has **"Emitir certificado al finalizar"** (`issues_certificate`) checked (`true`) by default. In `CourseCard.vue`, the card displays the category badge (without showing the role badge), omits the eye (`view`) icon button, and links both the cover image preview and the course name to the course detail (`FR-012`).
3. **Student-Only Categories in `/scheduling` and `/enrollments`**: Academic Scheduling (`/scheduling`, `/scheduling/pending`, `POST /scheduling/offerings`) and Enrollments (`/enrollments`, `POST /enrollments`) show and accept **only** courses from `estudiante` categories.
4. **Role Catalog & Self-Enrollment in `/courses` for Non-Student Roles (`Docente`)**: When a `docente` user logs in and visits `/courses`, they see all published courses belonging to `docente` categories, can self-enroll directly (`POST /courses/{course}/enroll`) without a schedule, study lessons and assessments in `/my/courses/{course}`, and generate/download their landscape `.pdf` certificate stored in the filesystem volume with only `file_url` saved in `certificates`.

## Technical Context

**Language/Version**: PHP 8.4, TypeScript 5.x  
**Primary Dependencies**: Laravel 13, Inertia.js v3 (`@inertiajs/vue3`), Vue 3, Tailwind CSS v4, Spatie Laravel Permission  
**Storage**: PostgreSQL (`course_categories`, `courses`, `enrollments`, `lesson_progress`, `attempts`, `certificates`) + Local/Volume Filesystem Disk (`Storage::disk('local')` under `certificates/{code}.pdf`)  
**Testing**: Pest 4 (`php artisan test --compact`)  
**Target Platform**: Web application (desktop & mobile responsive)  
**Project Type**: Modular Monolith (Hexagonal Architecture under `app/Modules/*`)  
**Performance Goals**: Instant self-enrollment and on-demand PDF generation in < 1s  
**Constraints**: Zero binary blobs in `certificates` database table (store only `file_url`); `Domain` and `Application` layers must not import `Illuminate\*` (`tests/Arch/HexagonalTest.php`).  
**Scale/Scope**: Touches `Courses`, `Scheduling`, `Enrollments`, `Progress`, and `Assessments` modules.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Modular Monolith & Bounded Contexts**: **PASS**. Changes respect module boundaries (`Courses`, `Scheduling`, `Enrollments`, `Progress`, `Assessments`).
- **II. Hexagonal Architecture**: **PASS**. Domain and Application layers remain pure PHP without `Illuminate\*` imports.
- **III. Strict RBAC & Least Privilege**: **PASS**. Category creation requires `courses.manage`; self-enrollment verifies that the user holds the role configured on the course's category (`category.role_name`).
- **IV. Data Integrity & Concurrency**: **PASS**. Unique constraints on categories, enrollments, and certificates prevent duplicates.
- **V. Auditability**: **PASS**. Category creation, self-enrollment, and certificate issuance are logged via `Auditor`.
- **VI. Rich Content & Media Security**: **PASS**. Generated `.pdf` certificates reside in the filesystem volume and are streamed through an authorized controller endpoint.
- **VII. Automated Testing & Code Style**: **PASS**. Covered by Pest feature tests, `HexagonalTest`, Pint, and `npm run build`.

## Project Structure

### Documentation (this feature)

```text
specs/018-course-categories-certificates-enrollment/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── categories-courses-enrollments-certificates.contract.md
└── tasks.md
```

### Source Code (repository root)

```text
app/Modules/
├── Courses/
│   ├── Application/
│   │   └── CreateCourseCategory.php
│   ├── Domain/
│   │   ├── CourseRepository.php
│   │   └── CourseTargetAudience.php
│   └── Infrastructure/
│       ├── EloquentCourseRepository.php
│       ├── Http/
│       │   ├── CourseCategoryController.php
│       │   ├── CourseController.php
│       │   └── Requests/
│       │       ├── SaveCourseCategoryRequest.php
│       │       └── SaveCourseRequest.php
│       └── Models/
│           ├── Course.php
│           └── CourseCategory.php
├── Scheduling/
│   └── Infrastructure/
│       └── Http/
│           ├── SchedulingController.php
│           └── Requests/
│               └── SaveScheduleRequest.php
├── Enrollments/
│   ├── Application/
│   │   ├── EnrollStudent.php
│   │   └── EnrollTeacher.php
│   ├── Domain/
│   │   └── EnrollmentRepository.php
│   └── Infrastructure/
│       ├── EloquentEnrollmentRepository.php
│       ├── Http/
│       │   ├── EnrollmentController.php
│       │   └── Requests/
│       │       └── StoreEnrollmentRequest.php
│       └── Policies/
│           └── CourseAccessPolicy.php
└── Progress/
    ├── Application/
    │   └── IssueCourseCertificate.php
    └── Infrastructure/
        ├── CertificatePdfGenerator.php
        ├── EloquentProgressRepository.php
        └── Http/
            ├── CertificateController.php
            └── StudyController.php

database/
├── migrations/
│   └── 2026_11_15_000001_add_categories_audience_certificates_and_teacher_enrollments.php
└── seeders/
    └── CourseCategorySeeder.php

resources/
├── views/certificates/template.blade.php
└── js/
    ├── components/courses/CourseCard.vue
    └── pages/
        ├── Courses/
        │   ├── Edit.vue
        │   ├── Index.vue
        │   └── Show.vue
        ├── Enrollments/
        │   └── Index.vue
        └── Study/
            └── CourseViewer.vue
```

**Structure Decision**: Preserves existing modular monolith structure under `app/Modules/{Courses,Scheduling,Enrollments,Progress,Assessments}` and Vue pages under `resources/js/pages/`.

## Complexity Tracking

No constitution violations.
