---
description: "Task list for feature 016-assessment-review-answers-feedback implementation"
---

# Tasks: Student Assessment Review Marked Answers and Feedback

**Input**: Design documents from `specs/016-assessment-review-answers-feedback/` (`spec.md`, `plan.md`, `research.md`, `data-model.md`, `contracts/assessment-review.contract.md`, `quickstart.md`)  
**Prerequisites**: `plan.md` (required), `spec.md` (required for user stories), `research.md`, `data-model.md`, `contracts/`  
**Constitution**: Aligned with Open-Edu Constitution v1.5.0 (Hexagonal modular architecture, Vue 3 + TypeScript, Pest & Vitest testing)  
**Organization**: Tasks are grouped by user story to enable independent implementation and testing.

## Format: `- [ ] [ID] [P?] [Story] Description with file path`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., [US1], [US2])
- Every task includes explicit file paths

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Verify test runners and initial baseline

- [X] T001 Verify backend and frontend test runners for assessments (`php artisan test --filter=AssessmentReviewTest` and `npm test resources/js/__tests__/AssessmentReview.test.ts`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core data and review contracts that MUST be verified before user story enhancements

- [X] T002 Inspect and ensure `App\Modules\Assessments\Infrastructure\Http\AssessmentController@review` and `App\Modules\Assessments\Infrastructure\Policies\AssessmentPolicy` adhere to review contracts in `app/Modules/Assessments/Infrastructure/Http/AssessmentController.php`

---

## Phase 3: User Story 1 - Review Marked Answers and Accuracy Badges in Student Assessment (Priority: P1) 🎯 MVP

**Goal**: Display student-marked choices, correct/incorrect accuracy badges with points, and pedagogical correct answer highlights in read-only mode immediately upon submission.

**Independent Test**: Run `php artisan test --filter=AssessmentReviewTest` and `npm test resources/js/__tests__/AssessmentReview.test.ts`. Visiting `/assessments/{id}/review` renders the student's selected answer (`[Respuesta del alumno]`), accuracy badges (`+X pts (Correcta)` or `0 pts (Incorrecta)`), and the correct choice badge without interactive form controls.

### Tests for User Story 1

- [X] T003 [P] [US1] Add feature tests for completed attempt review asserting marked choice, correct/incorrect status badges, and points in `tests/Feature/Assessments/AssessmentReviewTest.php`
- [X] T004 [P] [US1] Add frontend component tests in `resources/js/__tests__/AssessmentReview.test.ts` verifying rendering of `[Respuesta del alumno]`, `+X pts (Correcta)`, `0 pts (Incorrecta)`, and unanswered questions

### Implementation for User Story 1

- [X] T005 [US1] Update `AssessmentController@review` in `app/Modules/Assessments/Infrastructure/Http/AssessmentController.php` to ensure completed and submitted attempts map student selected choice IDs, choice correctness flags, and `student_answer.is_correct`
- [X] T006 [US1] Update `Review.vue` template and styles in `resources/js/pages/Assessments/Review.vue` to display marked answer badge `[Respuesta del alumno]`, checkmark indicator, question correctness badge (`+X pts (Correcta)` / `0 pts (Incorrecta)`), and correct choice highlight
- [X] T007 [US1] Ensure unanswered questions in `resources/js/pages/Assessments/Review.vue` display neutral `Sin responder` badge and 0 points without marked choice indicators

**Checkpoint**: At this point, User Story 1 is fully functional and testable independently as the core MVP.

---

## Phase 4: User Story 2 - Privacy and Integrity Protection for In-Progress Attempts & Results Integration (Priority: P2)

**Goal**: Block question review when an attempt is actively in progress (`in_progress`), display explanatory warning to resume exam, and provide status awareness in `/results`.

**Independent Test**: Access `/assessments/{id}/review` with an `in_progress` attempt. Verify that question breakdown is blocked with an informative message linking to `/assessments/{id}/take`, and verify that `/assessments/{id}/results` displays banners for active attempts or available retakes.

### Tests for User Story 2

- [X] T008 [P] [US2] Add feature test verifying that in-progress attempts redact `is_correct` and pass `canContinue: true` in `tests/Feature/Assessments/AssessmentReviewTest.php`
- [X] T009 [P] [US2] Add frontend test in `resources/js/__tests__/AssessmentReview.test.ts` verifying that in-progress attempts show resume banner and do not reveal correct answers or question breakdown

### Implementation for User Story 2

- [X] T010 [US2] Update `AssessmentController@results` in `app/Modules/Assessments/Infrastructure/Http/AssessmentController.php` to compute `hasInProgressAttempt` and `canTake` flags for student
- [X] T011 [US2] Update `resources/js/pages/Assessments/Results.vue` to display active attempt and available attempt alerts, plus direct link to `/assessments/{id}/review`
- [X] T012 [US2] Update `resources/js/pages/Assessments/Review.vue` to block question review and show dedicated empty state when `canContinue` is true (Option B clarification)

**Checkpoint**: User Stories 1 and 2 are functional, validated, and secure against exam leaks.

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Format code, execute full regression checks, and validate against quality checklists

- [X] T013 Run Laravel Pint formatter via `vendor/bin/pint --format agent` to maintain code standards
- [X] T014 Execute full suite test verification via `php artisan test --filter=AssessmentReviewTest`, `php artisan test --filter=TakeAssessmentTest`, and `npm test`
- [X] T015 Execute manual verification scenario following `specs/016-assessment-review-answers-feedback/quickstart.md`
- [X] T016 Re-check requirements in `specs/016-assessment-review-answers-feedback/checklists/requirements.md`

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup)**: Initial test verification.
- **Phase 2 (Foundational)**: Controller and policy inspection.
- **Phase 3 (User Story 1 - MVP)**: Implementation of marked answers and correctness badges.
- **Phase 4 (User Story 2)**: Integrity gating, review blocking for active attempts, and `/results` alerts.
- **Phase 5 (Polish)**: Pint formatting and full regression test verification.

### Parallel Opportunities
- T003 and T004 (US1 backend & frontend tests) run in parallel.
- T008 and T009 (US2 backend & frontend tests) run in parallel.
