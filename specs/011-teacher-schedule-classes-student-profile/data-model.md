# Data Model: Horario por Curso, Clases Próximas y Ficha Integral de Estudiante

## Entities and Data Structures

### 1. Calendar Block (Frontend Presentation)

Representa el evento mapeado para `FullCalendar` en `useScheduleCalendar.ts`:

```typescript
interface CalendarScheduleBlock {
    id?: number;
    scheduleId: number;
    sessionId?: number;
    blockIndex: number;
    title?: string;
    groupCode?: string;
    courseId?: number;          // Identificador clave para asignación cromática
    courseName: string;
    teacherId?: number;
    teacherName: string;
    modality: 'in_person' | 'virtual' | 'hybrid';
    classroomName: string | null;
    meetingUrl?: string | null;
    sessionDate?: string;        // YYYY-MM-DD
    dayOfWeek: number;          // 1..7
    startsAt: string;           // HH:MM
    endsAt: string;             // HH:MM
    status?: string;
    readOnly?: boolean;
}
```

### 2. Teacher Class Offering Item (Backend & Frontend)

Representa cada tarjeta de clase para `/my/classes`:

```typescript
interface TeacherClassItem {
    id: number;
    group_code: string;
    modality: 'in_person' | 'virtual' | 'hybrid';
    starts_on: string | null;   // YYYY-MM-DD
    ends_on: string | null;     // YYYY-MM-DD
    course: {
        id: number;
        title: string;
        name: string;
        excerpt: string;
        cover_image_url: string | null;
        units_count: number;
        lessons_count: number;
        status: string;
        teachers: Array<{ id: number; name: string }>;
    };
}
```

*Regla de ordenamiento:* `starts_on` ascendente (priorizando fechas >= hoy, luego pasadas o nulas).

---

### 3. Student Profile Course with Integrated Progress & Assessments

Estructura enriquecida enviada a `resources/js/pages/Students/Show.vue`:

```typescript
interface StudentCourseAssessmentAttempt {
    id: number;
    attempt_number: number;
    score: number;
    passed: boolean;
    status: string;             // 'submitted' | 'graded' | 'in_progress'
    submitted_at: string | null;
}

interface StudentCourseAssessment {
    id: number;
    title: string;
    total_score: number;
    passing_score: number;
    latest_attempt: StudentCourseAssessmentAttempt | null;
}

interface StudentCourseItem {
    id: number;                 // course_id
    enrollment_id?: number;
    name: string;
    status: string;             // active, completed, etc.
    group_code?: string | null;
    enrolled_at: string;
    progress: {
        completed_lessons: number;
        total_lessons: number;
        percentage: number;
    };
    assessments: StudentCourseAssessment[];
}
```

## State Transitions & Validation Rules

- **Calendario**: La paleta cromática se aplica cíclicamente (`courseId % PALETTE_LENGTH`), garantizando consistencia idéntica en renderizados sucesivos.
- **Autorización de Estudiante**: Los docentes sólo reciben y visualizan cursos, progreso y evaluaciones de estudiantes que pertenecen a cursos donde dicho docente está asignado (`canTeacherView`). Los administradores acceden a la totalidad de asignaturas del estudiante.

