# Data Model: Student Assessment Review Marked Answers and Feedback

**Feature**: `016-assessment-review-answers-feedback`  
**Date**: 2026-11-05  

## Existing Domain Entities & Relationships

### Assessment
- `id` (int, primary key)
- `course_id` (foreign key -> courses.id)
- `title` (string)
- `instructions` (RichTextDocument | null)
- `total_score` (int/decimal)
- `passing_score` (int/decimal)
- `max_attempts` (int)
- `is_published` (boolean)

### Question
- `id` (int, primary key)
- `assessment_id` (foreign key -> assessments.id)
- `statement` (RichTextDocument json)
- `points` (int)
- `position` (int)

### Choice
- `id` (int, primary key)
- `question_id` (foreign key -> questions.id)
- `text` (string)
- `is_correct` (boolean)

### Attempt
- `id` (int, primary key)
- `assessment_id` (foreign key -> assessments.id)
- `student_id` (foreign key -> students.id)
- `attempt_number` (int)
- `status` (enum: `in_progress`, `completed`, `abandoned`)
- `score` (int/decimal)
- `passed` (boolean)
- `started_at` (timestamp)
- `submitted_at` (timestamp, nullable)

### AttemptAnswer
- `id` (int, primary key)
- `attempt_id` (foreign key -> attempts.id)
- `question_id` (foreign key -> questions.id)
- `choice_id` (foreign key -> choices.id, nullable)

---

## Review ViewModel Structure (DTO for Inertia)

```typescript
interface ReviewPageProps {
    assessment: {
        id: number;
        title: string;
        instructions: RichTextDocument | null;
        total_score: number;
        passing_score: number;
        course_name: string | null;
    };
    student: {
        id: number;
        name: string;
        email?: string | null;
    } | null;
    attempt: {
        id: number;
        attempt_number: number;
        status: 'in_progress' | 'completed' | 'abandoned';
        score: number;
        passed: boolean;
        started_at: string;
        submitted_at: string | null;
    } | null;
    questions: Array<{
        id: number;
        statement: RichTextDocument;
        points: number;
        choices: Array<{
            id: number;
            text: string;
            is_correct: boolean | null; // null if attempt is not completed
        }>;
        student_answer: {
            choice_id: number | null;
            is_correct: boolean | null; // true | false | null
        } | null;
    }>;
    isStudent: boolean;
    canContinue: boolean;
}
```

---

## State Transition Rules
- **Attempt creation**: Starts as `in_progress`.
- **Attempt submission**: Transitions to `completed`, computes `score` and `passed`.
- **Review view access**:
  - If `attempt.status === 'completed'`: Returns full answers, marked selections, and accuracy indicators (`is_correct: true/false`).
  - If `attempt.status === 'in_progress'`: Redacts `is_correct` and displays resume banner (`canContinue = true`).

