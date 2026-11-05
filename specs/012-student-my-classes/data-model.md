# Data Model & State Transitions: Módulo de Mis Clases del Estudiante

## Entidades y Relaciones

```mermaid
erDiagram
    STUDENT ||--o{ ENROLLMENT : "posee"
    COURSE ||--o{ ENROLLMENT : "contiene"
    COURSE_OFFERING ||--o{ ENROLLMENT : "asigna grupo y calendario"
    COURSE ||--o{ UNIT : "organiza"
    UNIT ||--o{ LESSON : "contiene"
    STUDENT ||--o{ LESSON_PROGRESS : "registra avance"
    LESSON ||--o{ LESSON_PROGRESS : "es completada"
    COURSE ||--o{ ASSESSMENT : "evalua"
    ASSESSMENT ||--o{ ASSESSMENT_ATTEMPT : "registra intentos"
    STUDENT ||--o{ ASSESSMENT_ATTEMPT : "rinde"
```

## Estados de la Oferta / Clase para el Estudiante

```mermaid
stateDiagram-v2
    [*] --> Scheduled: starts_on > hoy
    Scheduled --> InProgress: starts_on <= hoy <= ends_on
    InProgress --> Finished: ends_on < hoy
    Scheduled --> Cancelled: Cancelación administrativa

    state Scheduled {
        [*] --> Bloqueado
        Bloqueado: Botón de entrar inhabilitado
        Bloqueado: Badge "Inicia el DD/MM/AAAA"
        Bloqueado: Acceso a visor responde 403
    }

    state InProgress {
        [*] --> Interactivo
        Interactivo: Acceso total al visor
        Interactivo: Marcar progreso permitido
        Interactivo: Rendir evaluaciones permitido
    }

    state Finished {
        [*] --> ModoConsulta
        ModoConsulta: Acceso a visor permitido
        ModoConsulta: Banner superior informativo
        ModoConsulta: Marcar lección bloqueado (403)
        ModoConsulta: Rendir evaluación bloqueado (403)
    }
```

## Propiedades de la Clase para el Estudiante (`StudentClassItem`)

```typescript
export interface StudentClassItem {
    id: number; // enrollment_id o offering_id
    offering_id: number;
    course_id: number;
    course_name: string;
    group_code: string;
    modality: string;
    cover_url: string;
    units_count: number;
    teachers: { id: number; name: string }[];
    starts_on: string | null;
    ends_on: string | null;
    status: 'scheduled' | 'in_progress' | 'finished' | 'cancelled';
    can_access: boolean; // starts_on <= hoy
    is_finished: boolean; // ends_on < hoy
    progress: {
        completed_lessons: number;
        total_lessons: number;
        percentage: number;
    };
}
```

