# Phase 1: Data Model & Contracts

## Data Model Changes
No se requieren cambios estructurales en base de datos ni migraciones. Las entidades `Course`, `User`, `Role` y `Permission` permanecen inalteradas.

## Contract / Response Shape

### Route: `GET /courses/{course}` (Courses/Show)

```typescript
interface CourseShowPageProps {
  course: {
    id: number;
    name: string;
    description: RichTextDocument | null;
    objectives: RichTextDocument | null;
    intro_video_url: string | null;
    intro_video_embed_url: string | null;
    status: 'draft' | 'published' | 'deactivated';
    lock_version: number;
    teachers: { id: number; name: string }[];
    units: UnitData[];
    assessments: AssessmentData[];
  };
  // Modificado: Ahora sólo es true si el usuario autenticado tiene el permiso 'courses.manage'
  canManageCourses: boolean;
  canManageContent: boolean;
}
```

### Authorization Matrix

| Acción / Elemento | Usuario con `courses.manage` | Docente asignado sin `courses.manage` |
|---|---|---|
| Ver detalle (`GET /courses/{id}`) | ✅ Permitido | ✅ Permitido (si tiene `courses.view` y está asignado) |
| Panel "Publicación" | ✅ Visible y editable | ❌ Oculto (no puede cambiar estado) |
| Panel "Zona de peligro" | ✅ Visible y ejecutable | ❌ Oculto (no puede eliminar curso) |
| `PATCH /courses/{id}/status` | ✅ 200 / 302 | ❌ 403 Forbidden |
| `DELETE /courses/{id}` | ✅ 200 / 302 | ❌ 403 Forbidden |

