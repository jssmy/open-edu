---
description: "Task list for Role-Based Course Categories, Teacher Self-Enrollment, and Certificate Issuance"
---

# Tasks: Role-Based Course Categories, Teacher Self-Enrollment, and Certificate Issuance

**Input**: Design documents from `/specs/018-course-categories-certificates-enrollment/`

**Prerequisites**: [plan.md](file:///Users/jssmy/Documents/projects/open-edu/specs/018-course-categories-certificates-enrollment/plan.md), [spec.md](file:///Users/jssmy/Documents/projects/open-edu/specs/018-course-categories-certificates-enrollment/spec.md), [research.md](file:///Users/jssmy/Documents/projects/open-edu/specs/018-course-categories-certificates-enrollment/research.md), [data-model.md](file:///Users/jssmy/Documents/projects/open-edu/specs/018-course-categories-certificates-enrollment/data-model.md), [contracts/](file:///Users/jssmy/Documents/projects/open-edu/specs/018-course-categories-certificates-enrollment/contracts/categories-courses-enrollments-certificates.contract.md)

**Tests**: Comprehensive Pest feature tests are included in Phase 7 per Constitution Principle VII and `AGENTS.md` test enforcement rules.

**Organization**: Tasks are grouped by user story (`[US1]`–`[US4]`) to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (`[US1]`, `[US2]`, `[US3]`, `[US4]`)
- Include exact file paths and verbatim data-model constraints in descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Domain value objects/enums needed across modules

- [X] T001 [P] Create `CourseTargetAudience` enum (`Student = 'student'`, `Teacher = 'teacher'`) with labels in `app/Modules/Courses/Domain/CourseTargetAudience.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core database schema migrations and Eloquent models that MUST be complete before user stories can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 Update migration `database/migrations/2026_11_15_000001_add_categories_audience_certificates_and_teacher_enrollments.php` defining: `course_categories` (`id`, `name varchar(120) NOT NULL UNIQUE`, `slug varchar(140) NOT NULL UNIQUE`, `role_name varchar(64) NOT NULL default 'estudiante'`, `is_active boolean NOT NULL default true`), adding to `courses` (`category_id bigint NULLABLE REFERENCES course_categories(id) ON DELETE RESTRICT`, `target_audience varchar(20) NOT NULL default 'student'`, `issues_certificate boolean NOT NULL default true`), updating `enrollments`, `lesson_progress`, and `attempts` (`student_id NULLABLE`, adding `user_id bigint NULLABLE REFERENCES users(id) ON DELETE CASCADE` with unique constraints `(student_id, course_id)` and `(user_id, course_id)`), and creating `certificates` (`id`, `enrollment_id bigint NOT NULL UNIQUE REFERENCES enrollments(id) ON DELETE CASCADE`, `course_id bigint NOT NULL REFERENCES courses(id) ON DELETE CASCADE`, `user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE`, `student_id bigint NULLABLE REFERENCES students(id) ON DELETE SET NULL`, `code varchar(64) NOT NULL UNIQUE`, `file_url varchar(500) NOT NULL`, `issued_at timestamp NOT NULL`)
- [X] T003 [P] Update `CourseCategory` Eloquent model (`name`, `slug`, `role_name`, `is_active`) and `courses()` relation in `app/Modules/Courses/Infrastructure/Models/CourseCategory.php`
- [X] T004 [P] Create `Certificate` Eloquent model (`enrollment_id`, `course_id`, `user_id`, `student_id`, `code`, `file_url`, `issued_at`) in `app/Modules/Progress/Infrastructure/Models/Certificate.php`
- [X] T005 Update `Course` Eloquent model (`fillable`, `casts`, `category()` / `certificates()` relationships, and `scopeForStudentAudience` / `scopeForRole` query scopes) in `app/Modules/Courses/Infrastructure/Models/Course.php` and `database/factories/Modules/Courses/Infrastructure/Models/CourseFactory.php`
- [X] T006 Update `Enrollment` Eloquent model (`user_id` fillable and `user()` / `certificate()` relations) in `app/Modules/Enrollments/Infrastructure/Models/Enrollment.php`

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - Role-Targeted Course Categories and Default Seeders (Priority: P1) 🎯 MVP

**Goal**: Associate each course category with a target role (`role_name`, defaulting to `'estudiante'`), seed the 8 default categories (`"Onboarding"` and `"Capacitación"` for `docente`, and `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"`, `"Digital"` for `estudiante`), and allow administrators to create new categories with a role selector defaulting to `estudiante`.

**Independent Test**: Run `CourseCategorySeeder`, verify all 8 categories exist with their assigned `role_name`, create a new category via `POST /course-categories` (verifying `role_name` defaults to `'estudiante'` or accepts `'docente'`), and verify it appears in `/courses` and `/courses/create`.

### Implementation for User Story 1

- [X] T007 [P] [US1] Update `CourseCategorySeeder` in `database/seeders/CourseCategorySeeder.php` to seed `"Onboarding"` and `"Capacitación"` with `role_name = 'docente'` and `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"`, and `"Digital"` with `role_name = 'estudiante'`
- [X] T008 [P] [US1] Update `CreateCourseCategory` application use case and repository method in `app/Modules/Courses/Application/CreateCourseCategory.php`, `app/Modules/Courses/Domain/CourseRepository.php`, and `app/Modules/Courses/Infrastructure/EloquentCourseRepository.php` to accept `role_name` (`varchar(64) NOT NULL default 'estudiante'`) alongside `name` (`varchar(120) NOT NULL UNIQUE`)
- [X] T009 [P] [US1] Update `SaveCourseCategoryRequest` in `app/Modules/Courses/Infrastructure/Http/Requests/SaveCourseCategoryRequest.php` to validate `name` (`required|string|max:120`) and `role_name` (`sometimes|required|string|max:64|exists:roles,name`, defaulting to `'estudiante'`)
- [X] T010 [US1] Update `CourseCategoryController@store` in `app/Modules/Courses/Infrastructure/Http/CourseCategoryController.php` to pass `role_name` (`default 'estudiante'`) to `CreateCourseCategory`

**Checkpoint**: User Story 1 is functional and testable independently

---

## Phase 4: User Story 2 - Simplified Course Creation (Category Selection Only + Default Certificate Toggle) (Priority: P1)

**Goal**: Allow administrators creating or editing a course to select **only** the course category (`category_id`, automatically deriving the target role from the category's `role_name`) and have **"Emitir certificado al finalizar"** checked (`true`) by default, while the inline "Nueva categoría" modal defaults its role selector to **Estudiante** (`estudiante`).

**Independent Test**: Open `/courses/create`, verify there is no separate target audience dropdown, verify "Emitir certificado al finalizar" is checked (`true`) by default, select a category (e.g. `"Capacitación"` for `docente` or `"Lógica"` for `estudiante`), save, and verify the course inherits its role from the category.

### Implementation for User Story 2

- [X] T011 [US2] Update `SaveCourseRequest` in `app/Modules/Courses/Infrastructure/Http/Requests/SaveCourseRequest.php` and `EloquentCourseRepository` in `app/Modules/Courses/Infrastructure/EloquentCourseRepository.php` so course creation/editing requires only `category_id` (`nullable|integer|exists:course_categories,id`) and `issues_certificate` (`sometimes|boolean`, default `true`), automatically synchronizing `target_audience` from the selected category's `role_name` (`'student'` when `role_name === 'estudiante'`, `'teacher'` otherwise)
- [X] T012 [US2] Update `CourseController` (`index`, `create`, `edit`, `show`) in `app/Modules/Courses/Infrastructure/Http/CourseController.php` to pass `categories` (including `role_name`) and `roles` (for the category creation modal, defaulting to `estudiante`) to Inertia views
- [X] T013 [P] [US2] Update `resources/js/pages/Courses/Edit.vue` to remove the separate target audience selector (keeping only the Category selector + inline "Nueva categoría" modal with Role selector defaulting to `estudiante` + "Emitir certificado al finalizar" checkbox checked `true` by default)
- [X] T014 [P] [US2] Update `resources/js/components/courses/CourseCard.vue` and `resources/js/pages/Courses/Show.vue` so `CourseCard.vue` displays only the category badge (never displaying the role badge on the card), omits the eye (`view`) icon button from the footer, and links both the cover image preview and the course name to the course detail (`FR-012`)

**Checkpoint**: User Stories 1 and 2 work together seamlessly

---

## Phase 5: User Story 3 - Student-Only Scheduling & Enrollment vs. Teacher Catalog & Self-Enrollment in `/courses` (Priority: P1)

**Goal**: Ensure Academic Scheduling (`/scheduling`) and Enrollments (`/enrollments`) display and accept **only** courses from `estudiante` categories, while non-student roles (such as `docente`) logging into `/courses` see **all published courses** available in their role's categories and can **self-enroll** (`POST /courses/{course}/enroll`) and take those courses immediately in `/my/courses/{course}`.

**Independent Test**: Verify `/scheduling` and `/enrollments` exclude `"Capacitación"` / `"Onboarding"` (`docente` category) courses and reject scheduling/enrolling them. Then log in as `docente`, visit `/courses`, verify all published `"Capacitación"` / `"Onboarding"` courses appear, self-enroll via `POST /courses/{course}/enroll`, and complete lessons/assessments in `/my/courses/{course}`.

### Implementation for User Story 3

- [X] T015 [P] [US3] Update `SchedulingController` (`index`, `pending`) in `app/Modules/Scheduling/Infrastructure/Http/SchedulingController.php` and `SaveScheduleRequest` in `app/Modules/Scheduling/Infrastructure/Http/Requests/SaveScheduleRequest.php` so that Academic Scheduling lists and allows creating `CourseOffering` records **exclusively** for courses belonging to `estudiante` categories (`scopeForStudentAudience`)
- [X] T016 [P] [US3] Update `EnrollmentController` in `app/Modules/Enrollments/Infrastructure/Http/EnrollmentController.php`, `StoreEnrollmentRequest` in `app/Modules/Enrollments/Infrastructure/Http/Requests/StoreEnrollmentRequest.php`, `EloquentEnrollmentRepository` in `app/Modules/Enrollments/Infrastructure/EloquentEnrollmentRepository.php`, and `resources/js/pages/Enrollments/Index.vue` so `/enrollments` is exclusively for student enrollments in `estudiante`-category courses
- [X] T017 [US3] Update `EnrollTeacher` use case in `app/Modules/Enrollments/Application/EnrollTeacher.php` and `EloquentEnrollmentRepository` in `app/Modules/Enrollments/Infrastructure/EloquentEnrollmentRepository.php` to verify that the course is published and its category's `role_name` matches the user's role (e.g. `docente`), creating/reactivating an active `Enrollment` (`user_id`, `course_id`, `course_offering_id = null`)
- [X] T018 [US3] Update `CourseController@index`, add `CourseController@selfEnroll` in `app/Modules/Courses/Infrastructure/Http/CourseController.php`, register `POST /courses/{course}/enroll` in `routes/web.php`, and update `resources/js/pages/Courses/Index.vue` and `resources/js/components/courses/CourseCard.vue` so that for non-student roles (e.g. `docente` without `courses.manage`), `/courses` displays all published courses from categories matching their role (`role_name = 'docente'`) with a self-enrollment button (**"Inscribirme"**) and direct study link (**"Continuar curso"** → `/my/courses/{course}`)
- [X] T019 [US3] Update `CourseAccessPolicy` in `app/Modules/Enrollments/Infrastructure/Policies/CourseAccessPolicy.php`, `EloquentProgressRepository` in `app/Modules/Progress/Infrastructure/EloquentProgressRepository.php`, `StudyController` in `app/Modules/Progress/Infrastructure/Http/StudyController.php`, `AssessmentPolicy` in `app/Modules/Assessments/Infrastructure/Policies/AssessmentPolicy.php`, `EloquentAssessmentRepository` in `app/Modules/Assessments/Infrastructure/EloquentAssessmentRepository.php`, and `AssessmentController` in `app/Modules/Assessments/Infrastructure/Http/AssessmentController.php` so self-enrolled teachers can study lessons and take/submit/review assessments

**Checkpoint**: Student courses are exclusively in `/scheduling` and `/enrollments`, while teachers see all courses of their category in `/courses` and can self-enroll and study them

---

## Phase 6: User Story 4 - Certificate Template, Volume Storage, and URL-Only Database Persistence (Priority: P2)

**Goal**: Generate a landscape `.pdf` completion certificate from an institutional template on demand (`POST /courses/{course}/certificate`) when a learner (student or self-enrolled teacher) completes 100% of lessons (and passes published assessments if any) in a course with `issues_certificate = true`, storing the `.pdf` file in the system storage volume and saving only `file_url` in the `certificates` table.

**Independent Test**: Complete all lessons (and pass any published assessments) in a course with `issues_certificate = true`, send `POST /courses/{course}/certificate`, verify the `.pdf` file exists in the storage volume disk, verify `certificates.file_url` holds the URL in the database (with zero binary columns), and download via `GET /certificates/{certificate}/download`.

### Implementation for User Story 4

- [X] T020 [P] [US4] Create institutional certificate template in `resources/views/certificates/template.blade.php` and landscape PDF 1.4 generator service `CertificatePdfGenerator` in `app/Modules/Progress/Infrastructure/CertificatePdfGenerator.php` that writes the generated `.pdf` file to the storage volume (`certificates/{code}.pdf`)
- [X] T021 [US4] Create `IssueCourseCertificate` use case in `app/Modules/Progress/Application/IssueCourseCertificate.php` verifying active enrollment, `course.issues_certificate === true`, 100% lesson completion, and all published course assessments passed, storing the `.pdf` in the storage volume and saving only `file_url` (`varchar(500) NOT NULL`) and metadata in `certificates`
- [X] T022 [US4] Create `CertificateController` (`issue` and `download` actions) in `app/Modules/Progress/Infrastructure/Http/CertificateController.php` and register `POST /courses/{course}/certificate` and `GET /certificates/{certificate}/download` in `routes/web.php`
- [X] T023 [US4] Update `StudyController@show` in `app/Modules/Progress/Infrastructure/Http/StudyController.php` and `resources/js/pages/Study/CourseViewer.vue` to pass certificate eligibility/state and display the **"Obtener certificado"** / **"Descargar certificado (.pdf)"** action in the course viewer

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: End-to-end automated testing, code formatting, and frontend bundle verification

- [X] T024 Update feature test suite `tests/Feature/Courses/CourseCategoriesCertificatesEnrollmentTest.php` to verify: (1) 8 seeded categories with `role_name` (`docente` for `"Onboarding"` & `"Capacitación"`, `estudiante` for the other 6) and new category creation defaulting `role_name` to `'estudiante'`, (2) course creation selecting only `category_id` and `issues_certificate = true` by default, (3) `/scheduling` and `/enrollments` displaying/accepting only `estudiante`-category courses, (4) `docente` viewing all available `docente`-category courses in `/courses`, self-enrolling via `POST /courses/{course}/enroll`, completing lessons and assessments, and (5) generating/downloading the `.pdf` certificate stored in the volume with only `file_url` in the database
- [X] T025 [P] Update unit test suite `resources/js/__tests__/CourseCard.test.ts` to verify `CourseCard.vue` renders the category badge without role badge, links both the cover preview and title to the course detail, and omits the eye icon button (`FR-012`)
- [X] T026 Run `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`, `npx vitest run resources/js/__tests__/CourseCard.test.ts`, and `npm run build` to verify zero regressions and clean formatting

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Complete (`T001`).
- **Foundational (Phase 2)**: `T002`–`T006` block user stories.
- **User Story 1 (Phase 3)**: Depends on Phase 2 (`T007`–`T010`).
- **User Story 2 (Phase 4)**: Depends on Phase 2 and User Story 1 (`T011`–`T014`).
- **User Story 3 (Phase 5)**: Depends on Phase 2 and User Story 2 (`T015`–`T019`).
- **User Story 4 (Phase 6)**: Complete (`T020`–`T023`).
- **Polish (Phase 7)**: Depends on Phases 3–6 (`T024`–`T025`).

### Parallel Opportunities

- `T007`, `T008`, `T009` in User Story 1 can run in parallel.
- `T013` and `T014` in User Story 2 can run in parallel.
- `T015` (`Scheduling`) and `T016` (`Enrollments`) in User Story 3 can run in parallel.
