# Research: Student Assessment Review Marked Answers and Feedback

**Feature**: `016-assessment-review-answers-feedback`  
**Date**: 2026-11-05 (Updated post-clarification)

## Objective
Establish the implementation design for presenting a student's marked answers, whether they are correct or incorrect in the read-only assessment review interface (`/assessments/{id}/review`), and handling in-progress/available attempts in both `/review` and `/results`.

---

## Findings & Decisions

### 1. Presentation of Student Answers and Correctness Badges
- **Context**: On `/assessments/{id}/review`, each question must indicate which option the student selected, whether their answer was correct or incorrect, and clearly highlight the correct answer for completed attempts.
- **Decision**: 
  - In `Review.vue`:
    - The option chosen by the student is visually flagged with `[Respuesta del alumno]` and an active checkmark/radio indicator.
    - Each question shows a status badge in its header:
      - `+X pts (Correcta)` (success variant) when `student_answer.is_correct === true`.
      - `0 pts (Incorrecta)` (danger variant) when the student selected a wrong option (`student_answer.is_correct === false`).
      - `Sin responder` (neutral variant) when the student did not answer.
    - When `isCompleted` is true (status is `completed` or `submitted`), the correct alternative displays a `Respuesta correcta` badge with emerald highlight styling (`ring-1 ring-emerald-500/50 bg-emerald-500/5`).
- **Rationale**: Meets requirement FR-002, FR-003, FR-004 and matches Open-Edu design system conventions (`Badge`, `RichTextContent`, `Card`).

### 2. Immediate Feedback vs In-Progress Integrity & Blocking
- **Context**: Students must immediately receive feedback upon final submission, while ongoing attempts (`in_progress`) must protect test integrity without leaking answers. Per user clarification (Option B), if an attempt is currently in progress, review of questions and correctness is blocked.
- **Decision**: 
  - `AssessmentController::review` checks the attempt status:
    - If `completed` or `submitted`, it passes `is_correct` flags for both choices and student answers to the frontend.
    - If `in_progress`, `is_correct` remains `null`, preventing inspection via network/DOM, and `canContinue` is enabled.
  - In `Review.vue`:
    - If `canContinue` is true, the question review section is blocked and replaced with an informative banner and action to resume taking the exam (`Ir al examen en curso →`).
  - In `Results.vue`:
    - If `hasInProgressAttempt` is true, displays the `Evaluación en curso` alert.
    - If `canTake` is true and an attempt exists, displays `Evaluación aún disponible`.
    - Includes a direct link `Ver respuestas y corrección detallada →` to `/assessments/{id}/review`.
- **Rationale**: Aligns with user clarification session 2026-11-05 and security best practices.

### 3. Backend Data Retrieval & Performance
- **Context**: Loading attempts and questions should avoid N+1 queries.
- **Decision**:
  - Eager load `questions.choices` and attempt answers (`attempts.answers`) in a single query chain.
- **Rationale**: Complies with Constitution Principle I and XV (performance and preventing N+1 queries).
