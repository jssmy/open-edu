# Research: Programación académica (`CourseOffering`, `Sessions`) y Matrícula de estudiantes

## D1. Evolución unificada de `Scheduling` hacia `CourseOffering` y `Sessions`
- **Decision**: Evolucionar el módulo `app/Modules/Scheduling` para gestionar `CourseOffering` (edición/grupo de un curso con código de grupo autogenerado secuencialmente como `GRP-01`, `GRP-02`, ... por curso, `starts_on`, `ends_on`, `max_students`, modalidad `in_person | virtual | hybrid`, estado `draft | published | in_progress | finished | cancelled` y un único docente asignado al grupo) y `CourseSession` (sesiones individuales por fecha con `session_date`, `starts_at`, `ends_at`, docente del grupo, `classroom_id` nullable, `meeting_url` nullable y estado `scheduled | in_progress | finished | cancelled`, sin solicitar título/tema ni acoplar a las lecciones del curso). Se retira `AcademicPeriod` (`academic_periods` y `/scheduling/periods`).
- **Rationale**: Clarificado con el usuario (sesiones 2026-11-03 y 2026-11-04); evita mantener dos modelos paralelos de horarios, asigna un solo docente responsable por grupo, deja libre al docente cómo avanzar las lecciones durante sus clases y permite programar sesiones por meses y editarlas libremente sin bloqueos de periodo.
- **Alternatives considered**: Crear un tercer módulo separado para `CourseOffering` o acoplar sesiones a lecciones/múltiples docentes por grupo (descartado por romper la flexibilidad pedagógica del docente y complicar el flujo).

## D2. Transición automática de estados (`en_curso` / `finalizada`) por fecha y hora
- **Decision**: Calcular/sincronizar de forma determinista el estado efectivo tanto en el dominio como en las consultas/casos de uso cuando `status` no es `cancelled` ni `draft`:
  - Para `CourseOffering` (`published | in_progress | finished`): si hoy `< starts_on` → `published`; si `starts_on <= hoy <= ends_on` → `in_progress` (`en_curso`); si `hoy > ends_on` → `finished` (`finalizada`).
  - Para `CourseSession` (`scheduled | in_progress | finished`): según `session_date` + `starts_at` / `ends_at` frente al instante actual (`now`), pasa automáticamente a `in_progress` (`en_curso`) y luego a `finished` (`finalizada`), salvo que esté `cancelled` (`cancelada`).
- **Rationale**: Garantiza exactitud inmediata al consultar o validar matrículas sin depender de demonios o cron jobs externos (alineado con Principio I de la constitución: sin infraestructura distribuida ni colas innecesarias).
- **Alternatives considered**: Cron job por minuto en segundo plano (descartado por introducir latencia de hasta 60s y complejidad operativa innecesaria).

## D3. Detección de conflictos por fecha y hora (`SessionConflictDetector`)
- **Decision**: Regla de dominio pura en `Scheduling\Domain\ConflictDetector` que evalúa sesiones individuales (`session_date`, `starts_at`, `ends_at`) ignorando sesiones con estado `cancelled`:
  - Dos sesiones en la misma `session_date` se traslapan si y solo si `starts_at < other.ends_at && ends_at > other.starts_at` (bloques consecutivos donde `ends_at == other.starts_at` son válidos).
  - En programación académica: valida que el `teacher_id` y el `classroom_id` (cuando aplica) no tengan otra sesión no cancelada traslapada en esa misma fecha.
  - En matrícula (`Enrollments`): valida que ninguna sesión no cancelada del `CourseOffering` elegido se traslape en `session_date` y hora con las sesiones no canceladas de las demás matrículas activas del estudiante.
- **Rationale**: Regla crítica garantizada en backend dentro de una transacción con bloqueo pesimista (`lockForUpdate` sobre el `CourseOffering` y filas relacionadas).
- **Alternatives considered**: Validar solo en el cliente Vue (descartado por Principio I y VIII de la constitución).

## D4. Control de vacantes, desmatrícula y re-matrícula en `Enrollments`
- **Decision**: Vincular `enrollments` a `course_offering_id` (además de `student_id` y `course_id`). Al matricular:
  - Se bloquea la fila de `course_offerings` (`SELECT ... FOR UPDATE`), se cuentan las matrículas con `status = 'active'` para ese `course_offering_id` y se verifica `active_count < max_students`.
  - Se verifica que el estudiante no tenga otra matrícula `active` en el mismo `course_id`.
  - Si existe una matrícula previa `cancelled` del mismo estudiante en ese `course_id`, se reactiva y actualiza al nuevo `course_offering_id` y `enrolled_at`, preservando intacta la tabla `lesson_progress`.
  - Al desmatricular (`DELETE /enrollments/{enrollment}` o baja desde el módulo de matrícula), la matrícula pasa a `cancelled` (liberando de inmediato la vacante y retirando sus sesiones del horario activo del estudiante sin borrar `lesson_progress`).
- **Rationale**: Cumple FR-006 y FR-008, evita errores de clave única `(student_id, course_id)` al re-matricular y previene sobrecupo bajo concurrencia (SC-002, SC-004).
- **Alternatives considered**: Borrado físico de `lesson_progress` al desmatricular (descartado por clarificación Q3 de sesión 2).

## D5. Calendario en tiempo real y componentes del Sistema de Diseño
- **Decision**: Reutilizar `ScheduleCalendar.vue` (basado en `@fullcalendar/vue3` v7, exigido por el Principio XI de la constitución v1.5.0) tanto en `/scheduling` (con soporte de fechas reales de sesiones por mes/semana/día, asignación de un color distintivo por docente mediante `color` y `contrastColor` sin mostrar leyenda de docentes sobre el calendario, slot `#eventContent` multilínea que muestra dentro de cada ítem del calendario el horario, curso/grupo, `Docente: ...` y `Aula: ...` sin truncarlos en una sola línea, y emisión de `eventClick` para abrir el modal `Editar programación académica`) como en `/enrollments` (para mostrar el horario del estudiante en tiempo real conforme se matricula o desmatricula via Inertia partial reloads / estado reactivo), `SearchableSelect.vue` (con buscador y máximo 5 elementos visibles con scroll) para seleccionar curso, docente único del grupo y estudiante, y `requestAlertDialog` para confirmar la desmatrícula o eliminación.
- **Rationale**: Cumple los Principios V, VI y XI de la constitución sin añadir nuevas dependencias de terceros, y soluciona el truncado por defecto (`white-space: nowrap`) y el manejo de `--fc-event-color` (`color`/`contrastColor`) del tema clásico de FullCalendar v7.
- **Alternatives considered**: Construir una grilla HTML manual para el horario del estudiante (prohibido explícitamente por el Principio XI de la constitución) o depender del renderizado de una sola línea por defecto de FullCalendar (descartado porque ocultaba el docente y el aula dentro de cada bloque).

