# Contracts: Reanudación de Diapositiva en la Última Lección Completada

**Feature**: `020-course-resume-completed-lesson`  
**Status**: Completed  
**Spec**: [spec.md](spec.md)

---

## 1. Contrato de Props en `Study/CourseViewer.vue`

El componente recibe del backend (`StudyController@show` y `TeacherClassController@viewer`) el siguiente objeto de datos:

```typescript
interface CourseViewerProps {
    course: {
        id: number;
        name: string;
        description: RichTextDocument | null;
        intro_video_embed_url: string | null;
        units: Array<{
            id: number;
            title: string;
            position: number;
            lessons: Array<{
                id: number;
                title: string;
                position: number;
                content_type: 'text' | 'document' | 'image' | 'link' | 'video';
                content: RichTextDocument | string | null;
                is_completed: boolean;
            }>;
        }>;
        progress: {
            completed_lessons: number;
            total_lessons: number;
            percentage: number;
        };
        assessments: Array<{ id: number; title: string; total_score: number }>;
    };
    isTeacherView?: boolean;
    isReadOnly?: boolean;
    canMarkComplete?: boolean;
    course_offering_id?: number | null;
    backUrl?: string;
}
```

---

## 2. Contrato de la Función de Resolución de Índice (`resolveLastCompletedLessonIndex`)

Función pura encargada de determinar la diapositiva inicial:

```typescript
/**
 * Determina el índice de la diapositiva a proyectar inicialmente.
 * Recorre la lista de lecciones en orden inverso y retorna el índice de la última con is_completed === true.
 * Si ninguna lección está completada, retorna 0 (la primera diapositiva).
 *
 * @param lessons Lista plana de lecciones en orden curricular
 * @returns Índice 0-based de la diapositiva activa
 */
function resolveLastCompletedLessonIndex(lessons: FlattenedLesson[]): number;
```

### Casos de Prueba del Contrato:

1. `[{ is_completed: true }, { is_completed: true }, { is_completed: false }]` → Retorna `1`.
2. `[{ is_completed: true }, { is_completed: false }, { is_completed: true }, { is_completed: false }]` → Retorna `2`.
3. `[{ is_completed: false }, { is_completed: false }]` → Retorna `0`.
4. `[]` → Retorna `0`.

