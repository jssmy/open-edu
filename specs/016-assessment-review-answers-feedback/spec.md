# Feature Specification: Student Assessment Review Marked Answers and Feedback

**Feature Branch**: `016-assessment-review-answers-feedback`

**Created**: 2026-11-05

**Status**: Draft

**Input**: User description: "en la vista no editable del examen del estudiante se debe mostrar las respuestas que el estudiando ha marcado a demas indicar si fue correcto o incorrecto"

## Clarifications

### Session 2026-11-05
- Q: ¿Debe el estudiante ver inmediatamente cuáles respuestas fueron correctas e incorrectas al completar su examen, o depende de la publicación docente / fecha límite? → A: Opción A - Mostrar siempre el detalle completo al estudiante una vez enviado su intento final.
- Q: ¿Cómo debe comportarse la pantalla de revisión (/assessments/{id}/review) cuando un estudiante tiene un intento previo completado pero abrió un nuevo intento que sigue en curso? → A: Opción B - Bloquear la revisión de intentos anteriores mientras exista un intento en curso (mostrando únicamente la opción de continuar el examen).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Review Marked Answers and Accuracy Badges in Student Assessment (Priority: P1)

As a student reviewing my completed assessment (or a teacher inspecting a student's submission),
I want to view the non-editable exam displaying the exact answers selected by the student along with distinct visual badges indicating whether each answer was correct or incorrect immediately upon completing my attempt,
So that both student and educator have complete pedagogical transparency into exam outcomes, mistakes, and successes without waiting for external manual publication.

**Why this priority**: Directly satisfies the user's primary requirement: ensuring that on the non-editable review screen, marked responses are clearly distinguished and the accuracy (correct or incorrect) is prominently indicated for every answered question.

**Independent Test**: Navigate to `/assessments/{id}/review?student_id={studentId}` for a completed assessment attempt. The screen renders each question in read-only mode, highlights the choice selected by the student with a designated marker and badge, and explicitly displays whether that choice was "Correcta" or "Incorrecta" with its earned points.

**Acceptance Scenarios**:

1. **Given** a student has finished and submitted an assessment attempt and does NOT have an active attempt in progress, **When** the student or their teacher visits the assessment review view (`/assessments/{assessmentId}/review`), **Then** every question immediately displays:
   - The question statement and maximum points.
   - All options in disabled/read-only mode.
   - The choice selected by the student clearly flagged (e.g. `[Respuesta del alumno]`).
   - An indicator badge on the question stating "+X pts (Correcta)" if correct, or "0 pts (Incorrecta)" if incorrect.
   - The correct choice identified with an indicator (e.g. badge "Respuesta correcta").
2. **Given** a question that the student left unanswered in the attempt, **When** reviewing the assessment, **Then** the question displays a neutral status badge ("Sin responder") without marking any choice as the student's answer.
3. **Given** the review screen is displayed, **When** an user tries to click, select, or submit any choice, **Then** all inputs remain inert/read-only, preventing form submission or response alterations.

---

### User Story 2 - Privacy and Integrity Protection for In-Progress Attempts (Priority: P2)

As a course instructor or platform administrator,
I want the system to block review of answers and conceal correctness indicators while an assessment attempt is in progress, even if previous attempts were completed,
So that students cannot view answers or use earlier feedback to cheat while actively taking a new attempt.

**Why this priority**: Critical integrity constraint. Showing correctness indicators must be restricted to completed attempts when no attempt is currently in progress.

**Independent Test**: Load `/assessments/{id}/review` when the student's latest attempt status is `in_progress`. The system blocks the review of questions and correctness breakdown, renders an informative banner indicating that the evaluation is currently in progress, and provides a direct call-to-action to resume and submit the exam.

**Acceptance Scenarios**:

1. **Given** an active student attempt with status `in_progress`, **When** the student visits `/assessments/{id}/review` or `/assessments/{id}/results`, **Then** the system displays a prominent banner indicating that the evaluation is in progress with an action to resume (`Continuar evaluación →`), blocking the display of question correctness breakdown.
2. **Given** an attempt with status `completed` (or `submitted`) and NO active attempt in progress, **When** the review page is loaded, **Then** the full breakdown of correct/incorrect status is revealed immediately to both the student and authorized teachers.

---

### Edge Cases

- **Assessment with zero attempts or not started**: What happens when viewing the review screen for a student who never took the test? An empty state message is presented informing that no attempts have been recorded.
- **Unanswered questions in completed attempt**: What happens if an attempt was submitted with unanswered questions? They are scored with 0 points and labeled "Sin responder" without flagging any choice.
- **Student with completed attempt 1 and in-progress attempt 2**: The student cannot review attempt 1 answers while attempt 2 is in progress; the system prompts them to resume and complete attempt 2.
- **Questions without defined choices (open-ended / invalid data)**: If a question has missing choices, the view renders a fallback warning without throwing JavaScript errors.
- **Unauthorized user review attempt**: What happens if an unrelated student tries to review another student's exam? The system denies access with an authorization error (403 Forbidden).

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The non-editable assessment review screen (`/assessments/{id}/review`) MUST display all questions and choices in read-only mode without editable inputs or submit controls.
- **FR-002**: For each question in a completed attempt, the system MUST visibly display the specific choice that the student selected (marked answer).
- **FR-003**: For each answered question in a completed attempt, the system MUST explicitly indicate whether the marked answer was correct or incorrect via dedicated visual indicators/badges (e.g., "Correcta" with earned points, "Incorrecta" with 0 points).
- **FR-004**: For completed attempts, the system MUST clearly highlight the correct answer for pedagogical feedback immediately upon completion without requiring manual teacher grade publishing.
- **FR-005**: For questions left unanswered by the student, the system MUST show an indicator designating the question as unanswered ("Sin responder") with 0 points.
- **FR-006**: When a student has an attempt currently in progress (`in_progress`), the system MUST block the review of question answers and correctness indicators, and MUST display a clear banner/message indicating that the assessment is in progress with an action to resume the exam (`/assessments/{id}/take`).
- **FR-007**: Both `/assessments/{id}/results` and `/assessments/{id}/review` MUST inform students if the assessment is in progress or if attempts remain available to take.
- **FR-008**: The review screen MUST display overall attempt metrics: attempt number, submission status, total score earned, passing score status (Aprobado/Desaprobado), and submission date.

### Key Entities *(include if feature involves data)*

- **Assessment**: The evaluation containing questions, instructions, passing score, and maximum score.
- **Attempt**: A student's exam instance with status (`in_progress`, `completed`, `submitted`), started at, submitted at, score, and passed flag.
- **AttemptAnswer**: The specific choice recorded for each question by the student.
- **Question**: The exam question with text/statement, point value, and associated choices.
- **Choice**: The alternative options with choice text and boolean `is_correct` attribute.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of questions in a completed exam review (with no active attempt) display the student's selected answer and clear correctness feedback ("Correcta" / "Incorrecta").
- **SC-002**: Zero unauthorized response mutations: the review page contains 0 interactive radio controls or submission buttons capable of altering stored answers.
- **SC-003**: 100% of students with an in-progress attempt are directed to resume their exam without having access to answer keys or correctness clues from earlier attempts.
- **SC-004**: Zero security leaks: 0% of `in_progress` attempts reveal correct answer keys before final submission.

---

## Assumptions

- Multiple-choice single-selection questions are the standard question type evaluated in this review interface.
- Teachers assigned to the course and administrators have full permission to review student attempts via the `student_id` query parameter.
- Students can view their review once their attempt is completed and no subsequent attempt is active.
