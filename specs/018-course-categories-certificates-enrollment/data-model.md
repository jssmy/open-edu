# Data Model: Role-Based Course Categories, Teacher Self-Enrollment, and Certificate Issuance

**Feature**: `018-course-categories-certificates-enrollment`
**Date**: 2026-11-15

## 1. `course_categories` Table

Stores course categories and the role (`role_name`) for which each category is available.

| Column | Type | Nullable | Default | Constraints / Notes |
|--------|------|----------|---------|---------------------|
| `id` | `bigserial` | No | auto | Primary Key |
| `name` | `varchar(120)` | No | — | Unique (`course_categories_name_unique`) |
| `slug` | `varchar(140)` | No | — | Unique (`course_categories_slug_unique`) |
| `role_name` | `varchar(64)` | No | `'estudiante'` | Role name (e.g. `'estudiante'`, `'docente'`), indexed |
| `is_active` | `boolean` | No | `true` | Active status |
| `created_at` | `timestamp` | Yes | — | Laravel timestamps |
| `updated_at` | `timestamp` | Yes | — | Laravel timestamps |

**Seeded Records (`CourseCategorySeeder`)**:
- `"Onboarding"` (`slug: 'onboarding'`, `role_name: 'docente'`)
- `"Capacitación"` (`slug: 'capacitacion'`, `role_name: 'docente'`)
- `"Disciplina"` (`slug: 'disciplina'`, `role_name: 'estudiante'`)
- `"Expresión"` (`slug: 'expresion'`, `role_name: 'estudiante'`)
- `"Creatividad"` (`slug: 'creatividad'`, `role_name: 'estudiante'`)
- `"Lógica"` (`slug: 'logica'`, `role_name: 'estudiante'`)
- `"Idiomas"` (`slug: 'idiomas'`, `role_name: 'estudiante'`)
- `"Digital"` (`slug: 'digital'`, `role_name: 'estudiante'`)

---

## 2. `courses` Table (Additions)

| Column | Type | Nullable | Default | Constraints / Notes |
|--------|------|----------|---------|---------------------|
| `category_id` | `bigint` | Yes | `null` | `FOREIGN KEY REFERENCES course_categories(id) ON DELETE RESTRICT` |
| `target_audience` | `varchar(20)` | No | `'student'` | Derived automatically from category's `role_name` (`'student'` when `role_name = 'estudiante'`, `'teacher'` otherwise) |
| `issues_certificate` | `boolean` | No | `true` | Checked (`true`) by default on course creation |

**Validation & Business Rules**:
- On course creation/update (`SaveCourseRequest`), the administrator selects `category_id` and `issues_certificate` (no separate `target_audience` input required; `target_audience` is synchronized from the selected category's `role_name`).
- In **Programación Académica** (`/scheduling`) and **Matrícula** (`/enrollments`), only courses whose category has `role_name = 'estudiante'` (or `category_id IS NULL` for legacy records) are visible and eligible.

---

## 3. `enrollments`, `lesson_progress`, and `attempts` Tables

| Table | Column | Type | Nullable | Constraints / Notes |
|-------|--------|------|----------|---------------------|
| `enrollments` | `student_id` | `bigint` | **Yes** | Populated for student enrollments in `/enrollments` |
| `enrollments` | `user_id` | `bigint` | **Yes** | Populated for self-enrolled non-student users (e.g. `docente` in `/courses`) and student users |
| `enrollments` | `course_offering_id` | `bigint` | **Yes** | Populated for student enrollments; `null` for self-enrolled non-student users |
| `lesson_progress` | `student_id` | `bigint` | **Yes** | Nullable for non-student learners |
| `lesson_progress` | `user_id` | `bigint` | **Yes** | Populated for non-student learners |
| `attempts` | `student_id` | `bigint` | **Yes** | Nullable for non-student learners |
| `attempts` | `user_id` | `bigint` | **Yes** | Populated for non-student learners |

---

## 4. `certificates` Table

Stores only the URL/path (`file_url`) to the generated `.pdf` file in the storage volume plus issuance metadata.

| Column | Type | Nullable | Default | Constraints / Notes |
|--------|------|----------|---------|---------------------|
| `id` | `bigserial` | No | auto | Primary Key |
| `enrollment_id` | `bigint` | No | — | `UNIQUE FOREIGN KEY REFERENCES enrollments(id) ON DELETE CASCADE` |
| `course_id` | `bigint` | No | — | `FOREIGN KEY REFERENCES courses(id) ON DELETE CASCADE` |
| `user_id` | `bigint` | No | — | `FOREIGN KEY REFERENCES users(id) ON DELETE CASCADE` |
| `student_id` | `bigint` | Yes | `null` | `FOREIGN KEY REFERENCES students(id) ON DELETE SET NULL` |
| `code` | `varchar(64)` | No | — | `UNIQUE` verification code (`CERT-YYYYMMDD-{courseId}-{enrollmentId}`) |
| `file_url` | `varchar(500)` | No | — | URL/path reference to `.pdf` file in storage volume (NO binary column in DB) |
| `issued_at` | `timestamp` | No | — | Issuance timestamp |
| `created_at` | `timestamp` | Yes | — | Laravel timestamps |
| `updated_at` | `timestamp` | Yes | — | Laravel timestamps |
