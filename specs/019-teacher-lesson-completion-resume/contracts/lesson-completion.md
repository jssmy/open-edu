# API Contract: Completitud de Lecciones en Modo Docente y Estudiante

**Feature**: `019-teacher-lesson-completion-resume`
**Date**: 2026-11-16

## Endpoint: `POST /lessons/{lesson}/complete`

Registra o alterna el estado de completitud de una lección para un estudiante o para una clase dictada por un docente.

### Autenticación
Requerida sesión autenticada (`auth` middleware).

### Request Payload
```json
{
  "course_offering_id": 14,
  "action": "toggle"
}
```

| Campo | Tipo | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `course_offering_id` | `integer` | No | ID de la oferta académica. Requerido cuando el docente marca la lección desde `/my/classes/{offering}/viewer`. En cursos con programación académica, solo docentes asignados pueden invocar este endpoint. |
| `action` | `string` | No | `"complete"`, `"uncomplete"` o `"toggle"`. Por defecto `"complete"`. Para docentes en clase permite revertir lección si se envía `"toggle"` o `"uncomplete"`. |

### Regla de Control en Cliente (Vue):
- En **`/my/classes/{offering}/viewer`** (Docente en clase programada): Se muestra el botón interactivo para marcar o desmarcar lecciones dictadas.
- En **`/my/courses/{course}` con `course_offering_id`** (Estudiante en clase programada): El visor muestra las lecciones dictadas por el profesor como completadas y oculta el botón de marcado manual (`can_mark_complete: false`), asegurando sincronía con el dictado.
- En **`/my/courses/{course}` sin `course_offering_id`** (Cursos asíncronos / capacitaciones): Se muestra el botón `"✓ Marcar completada"` individual para el participante.

### Respuestas

#### Éxito: 200 OK
```json
{
  "success": true,
  "is_completed": true,
  "progress": {
    "completed_lessons": 4,
    "total_lessons": 10,
    "percentage": 40
  }
}
```

#### No Autorizado / No Asignado: 403 Forbidden
```json
{
  "message": "No estás autorizado para modificar el avance de esta clase."
}
```

#### Curso / Oferta No Encontrada: 404 Not Found
```json
{
  "message": "La lección o clase especificada no existe."
}
```

---

## Endpoint: `GET /my/classes/{offering}/viewer`

Retorna la vista Inertia `Study/CourseViewer` con los datos del curso, lecciones, progreso del grupo y bandera de modo docente.

### Props Inertia entregadas a `Study/CourseViewer`:
```json
{
  "course": {
    "id": 5,
    "name": "Introducción a la Programación",
    "description": "...",
    "units": [
      {
        "id": 1,
        "title": "Unidad 1",
        "position": 1,
        "lessons": [
          {
            "id": 10,
            "title": "Bienvenida y Entorno",
            "position": 1,
            "content_type": "text",
            "content": "...",
            "is_completed": true
          },
          {
            "id": 11,
            "title": "Variables y Tipos",
            "position": 2,
            "content_type": "text",
            "content": "...",
            "is_completed": true
          },
          {
            "id": 12,
            "title": "Estructuras de Control",
            "position": 3,
            "content_type": "text",
            "content": "...",
            "is_completed": false
          }
        ]
      }
    ],
    "progress": {
      "completed_lessons": 2,
      "total_lessons": 3,
      "percentage": 67
    },
    "course_offering_id": 14
  },
  "isTeacherView": true,
  "isReadOnly": false,
  "backUrl": "/my/classes"
}
```
*El cliente localiza que la última completada es la lección ID 11 (índice 1) y posiciona el visor automáticamente en ella.*

