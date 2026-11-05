# Contract: Assessment Review Endpoint & UI

## Endpoint: `GET /assessments/{assessment}/review`

### Query Parameters

| Parámetro | Tipo | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `student_id` | `number` | Condicional | ID del estudiante a consultar. Requerido para docentes/administradores cuando supervisan la ficha de un estudiante específico. Si no se envía y el usuario es estudiante, se toma el estudiante autenticado. |

### Inertia Props (`Assessments/Review`)

```typescript
interface ReviewProps {
    assessment: {
        id: number;
        title: string;
        instructions: RichTextDocument | null;
        total_score: number;
        passing_score: number;
    };
    student: {
        id: number;
        name: string;
        email: string;
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
    questions: {
        id: number;
        statement: RichTextDocument;
        score: number;
        choices: {
            id: number;
            text: string;
            is_correct?: boolean;
        }[];
        student_answer: {
            choice_id: number | null;
            is_correct?: boolean;
        } | null;
    }[];
    isStudent: boolean;
    canContinue: boolean; // true si isStudent && attempt?.status === 'in_progress'
}
```

