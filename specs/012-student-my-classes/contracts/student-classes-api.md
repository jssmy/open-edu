# Contratos de Rutas y Respuestas: Mis Clases del Estudiante

## 1. Listado de Mis Clases
- **Método**: `GET`
- **Ruta**: `/my/classes`
- **Middleware**: `auth`, `active`, `role:estudiante` (o despachador de rol si comparte ruta)
- **Props Inertia**:
  ```json
  {
    "classes": [
      {
        "id": 1,
        "offering_id": 10,
        "course_id": 5,
        "course_name": "Diseño Web",
        "group_code": "GRP-01",
        "modality": "virtual",
        "cover_url": "/courses/5/cover",
        "units_count": 4,
        "teachers": [{"id": 2, "name": "Prof. Carlos"}],
        "starts_on": "2026-11-01",
        "ends_on": "2026-12-01",
        "status": "in_progress",
        "can_access": true,
        "is_finished": false,
        "progress": {
          "completed_lessons": 2,
          "total_lessons": 4,
          "percentage": 50
        }
      }
    ],
    "started_classes": [...],
    "upcoming_classes": [...],
    "filters": {
      "status": "in_progress"
    }
  }
  ```

## 2. Redirección de Mis Cursos
- **Método**: `GET`
- **Ruta**: `/my/courses`
- **Comportamiento**: Redirección 302 a `/my/classes` para usuarios con rol `estudiante`.

## 3. Visor de Estudio para Curso de la Clase
- **Método**: `GET`
- **Ruta**: `/my/courses/{course}` (o `/my/classes/{offering}/study`)
- **Control de Acceso**:
  - Si `starts_on > hoy`: 403 Forbidden ("La clase programada aún no ha iniciado.").
  - Si `ends_on < hoy`: 200 OK con prop `is_finished: true` y `ends_on: "YYYY-MM-DD"`.

## 4. Marcar Lección Completada
- **Método**: `POST`
- **Ruta**: `/lessons/{lesson}/complete`
- **Comportamiento si la clase ha finalizado**: 403 Forbidden ("No es posible marcar progreso en un curso finalizado.").

## 5. Tomar y Enviar Evaluación
- **Método**: `POST`
- **Rutas**: `/assessments/{assessment}/take` y `/assessments/{assessment}/submit`
- **Comportamiento si la clase ha finalizado**: 403 Forbidden ("Las evaluaciones no están disponibles para cursos finalizados.").

