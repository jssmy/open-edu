# Data Model: Programación académica (`CourseOffering`, `Sessions`) y Matrícula de estudiantes

## 1. `classrooms` (existente, se mantiene)
- `id`: PK bigint
- `name`: string único
- `capacity`: unsigned integer nullable (capacidad sugerida por defecto para sesiones/grupos presenciales)
- `is_active`: boolean default `true`
- `timestamps`

## 2. `course_offerings` (reemplaza y evoluciona a `course_schedules`; se elimina `academic_periods`)
Representa una edición o grupo específico de un curso (`CourseOffering`).
- `id`: PK bigint
- `course_id`: FK `courses(id)` cascadeOnDelete
- `group_code`: string(80) (código del grupo autogenerado secuencialmente por curso como `"GRP-01"`, `"GRP-02"`, etc., único por `course_id`)
- `starts_on`: date (fecha de inicio de la programación)
- `ends_on`: date (fecha de finalización de la programación; `ends_on >= starts_on`)
- `max_students`: unsigned integer (capacidad máxima de estudiantes, `>= 1`)
- `modality`: string(20) enum (`in_person`, `virtual`, `hybrid`)
- `status`: string(20) enum (`draft`, `published`, `in_progress`, `finished`, `cancelled`)
- `timestamps`
- **Índices**: `unique(['course_id', 'group_code'])`, `index(['course_id', 'status'])`
- **Reglas de estado**:
  - `draft` (`borrador`): editable, no visible para matrícula.
  - `published` (`publicada`), `in_progress` (`en_curso`), `finished` (`finalizada`): cuando una programación sale de `draft` y no está `cancelled`, su estado efectivo transiciona automáticamente según la fecha actual (`hoy < starts_on` → `published`; `starts_on <= hoy <= ends_on` → `in_progress`; `hoy > ends_on` → `finished`).
  - `cancelled` (`cancelada`): estado terminal manual; no admite matrículas.
  - Disponible para matrícula cuando el estado efectivo es `published` o `in_progress` y `enrolled_count < max_students`.

## 3. `course_offering_teacher` (asignación de docente único por grupo)
Vincula a cada `CourseOffering` con su único docente asignado (regla de dominio: exactamente 1 docente por grupo).
- `id`: PK bigint
- `course_offering_id`: FK `course_offerings(id)` cascadeOnDelete
- `teacher_id`: FK `users(id)` cascadeOnDelete
- `timestamps`
- **Índices**: `unique(['course_offering_id', 'teacher_id'])`, `index(['teacher_id'])`
- **Sincronización derivada**: `course_teacher` (`course_id`, `user_id`) se mantiene sincronizada con la unión de docentes de los `course_offerings` del curso para que el detalle del curso (`/courses/{id}`) y las políticas existentes (`TeacherScopeTest`) sigan funcionando sin regresiones.

## 4. `course_sessions` (reemplaza a `schedule_blocks`)
Representa cada clase o sesión individual creada manualmente dentro de un `CourseOffering` (sin campo de título/tema en UI ni acoplamiento a lecciones del curso).
- `id`: PK bigint
- `course_offering_id`: FK `course_offerings(id)` cascadeOnDelete
- `title`: string(160) (etiqueta interna autodefinida por defecto como `'Sesión de clase'`, no solicitada al usuario)
- `session_date`: date (fecha exacta de la sesión; dentro del horizonte de programación)
- `starts_at`: string(5) (`HH:MM`, 24h)
- `ends_at`: string(5) (`HH:MM`, 24h; `ends_at > starts_at`)
- `teacher_id`: FK `users(id)` restrictOnDelete (corresponde al docente único asignado al `CourseOffering`)
- `classroom_id`: FK nullable `classrooms(id)` restrictOnDelete (requerido cuando la sesión es presencial; opcional en híbrida; nulo en virtual)
- `meeting_url`: string(500) nullable (enlace de videoconferencia cuando la sesión es virtual o híbrida)
- `status`: string(20) enum (`scheduled`, `in_progress`, `finished`, `cancelled`)
- `timestamps`
- **Índices**: `index(['course_offering_id', 'session_date'])`, `index(['teacher_id', 'session_date'])`, `index(['classroom_id', 'session_date'])`
- **Reglas de validación y estado**:
  - `ends_at > starts_at`.
  - Si la modalidad del `CourseOffering` es `in_person`, `classroom_id` es obligatorio; si es `virtual`, `classroom_id` es nulo (`meeting_url` opcional/recomendado); si es `hybrid`, debe indicarse `classroom_id`, `meeting_url` o ambos.
  - El estado efectivo de una sesión no cancelada transiciona automáticamente a `in_progress` (`en_curso`) cuando `now` está entre `session_date + starts_at` y `session_date + ends_at`, y a `finished` (`finalizada`) cuando supera `session_date + ends_at`.
  - **Conflictos**: ninguna sesión con estado distinto de `cancelled` puede traslaparse en la misma `session_date` (`starts_at < other.ends_at && ends_at > other.starts_at`) para el mismo `teacher_id` ni para el mismo `classroom_id`.

## 5. `enrollments` (evolución de la tabla existente)
Vincula al estudiante con un `CourseOffering` específico de un curso.
- `id`: PK bigint
- `student_id`: FK `students(id)` cascadeOnDelete
- `course_id`: FK `courses(id)` cascadeOnDelete
- `course_offering_id`: FK nullable `course_offerings(id)` restrictOnDelete (obligatorio para toda matrícula creada desde el módulo de matrículas)
- `status`: string(20) enum (`active`, `suspended`, `cancelled`)
- `enrolled_at`: timestamp
- `timestamps`
- **Índices**: `unique(['student_id', 'course_id'])`, `index(['course_offering_id', 'status'])`, `index(['course_id', 'status'])`
- **Reglas de negocio**:
  - Cupos disponibles de un `CourseOffering`: `max_students - count(enrollments where course_offering_id = X and status = 'active')`.
  - Al matricular: valida estudiante activo, curso publicado, `CourseOffering` en estado `published` o `in_progress`, vacantes `> 0`, ausencia de otra matrícula `active` en el mismo `course_id` y ausencia de cruce de fecha/hora entre las `course_sessions` no canceladas del `CourseOffering` y las `course_sessions` no canceladas de las matrículas `active` actuales del estudiante.
  - Al desmatricular: cambia `status` a `cancelled`, libera inmediatamente el cupo y el horario del estudiante, conserva `lesson_progress` y permite futura re-matrícula (actualizando el registro existente de `(student_id, course_id)` a `status = 'active'` y al nuevo `course_offering_id`).

