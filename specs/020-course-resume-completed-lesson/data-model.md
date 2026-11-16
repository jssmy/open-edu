# Data Model: Reanudación de Diapositiva en la Última Lección Completada

**Feature**: `020-course-resume-completed-lesson`  
**Status**: Completed  
**Spec**: [spec.md](spec.md)

---

## 1. Entidades de Base de Datos (Persistencia)

### Tabla: `lesson_progress`

Almacena el registro de completitud de lecciones para estudiantes individuales, usuarios autoinscritos (docentes en capacitación) u ofertas de cohorte.

| Columna | Tipo | Nulo | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | No | Clave primaria. |
| `course_offering_id` | `BIGINT` | Sí | FK a `course_offerings(id)`. Presente cuando la lección fue dictada en una clase programada. |
| `student_id` | `BIGINT` | Sí | FK a `students(id)`. Presente en avance individual de estudiantes. |
| `user_id` | `BIGINT` | Sí | FK a `users(id)`. Usuario asociado (docente que dicta o usuario autoinscrito). |
| `lesson_id` | `BIGINT` | No | FK a `lessons(id)`. Lección completada. |
| `completed_at` | `TIMESTAMP` | No | Momento en que se registró la completitud. |
| `created_at` | `TIMESTAMP` | No | Timestamp de auditoría. |
| `updated_at` | `TIMESTAMP` | No | Timestamp de auditoría. |

---

## 2. Modelos Frontend (TypeScript)

### `FlattenedLesson`

Representación plana de cada diapositiva en el visor del curso, calculada a partir de las unidades y lecciones ordenadas curricularmente.

```typescript
interface FlattenedLesson {
    id: number;
    title: string;
    position: number;
    content_type: 'text' | 'document' | 'image' | 'link' | 'video';
    content: RichTextDocument | string | null;
    is_completed: boolean;
    unitTitle: string;
    unitId: number;
    indexInCourse: number; // Índice 0-based en el temario secuencial
}
```

### Reglas de Dominio para el Posicionamiento

1. **Secuencialidad Curricular**:
   Las lecciones se aplanan ordenando primero por `unit.position ASC` y luego por `lesson.position ASC`.
2. **Índice de Reanudación**:
   - Si existen lecciones con `is_completed === true`, el índice activo inicial corresponde al mayor `indexInCourse` con `is_completed === true`.
   - Si ninguna lección tiene `is_completed === true`, el índice activo inicial es `0`.

