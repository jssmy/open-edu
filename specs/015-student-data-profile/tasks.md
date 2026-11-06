# Tasks: Student Profile Data and Username Authentication

**Input**: Design documents from `/specs/015-student-data-profile/`  
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/, quickstart.md  
**Obsidian Note**: `[[REQ-026 - Perfil de Estudiante con Username Documento de Identidad y Fecha de Nacimiento]]`

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

---

## Phase 1: Setup (Migrations & Data Model)

**Purpose**: Database schema migrations and model extensions for user username and student birth date

- [x] T001 [P] Create migration `database/migrations/2026_11_05_000001_add_username_to_users_table.php` to add `username` (`VARCHAR(50)`, nullable, unique) and make `email` nullable on `users` table
- [x] T002 [P] Create migration `database/migrations/2026_11_05_000002_add_birth_date_to_students_table.php` to add `birth_date` (`DATE`, nullable) to `students` table
- [x] T003 Update User Eloquent model in `app/Models/User.php` and `database/factories/UserFactory.php` to include `username` in `$fillable` (`['name', 'username', 'email', 'password', 'must_change_password', 'is_active']`) and default factory definition
- [x] T004 Update Student Eloquent model in `app/Modules/Students/Infrastructure/Models/Student.php` and `database/factories/StudentFactory.php` to include `birth_date` in `$fillable` (`['user_id', 'identifier', 'birth_date', 'phone', 'is_active']`) and `$casts` (`'birth_date' => 'date'`)

---

## Phase 2: Foundational (Domain & Infrastructure Adapters)

**Purpose**: Core domain contracts, value objects, and repository signatures needed across user stories

- [x] T005 Update domain entity `Student` in `app/Modules/Students/Domain/Student.php` to accept `public ?string $username`, `public ?string $email`, `public ?string $identifier`, `public ?string $birthDate`, `public bool $isActive`
- [x] T006 Update domain interface `StudentRepository` in `app/Modules/Students/Domain/StudentRepository.php` updating `register` and `update` signatures with `?string $username`, `?string $birthDate` and removing mandatory phone parameters
- [x] T007 Update `EloquentStudentRepository` in `app/Modules/Students/Infrastructure/EloquentStudentRepository.php` to implement the updated `StudentRepository` interface, saving `username` in `User` and `birth_date` in `Student`, without requiring `phone`

**Checkpoint**: Foundation ready - Domain and persistence support `username`, `birth_date`, and optional `email`

---

## Phase 3: User Story 1 - Student Account Registration & Management by Administrator (Priority: P1) 🎯 MVP

**Goal**: Administrators can register and update student profiles with name, unique document identifier, birthdate, unique username, and password, without requiring phone numbers or email addresses.

**Independent Test**: An administrator registers a student via `/students` with name, identifier, birth date, username, and password. The student is created, persisted, listed, and duplicate username/identifier submissions are rejected with validation errors.

### Tests for User Story 1

- [x] T008 [P] [US1] Create/update feature tests in `tests/Feature/Students/StudentRegistrationTest.php` verifying student creation with name, identifier, birth_date, username, password, ensuring phone is omitted and duplicates are rejected

### Implementation for User Story 1

- [x] T009 [US1] Update application use case `RegisterStudent` in `app/Modules/Students/Application/RegisterStudent.php` to accept `string $name`, `string $username`, `?string $email`, `string $temporaryPassword`, `string $identifier`, `string $birthDate`, `?int $actorId = null`
- [x] T010 [US1] Update application use case `UpdateStudent` in `app/Modules/Students/Application/UpdateStudent.php` to accept `int $studentId`, `string $name`, `string $username`, `?string $email`, `string $identifier`, `string $birthDate`, `?int $actorId = null`
- [x] T011 [US1] Update form request `SaveStudentRequest` in `app/Modules/Students/Infrastructure/Http/Requests/SaveStudentRequest.php` enforcing: `name` ('required', 'string', 'max:255'), `username` ('required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($userId)), `identifier` ('required', 'string', 'max:50', Rule::unique('students', 'identifier')->ignore($studentId)), `birth_date` ('required', 'date', 'before:today'), `email` ('nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)), and removing phone validation
- [x] T012 [US1] Update `StudentController` in `app/Modules/Students/Infrastructure/Http/StudentController.php` to pass validated `username` and `birth_date` to `RegisterStudent` and `UpdateStudent`, and include `username` and `birth_date` in `present()` method
- [x] T013 [US1] Update `StudentListQuery` in `app/Modules/Students/Infrastructure/StudentListQuery.php` to search by `identifier`, `user.name`, `user.username`, `user.email`, return `username` and `birth_date`, and remove phone from queries and mapped output
- [x] T014 [US1] Update student list and creation modal in `resources/js/pages/Students/Index.vue` to replace `phone` with columns for `identifier`, `username`, and `birth_date`, and add modal fields for `identifier`, `username`, `birth_date`, removing the phone field

**Checkpoint**: User Story 1 (Registration and management of students without phone/email and with username/identifier/birthdate) is functional and verified.

---

## Phase 4: User Story 2 - Student Authentication via Username (Priority: P2)

**Goal**: Students can log into the platform using their unique username and password, while administrators and teachers can still log in using their email.

**Independent Test**: Navigate to `/login`, enter a registered student's username and password, and verify successful login and redirect to the student dashboard. Also verify email login still works for existing staff accounts.

### Tests for User Story 2

- [x] T015 [P] [US2] Create/update authentication tests in `tests/Feature/Identity/UsernameAuthenticationTest.php` testing login with username, login with email, rate limiting on username attempt, and rejection of invalid credentials

### Implementation for User Story 2

- [x] T016 [US2] Update `AuthController` in `app/Modules/Identity/Infrastructure/Http/AuthController.php` to accept `login` (string) instead of `email`, detect if input is an email via `filter_var(..., FILTER_VALIDATE_EMAIL)` to query `'email'` or `'username'`, and throttle rate limiting using compound key `strtolower($login).'|'.$request->ip()`
- [x] T017 [US2] Update login view in `resources/js/pages/Auth/Login.vue` replacing `email` input with `login` ("Usuario o Correo"), with appropriate autocomplete, placeholder, and error handling

**Checkpoint**: User Story 2 is functional: both username-based student logins and email-based staff logins work seamlessly.

---

## Phase 5: User Story 3 - Student Detail Profile & Verification (Priority: P3)

**Goal**: Viewing a student's comprehensive profile card (`/students/{id}`) clearly displays their document identifier, date of birth, and username, without displaying empty phone placeholders.

**Independent Test**: Visit `/students/{id}` for an active student; verify personal information card renders document identifier, birth date, username, and does not render any phone field or placeholder.

### Implementation for User Story 3

- [x] T018 [P] [US3] Update student detail view and edit modal in `resources/js/pages/Students/Show.vue` to display `identifier`, `birth_date`, `username` in the personal information card and edit form, removing all references to `phone`
- [x] T019 [US3] Update course enrollment student list in `resources/js/pages/Courses/Enrollments.vue` and `EloquentStudentRepository::forCourse` to replace `phone` with `identifier` and `birth_date`

**Checkpoint**: User Story 3 is complete: Student profile and enrollment listings display identity data cleanly without phone references.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Format code, run regression tests, and ensure documentation integrity

- [x] T020 Run database migrations and seeders (`php artisan migrate`)
- [x] T021 Run code formatting with Laravel Pint (`vendor/bin/pint --dirty --format agent`)
- [x] T022 Run full test suite for Students and Identity (`php artisan test tests/Feature/Students tests/Feature/Identity`)
- [x] T023 [P] Execute end-to-end scenarios defined in `specs/015-student-data-profile/quickstart.md`
