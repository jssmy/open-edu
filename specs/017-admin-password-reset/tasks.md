# Tasks: Admin Password Reset and Public Recovery Removal

**Input**: Design documents from `/specs/017-admin-password-reset/`  
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/, quickstart.md  
**Obsidian Note**: `[[REQ-028 - Restablecimiento de Contraseña Exclusivo por Administrador y Eliminación de Recuperacion en Login]]`

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

---

## Phase 1: Setup & Foundational Tasks

**Purpose**: Core application contracts and dependencies for password hashing and reset capabilities

- [x] T001 Update `UserRepository` interface in `app/Modules/Identity/Domain/UserRepository.php` to accept optional `?string $passwordHash = null` and `bool $mustChangePassword = false` in `update()`
- [x] T002 Update `EloquentUserRepository` in `app/Modules/Identity/Infrastructure/EloquentUserRepository.php` to handle optional password hashing, `must_change_password`, and `remember_token` clearing
- [x] T003 Update `StudentRepository` interface in `app/Modules/Students/Domain/StudentRepository.php` to accept optional `?string $passwordHash = null` in `update()`
- [x] T004 Update `EloquentStudentRepository` in `app/Modules/Students/Infrastructure/EloquentStudentRepository.php` to update user password, set `must_change_password = true`, and clear `remember_token` when `$passwordHash` is provided

---

## Phase 2: User Story 1 - Removal of Public Password Recovery Option in Login (Priority: P1) 🎯 MVP

**Goal**: Remove "¿Olvidaste tu contraseña?" link from login view and disable public self-service password recovery routes.

**Independent Test**: Requesting `/login` renders without password recovery link, and requests to `/forgot-password` or `/reset-password` return 404 or redirect.

### Tests for User Story 1

- [x] T005 [P] [US1] Create test `tests/Feature/Identity/PublicPasswordRecoveryDisabledTest.php` asserting that `/login` has no recovery link and `/forgot-password` and `/reset-password` endpoints return 404

### Implementation for User Story 1

- [x] T006 [US1] Remove recovery link `<Link href="/forgot-password">` from `resources/js/pages/Auth/Login.vue`
- [x] T007 [US1] Remove or disable public `/forgot-password` and `/reset-password` route definitions from `routes/web.php` (keeping authenticated `/password/change`)

**Checkpoint**: User Story 1 complete and independently testable.

---

## Phase 3: User Story 2 - Administrator Password Reset for Students (Priority: P1)

**Goal**: Administrators can set or reset a student's password from their profile or edit form, forcing a password change on next login.

**Independent Test**: Admin updates student with a new password in `/students/{id}`, student logs in with the new password, and is redirected to `/password/change`.

### Tests for User Story 2

- [x] T008 [P] [US2] Add feature tests in `tests/Feature/Students/StudentPasswordResetTest.php` asserting that admin can update student password, non-admins are forbidden, and the student must change password upon next login

### Implementation for User Story 2

- [x] T009 [US2] Update `SaveStudentRequest` in `app/Modules/Students/Infrastructure/Http/Requests/SaveStudentRequest.php` allowing optional `temporary_password` on PUT requests with `min:8` validation
- [x] T010 [US2] Update `UpdateStudent` use case in `app/Modules/Students/Application/UpdateStudent.php` to accept `?string $temporaryPassword = null`, hash it with `PasswordHasher`, and pass to repository
- [x] T011 [US2] Update `StudentController::update` in `app/Modules/Students/Infrastructure/Http/StudentController.php` to pass `temporary_password` to `UpdateStudent`
- [x] T012 [US2] Update `resources/js/pages/Students/Show.vue` to include a "Nueva contraseña (opcional)" input field in the student edit form

**Checkpoint**: User Story 2 complete: Admin can reset student passwords with forced change on login.

---

## Phase 4: User Story 3 - Administrator Password Reset for Teachers and Staff (Priority: P2)

**Goal**: Administrators can reset teacher and staff passwords from the user management screen, forcing a password change on next login.

**Independent Test**: Admin edits teacher at `/users/{id}`, enters a new password; teacher logs in and is redirected to `/password/change`.

### Tests for User Story 3

- [x] T013 [P] [US3] Add feature test in `tests/Feature/Identity/UserPasswordResetByAdminTest.php` asserting admin can reset teacher password, sets `must_change_password`, and audits the action

### Implementation for User Story 3

- [x] T014 [US3] Update `UpdateUserRequest` in `app/Modules/Identity/Infrastructure/Http/Requests/UpdateUserRequest.php` to allow optional `password` (`['nullable', 'string', Password::min(8)]`)
- [x] T015 [US3] Update `UpdateUser` use case in `app/Modules/Identity/Application/UpdateUser.php` to accept `?string $password = null`, hash it, set `must_change_password = true`, and audit the action
- [x] T016 [US3] Update `UserController::update` in `app/Modules/Identity/Infrastructure/Http/UserController.php` to pass password to `UpdateUser`
- [x] T017 [US3] Update `resources/js/pages/Users/Edit.vue` to include a "Nueva contraseña (opcional)" input field in the user edit form

**Checkpoint**: User Story 3 complete: Admin can reset teacher/staff passwords with forced change on login.

---

## Phase 5: Polish & Validation

**Purpose**: Format code, run regression tests, and execute quickstart scenarios

- [x] T018 Run code formatting with Laravel Pint (`vendor/bin/pint --dirty --format agent`)
- [x] T019 Run full test suite for Identity and Students (`php artisan test tests/Feature/Identity tests/Feature/Students`)
- [x] T020 Run TypeScript validation (`npx vue-tsc --noEmit`)
- [x] T021 Execute validation scenarios from `specs/017-admin-password-reset/quickstart.md`
