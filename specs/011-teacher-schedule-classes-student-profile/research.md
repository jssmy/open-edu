# Research: Horario por Curso, Clases Próximas y Ficha Integral de Estudiante

## Decision 1: Asignación Cromática por Curso en Calendario de Horarios

- **Decision**: Cambiar la clave de mapeo de color en `useScheduleCalendar` para indexar y asociar paleta de colores por `courseId` (o `courseName` como fallback) en lugar de hacerlo por `teacherId`. Cada bloque en el calendario (`/my/schedule` y vista administrativa `/scheduling`) tomará su color y contraste según el curso al que pertenece.
- **Rationale**:
  - Cumple directamente con FR-001 y SC-001.
  - Para un docente, ver su propio calendario (`/my/schedule`) con todos los bloques del mismo color (porque él es el único docente) aportaba nula distinción visual entre sus diferentes materias. Al colorear por curso, puede distinguir al instante cuándo dicta Matemáticas, Comunicación o Ciencias.
  - Para el administrador en `/scheduling`, las sesiones de una misma asignatura compartirán tono, permitiendo identificar rápidamente la carga académica de cada materia.
- **Alternatives considered**:
  - *Color configurable en BD por curso*: Aumenta la complejidad del esquema en base de datos innecesariamente (violando KISS/YAGNI). Un mapa determinista con la paleta accesible existente (`COURSE_COLOR_PALETTE`) basado en id o hash/index resuelve la necesidad limpiamente.

---

## Decision 2: Ordenamiento de Clases en `/my/classes` por Proximidad

- **Decision**: En `TeacherClassController::index()`, ordenar la consulta de `CourseOffering` priorizando las clases activas/próximas a empezar mediante orden cronológico por fecha de inicio `starts_on ASC` (colocando ofertas que comienzan hoy o en el futuro inmediato en primer lugar, seguidas por las clases ya iniciadas o históricas, con fallback en `id ASC`).
- **Rationale**:
  - Cumple directamente con FR-003 y SC-002.
  - Permite al docente ver en la parte superior el grupo más relevante para su jornada y preparación de sesión.
- **Alternatives considered**:
  - *Ordenamiento en frontend en Vue*: Menos eficiente y rompe consistencia si en el futuro se pagina. El backend debe entregar la colección ya ordenada según la lógica de dominio.

---

## Decision 3: Ficha Integral de Estudiante (`/students/{studentId}`)

- **Decision**:
  1. En `StudentController::show()`, extender la respuesta de Inertia para que cada elemento de la colección `courses` contenga su progreso (`completed_lessons`, `total_lessons`, `percentage`) obtenido vía `ProgressRepository::coursesForStudent($studentId)` o servicio de progreso, y la lista de evaluaciones asociadas con los intentos rendidos por el estudiante (`attempts` con `score`, `passed`, `status`, `submitted_at`).
  2. Si el usuario autenticado es `docente`, se mantiene el filtro de `coursesFor($studentId, $teacherId)` para que solo vea los cursos donde él enseña. Si es `admin`, ve todos los cursos del estudiante.
  3. En `resources/js/pages/Students/Show.vue`, rediseñar las tarjetas de cursos para presentar:
     - Nombre de curso y grupo.
     - Barra de progreso visual con porcentaje y conteo de lecciones.
     - Bloque integrado de evaluaciones del curso con títulos, puntaje obtenido sobre total, badge de aprobado/no aprobado e indicador de fecha de intento.
- **Rationale**:
  - Respeta la arquitectura hexagonal y modular del proyecto: consulta datos a través de los repositorios y servicios de `Students`, `Progress` y `Assessments`.
  - Cumple la decisión de diseño acordada en `/speckit-clarify` (Opción A: Por curso integrado).
- **Alternatives considered**:
  - *Llamadas AJAX asíncronas separadas para progreso y evaluaciones*: Incrementa peticiones de red e introduce estados de carga fragmentados. Como se trata de la pantalla de ficha de estudiante renderizada por Inertia, inyectar los datos en la respuesta unificada ofrece renderizado instantáneo (<1s) y excelente UX.

