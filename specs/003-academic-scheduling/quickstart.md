# Quickstart: validar Programación académica

**Prerrequisitos**: `composer install`, `npm install` (incluye FullCalendar tras aprobación), `php artisan migrate --seed`.

1. Como administrador, crear un periodo, un aula y abrir `/scheduling`.
2. Crear programación presencial (docente, aula, bloque lun 08:00–10:00) → aparece en el calendario y el docente figura en el curso (solo lectura).
3. Crear otra para el mismo docente lun 09:00–11:00 → rechazo `teacher_conflict`; con bloque 10:00–12:00 → aceptada.
4. Programar mismo horario en la misma aula → `classroom_conflict`; marcar virtual → aceptada sin aula (docente distinto).
5. Arrastrar un bloque en el calendario → se guarda o se rechaza por conflicto.
6. Verificar que `/courses/{id}` ya no permite asignar docentes y que las asignaciones previas aparecen en "Por programar".
7. Como docente, abrir `/my/schedule`.

**Pruebas**: `php artisan test --compact --filter=Scheduling` · `npm test`.
