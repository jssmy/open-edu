# Contract: Assessment Review Page & Results Page Interface

**Feature**: `016-assessment-review-answers-feedback`  
**Routes**:
- `GET /assessments/{assessment}/review` (`Assessments/Review`)
- `GET /assessments/{assessment}/results` (`Assessments/Results`)

---

## 1. Review Page Contract (`GET /assessments/{assessment}/review`)

### Controller Action: `App\Modules\Assessments\Infrastructure\Http\AssessmentController@review`

### Response Props
```json
{
  "component": "Assessments/Review",
  "props": {
    "assessment": {
      "id": 6,
      "title": "Evaluación Diagnóstica",
      "instructions": null,
      "total_score": 20,
      "passing_score": 12,
      "course_name": "Diseño Web con HTML y CSS"
    },
    "student": {
      "id": 6,
      "name": "Valentina Mendoza Aguilar",
      "email": "estudiante03@openedu.test"
    },
    "attempt": {
      "id": 30,
      "attempt_number": 1,
      "status": "submitted",
      "score": 10,
      "passed": false,
      "started_at": "2026-11-03 03:45:18",
      "submitted_at": "2026-11-03 04:20:18"
    },
    "questions": [
      {
        "id": 21,
        "statement": { "type": "doc", "content": [] },
        "points": 5,
        "choices": [
          { "id": 73, "text": "Opción 1", "is_correct": true },
          { "id": 74, "text": "Opción 2", "is_correct": false }
        ],
        "student_answer": {
          "choice_id": 73,
          "is_correct": true
        }
      }
    ],
    "isStudent": true,
    "canContinue": false
  }
}
```

---

## 2. Results Page Contract (`GET /assessments/{assessment}/results`)

### Controller Action: `App\Modules\Assessments\Infrastructure\Http\AssessmentController@results`

### Response Props
```json
{
  "component": "Assessments/Results",
  "props": {
    "assessment": {
      "id": 6,
      "title": "Evaluación Diagnóstica",
      "instructions": null,
      "total_score": 20,
      "questions": []
    },
    "result": {
      "assessment_id": 6,
      "assessment_title": "Evaluación Diagnóstica",
      "attempt_number": 1,
      "score": 10,
      "total_score": 20,
      "passed": false,
      "submitted_at": "2026-11-03T04:20:18+00:00"
    },
    "results": [],
    "hasInProgressAttempt": true,
    "canTake": true
  }
}
```
