# Contrato HTTP/UI: Programación académica (`CourseOffering`, `Sessions`) y Matrícula de estudiantes (Inertia)

Todas las rutas están bajo middleware `auth` y delegan autorización al backend (Spatie permissions / Gates).

## 1. Módulo de Programación Académica (`/scheduling`)

| Método | Ruta | Permiso | Descripción |
|---|---|---|---|
| `GET` | `/scheduling` | `scheduling.manage` | Calendario de sesiones y listado de programaciones (`CourseOffering`) con sus sesiones, docentes, modalidad, estado y vacantes (`enrolled_count / max_students`). Filtros `?course=&teacher=&classroom=&status=`. |
| `GET` | `/scheduling/pending` | `scheduling.manage` | Asignaciones previas migradas pendientes de configurar como `CourseOffering`. |
| `POST` | `/scheduling/offerings` | `scheduling.manage` | Crea una programación académica (`CourseOffering`) con su único docente asignado y sesiones iniciales: `{ course_id, group_code?, starts_on, ends_on, max_students, modality, status, teacher_id, sessions[] }` (`group_code` se autogenera secuencialmente como `GRP-01`, `GRP-02`, ... por `course_id` si se omite o envía vacío; solo se admite 1 docente por grupo). Alias compatible en `POST /scheduling/schedules`. |
| `PUT` | `/scheduling/offerings/{offering}` | `scheduling.manage` | Actualiza datos del `CourseOffering`, su docente asignado y/o crea/edita/elimina sesiones individuales (`Sessions`). Alias compatible en `PUT /scheduling/schedules/{offering}`. |
| `DELETE` | `/scheduling/offerings/{offering}` | `scheduling.manage` | Elimina un `CourseOffering` (confirmado con `AlertDialog`). Alias compatible en `DELETE /scheduling/schedules/{offering}`. |
| `POST` | `/scheduling/offerings/{offering}/sessions` | `scheduling.manage` | Crea manualmente una sesión individual (`Session`) dentro del `CourseOffering`: `{ session_date, starts_at, ends_at, teacher_id, classroom_id?, meeting_url?, status? }` (sin requerir título/tema ni referencia a lecciones). |
| `PUT` | `/scheduling/sessions/{session}` | `scheduling.manage` | Edita una sesión individual (fecha, horario, docente, aula, enlace o estado). |
| `DELETE` | `/scheduling/sessions/{session}` | `scheduling.manage` | Elimina una sesión individual (con `AlertDialog`). |
| `CRUD` | `/scheduling/classrooms` | `scheduling.manage` | Catálogo de aulas físicas (`GET/POST/PUT/DELETE`). |
| `GET` | `/my/schedule` | `scheduling.view-own` | Horario de sesiones del docente autenticado. |

**Rutas retiradas**:
- `GET/POST/PUT/DELETE /scheduling/periods` (se elimina la entidad y pantalla de Periodos Académicos por clarificación).

## 2. Módulo de Matrícula de Estudiantes (`/enrollments`)

Protegido por el permiso `enrollments.manage` (asignable a roles en `/roles` y otorgado por defecto únicamente al rol Administrador).

| Método | Ruta | Permiso | Descripción |
|---|---|---|---|
| `GET` | `/enrollments` | `enrollments.manage` | Vista principal del módulo de matrícula (`Enrollments/Index.vue`). Recibe `?student_id=&search=`. Devuelve: lista de estudiantes activos (para `SearchableSelect`), catálogo de cursos publicados disponibles (indicando si el estudiante ya está matriculado), programaciones (`CourseOffering`) de cada curso con sus sesiones, docentes y cupos disponibles (`available_spots`), y las matrículas activas + sesiones del estudiante seleccionado para renderizar su horario en tiempo real (`ScheduleCalendar`). |
| `POST` | `/enrollments` | `enrollments.manage` | Matricula al estudiante en una programación académica: `{ student_id, course_offering_id }`. Valida transaccionalmente estado del estudiante y de la programación, cupos disponibles (`> 0`), no duplicidad en el mismo curso y ausencia de cruce de horario entre sesiones. Retorna redirect Inertia actualizando en tiempo real el horario y los cupos. |
| `DELETE` | `/enrollments/{enrollment}` | `enrollments.manage` | Desmatricula al estudiante del curso/programación (previa confirmación con `AlertDialog`), cambia su estado a `cancelled`, libera inmediatamente el cupo en el `CourseOffering` y actualiza el horario del estudiante en tiempo real. |

**Vistas ajustadas a modo consulta**:
- `Courses/Enrollments.vue` y `Students/Show.vue` muestran las matrículas e información del grupo (`CourseOffering`) en modo lectura con enlace directo al módulo `/enrollments?student_id={id}` para quien posea `enrollments.manage`.

## 3. Errores de Validación de Dominio (HTTP 422)

- `teacher_conflict`: El docente seleccionado ya tiene otra sesión programada en esa fecha y franja horaria.
- `classroom_conflict`: El aula seleccionada ya está ocupada por otra sesión en esa fecha y franja horaria.
- `student_schedule_conflict`: El estudiante tiene cruce de horario en la fecha y hora de una sesión con otro curso ya matriculado (detallando el curso, sesión y horario en conflicto).
- `offering_full`: La programación académica seleccionada no tiene cupos disponibles (`0` vacantes).
- `offering_unavailable`: La programación académica no está abierta para matrícula (`borrador`, `finalizada` o `cancelada`).
- `already_enrolled`: El estudiante ya cuenta con una matrícula activa en este curso.

## 4. Contratos de UI

- **Calendario de Programación Académica (`ScheduleCalendar` en `/scheduling`)**:
  - Cada docente presente en el calendario recibe un color distintivo (`color` y `contrastColor` de FullCalendar v7), sin mostrar una leyenda de docentes fuera del calendario.
  - Cada ítem/bloque dentro del calendario renderiza en líneas separadas y visibles (`#eventContent`) el horario, el curso y código de grupo, el docente (`Docente: {nombre}`) y el aula o modalidad (`Aula: {nombre}` / `Aula: Virtual` / `Aula: Híbrido · {nombre}`).
  - Hacer clic sobre cualquier ítem/bloque programado en el calendario emite `@event-click` con el `scheduleId` y abre el modal `Editar programación académica` precargado con los datos del `CourseOffering` correspondiente.
- **Modal de Programación Académica en Matrícula**: Al hacer clic en un curso disponible en `/enrollments`, se abre un modal (`Modal` del sistema de diseño) que detalla cada `CourseOffering` del curso: código de grupo, fechas de inicio y fin, modalidad, estado, docentes asignados, tabla/lista de sesiones individuales (fecha, día, hora inicio–fin, docente, aula/enlace, estado), cupos disponibles (`disponibles / capacidad máxima`), botón para "Matricular en este grupo" (deshabilitado si no hay cupos o ya está matriculado) y botón explícito "Cancelar".
- **Horario del Estudiante en Tiempo Real**: En la misma pantalla `/enrollments`, un panel con `ScheduleCalendar` (FullCalendar en español) muestra en tiempo real todas las sesiones de los cursos en los que el estudiante seleccionado está matriculado, junto con la lista de cursos matriculados y el botón "Desmatricular" (que invoca `requestAlertDialog` antes de enviar `DELETE /enrollments/{enrollment}`).

