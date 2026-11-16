# Phase 0 Research: Role-Based Course Categories, Teacher Self-Enrollment, and Certificate Issuance

**Feature**: `018-course-categories-certificates-enrollment`
**Date**: 2026-11-15

## Decision 1: Role-Targeted Course Categories (`course_categories.role_name`)

- **Decision**: Add `role_name` (`varchar(64) NOT NULL DEFAULT 'estudiante'`) to `course_categories`, referencing the system role name (Spatie `roles.name`, defaulting to `'estudiante'`). When creating a category via `POST /course-categories`, the administrator selects the role (`role_name`), which defaults to `'estudiante'` in the UI modal.
- **Rationale**: Binding the role directly to the category eliminates redundant audience selection when creating a course: selecting a category automatically determines which role the course belongs to, whether it participates in Academic Scheduling (`/scheduling`) and Student Enrollment (`/enrollments`), or whether it is exposed in `/courses` for self-enrollment by users of that role.
- **Alternatives Considered**:
  - *Separate `target_audience` dropdown on `courses`*: Rejected per user clarification—selecting the role at the category level is cleaner and avoids per-course duplication.

## Decision 2: Default Category Seeder (`CourseCategorySeeder`) with Role Mapping

- **Decision**: Seed the 8 predefined categories with explicit `role_name` assignments:
  - Role `docente`: `"Onboarding"`, `"Capacitación"`.
  - Role `estudiante`: `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"`, `"Digital"`.
- **Rationale**: Matches institutional structure where Onboarding and Capacitación are faculty training categories (`docente`) while the 6 academic/formative areas belong to `estudiante`.

## Decision 3: Filtering Academic Scheduling (`/scheduling`) and Enrollments (`/enrollments`) to Student Categories Only

- **Decision**:
  - In `SchedulingController` (`index`, `pending`, `store`) and `SaveSchedule`: only courses belonging to `estudiante` categories (or legacy uncategorized student courses) are visible and eligible for `CourseOffering` creation. Attempting to create a `CourseOffering` for a course whose category has `role_name !== 'estudiante'` throws a validation/domain error.
  - In `EnrollmentController` (`index`, `store`): `/enrollments` is exclusively for enrolling students in student-category courses (`role_name = 'estudiante'`) via `CourseOffering` with schedule and capacity checks. Non-student roles do not appear in `/enrollments`.
- **Rationale**: Directly enforces the requirement: *"En programación académica y matrícula solo serán visibles las categorías de los estudiantes. Los otros roles no tendrán programación académica ni matrícula."*

## Decision 4: Role-Filtered Catalog and Self-Enrollment in `/courses` for Non-Student Roles (e.g. `Docente`)

- **Decision**:
  - When a user without `courses.manage` (such as a `docente`) visits `GET /courses`:
    - `CourseController@index` displays **all published courses** whose category has `role_name` matching any of the user's roles (e.g. `docente`), along with a category filter limited to their role's categories.
    - Each course card indicates whether the user is already enrolled (`is_enrolled = true`, showing **"Continuar curso"** linking to `/my/courses/{course}`) or not yet enrolled (`is_enrolled = false`, showing **"Inscribirme y tomar curso"** which posts to `POST /courses/{course}/enroll`).
  - `POST /courses/{course}/enroll` (`CourseSelfEnrollmentController@store` / `EnrollTeacher` use case) verifies that the course is published and its category's `role_name` matches the authenticated user's role, creates (or reactivates) an `Enrollment` record (`user_id = $user->id`, `student_id = null`, `course_offering_id = null`, `status = 'active'`), and redirects directly to `/my/courses/{course}`.
- **Rationale**: Allows teachers to discover all courses available in their role's categories (`"Onboarding"`, `"Capacitación"`, etc.), self-enroll in one click without administrative bottleneck or schedules, and immediately take the course.

## Decision 5: On-Demand PDF Certificate in Storage Volume & URL-Only Database Persistence

- **Decision**: Retain `CertificatePdfGenerator` + `resources/views/certificates/template.blade.php` + `IssueCourseCertificate` (`POST /courses/{course}/certificate` and `GET /certificates/{certificate}/download`), storing the `.pdf` in `Storage::disk('local')` under `certificates/{code}.pdf` and persisting **only `file_url`** and metadata in the `certificates` table.
- **Rationale**: Satisfies `FR-010` and `FR-011` with zero binary blobs in the database.
