# Quickstart & Verification Guide: Student Assessment Review Marked Answers and Feedback

**Feature**: `016-assessment-review-answers-feedback`  
**Date**: 2026-11-05  

## Prerequisites
- Application dependencies installed and test database migrated.
- Node.js v20+ environment for Vite/Vitest.

---

## Automated Verification

### 1. Run Feature Tests (Backend & Policy Verification)
Verify that the assessment review endpoint returns marked answers, score breakdown, and correctness status accurately:
```bash
php artisan test --filter=AssessmentReviewTest
```

### 2. Run Vitest Unit/Component Tests (Frontend Rendering)
Verify that the Vue component renders read-only choices, student answers, and correct/incorrect badges:
```bash
npm test resources/js/__tests__/AssessmentReview.test.ts
```

---

## Manual Verification Scenario

1. Log in as an instructor or administrator.
2. Navigate to a student's profile: `/students/{studentId}`.
3. In the student's enrolled courses and assessment section, click on an assessment with a completed attempt.
4. Verify navigation to `/assessments/{assessmentId}/review?student_id={studentId}`:
   - Check that each question displays in read-only mode without radio selection buttons.
   - For every question answered by the student, verify that:
     - The selected option is flagged with `[Respuesta del alumno]`.
     - An indicator badge shows `+X pts (Correcta)` if the answer was correct.
     - An indicator badge shows `0 pts (Incorrecta)` if the answer was wrong.
     - The correct choice displays `Respuesta correcta`.
   - For unanswered questions, verify that the badge displays `Sin responder`.
5. Log in as the student and verify that viewing `/assessments/{assessmentId}/review` shows their own results immediately with identical pedagogical breakdown.

