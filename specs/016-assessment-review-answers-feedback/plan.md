# Implementation Plan: Student Assessment Review Marked Answers and Feedback

**Branch**: `016-assessment-review-answers-feedback` | **Date**: 2026-11-05 (Updated post-clarification) | **Spec**: [specs/016-assessment-review-answers-feedback/spec.md](file:///Users/jssmy/Documents/projects/open-edu/specs/016-assessment-review-answers-feedback/spec.md)

**Input**: Feature specification from `/specs/016-assessment-review-answers-feedback/spec.md`

## Summary

Provide an immediate, read-only pedagogical review of completed student evaluations at `/assessments/{id}/review`, displaying the exact choices selected by the student, visual badges indicating whether each answer was correct or incorrect, and the highlighted correct answers without permitting response modifications. Additionally, handle in-progress/available assessment states across `/assessments/{id}/results` and `/assessments/{id}/review`, blocking the answer key while an attempt is actively in progress.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 12 / Laravel Boost guidelines), TypeScript / Vue 3  
**Primary Dependencies**: Inertia.js v3, Tailwind CSS v4, Vue Test Utils, Pest PHP  
**Storage**: PostgreSQL (tables: `assessments`, `questions`, `choices`, `attempts`, `attempt_answers`, `students`)  
**Testing**: Pest PHP feature tests (`tests/Feature/Assessments/AssessmentReviewTest.php`), Vitest component tests (`resources/js/__tests__/AssessmentReview.test.ts`)  
**Target Platform**: Web application (Responsive desktop & mobile)  
**Project Type**: Modular Monolith (Hexagonal Architecture)  
**Performance Goals**: P95 < 200ms page render with eager-loaded attempts and questions avoiding N+1 queries  
**Constraints**: Read-only interface (zero editable inputs or submit buttons), strictly non-leaking in-progress attempts, blocking review while an attempt is actively in progress  
**Scale/Scope**: Course assessments across all active student enrollments  

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principle I (Monolito Modular con Arquitectura Hexagonal)**: PASS. Uses `Assessments` module domain models and HTTP controllers with thin presentation layer.
- **Principle III (DRY, KISS, YAGNI)**: PASS. Reuses existing `Review.vue`, `Results.vue`, and controller actions.
- **Principle V (Frontend con Vue y Componentes Reutilizables)**: PASS. Vue 3 Composition API with `<script setup lang="ts">`, typed props, and design system components (`Badge`, `Card`, `RichTextContent`, `Alert`, `EmptyState`).
- **Principle VI (Sistema de Diseño)**: PASS. Uses standard tokens and variants (`variant="success"`, `variant="danger"`, `variant="warning"`, `variant="neutral"`, `variant="info"`).
- **Principle VII (Calidad y Pruebas)**: PASS. Pest backend tests + Vitest frontend component tests covering read-only states, correct/incorrect markers, and privacy constraints.
- **Principle VIII (Seguridad)**: PASS. Authorization controlled via `AssessmentPolicy::viewResults`, in-progress responses protected from leaking correct keys and review blocked during active tests.
- **Principle XI (Interfaz y UX)**: PASS. Dedicated full-page review (no modal editors), accessible labels and clear visual indicators.

## Project Structure

### Documentation (this feature)

```text
specs/016-assessment-review-answers-feedback/
├── spec.md              # Feature specification
├── plan.md              # This file
├── research.md          # Phase 0 decisions & findings
├── data-model.md        # Phase 1 DTO & entity definitions
├── quickstart.md        # Phase 1 verification instructions
├── contracts/           # Phase 1 interface contracts
│   └── assessment-review.contract.md
└── checklists/          # Validation checklists
    └── requirements.md
```

### Source Code (repository root)

```text
app/Modules/Assessments/
├── Infrastructure/
│   ├── Http/
│   │   └── AssessmentController.php      # review() and results() actions with in-progress awareness
│   ├── Models/
│   │   ├── Assessment.php
│   │   ├── Attempt.php
│   │   ├── AttemptAnswer.php
│   │   ├── Choice.php
│   │   └── Question.php
│   └── Policies/
│       └── AssessmentPolicy.php          # viewResults authorization policy

resources/js/
├── pages/
│   └── Assessments/
│       ├── Review.vue                    # Read-only review component with answer correctness badges & in-progress blocking
│       └── Results.vue                   # Results summary page with in-progress / available alerts & review link
└── __tests__/
    └── AssessmentReview.test.ts          # Vitest component test suite

tests/Feature/Assessments/
└── AssessmentReviewTest.php              # Pest feature test for review endpoint
```

## Phase 0: Outline & Research
- Completed in `research.md`.

## Phase 1: Design & Contracts
- Completed in `data-model.md`, `contracts/assessment-review.contract.md`, and `quickstart.md`.
