# Data Model: Programación académica

## academic_periods
`id`, `name` (único), `starts_on`, `ends_on`, `timestamps`. Validación: `ends_on > starts_on`. Estado derivado: vigente/finalizado (por fecha); finalizado es irreversible.

## classrooms
`id`, `name` (único), `capacity` nullable, `is_active` (bool), `timestamps`. No se elimina con programaciones vigentes (se desactiva o se reasigna).

## course_schedules (Programación)
`id`, `course_id` FK, `teacher_id` FK users, `academic_period_id` FK nullable (nulo = por programar), `modality` enum(`in_person`,`virtual`), `classroom_id` FK nullable, `timestamps`.
Reglas: `classroom_id` obligatorio si `in_person`; nulo si `virtual`; `teacher_id` con rol docente; si hay bloques, `academic_period_id` es obligatorio.
Índices: (`teacher_id`,`academic_period_id`), (`classroom_id`,`academic_period_id`), (`course_id`).

## schedule_blocks
`id`, `course_schedule_id` FK (cascade), `day_of_week` (1–7), `starts_at` time, `ends_at` time, `timestamps`. Regla: `ends_at > starts_at`.

## course_teacher (existente)
Derivada: contiene (`course_id`,`user_id`) por cada docente con ≥1 programación en el curso. Se sincroniza en el caso de uso.

## Transiciones
Por programar (sin periodo/bloques) → Programada (periodo + ≥1 bloque) → Bloqueada (periodo finalizado, terminal).
