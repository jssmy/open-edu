# Research: Programación académica

## D1. Librería de calendario
- **Decisión**: FullCalendar (`@fullcalendar/vue3`, `core`, `daygrid`, `timegrid`, `interaction`, locale `es`), envuelta en un componente `ScheduleCalendar` del sistema de diseño.
- **Rationale**: Exigido por la constitución v1.5.0 (Principio XI); soporta vistas semanal/diaria, selección y arrastre de bloques, eventos recurrentes semanales y Vue 3.
- **Alternativas**: Schedule-X, vue-cal (más ligeras, menor ecosistema); descartadas por la constitución y madurez.
- **Nota**: añadir dependencias requiere aprobación (AGENTS.md); se solicita en la fase de tareas.

## D2. Nuevo módulo `Scheduling`
- **Decisión**: Módulo `app/Modules/Scheduling` (Domain/Application/Infrastructure) con su `SchedulingServiceProvider`, siguiendo el patrón de `Courses`.
- **Rationale**: Principio I (monolito modular hexagonal); mantiene bajo acoplamiento con `Courses`.
- **Alternativa**: ampliar `Courses`; descartada por mezclar responsabilidades.

## D3. Fuente de verdad de la asignación docente
- **Decisión**: `course_teacher` se mantiene como tabla derivada, sincronizada por el caso de uso al crear/editar/eliminar programaciones; el endpoint `PUT /courses/{course}/teachers` y `AssignTeachers` se retiran y el detalle del curso muestra docentes en solo lectura.
- **Rationale**: Las políticas y consultas existentes (`TeacherScopeTest`) siguen funcionando sin cambios.
- **Alternativa**: eliminar `course_teacher` y consultar programaciones; mayor riesgo de regresión.

## D4. Detección de conflictos
- **Decisión**: Regla de dominio pura `ConflictDetector` (mismo periodo; traslape si `inicio < otroFin && fin > otroInicio` el mismo día; docente siempre, aula solo si presencial). Ejecutada en el backend dentro de una transacción con bloqueo de las filas del docente/aula; el calendario solo presenta.
- **Rationale**: Constitución (reglas críticas en backend); bloques consecutivos no son conflicto.

## D5. Periodos y solo lectura
- **Decisión**: Periodo con `starts_on`/`ends_on`; periodo finalizado → programaciones bloqueadas (solo lectura), sin reapertura (clarificado).
- **Rationale**: Protege el historial sin impedir correcciones.

## D6. Permiso
- **Decisión**: Permiso `scheduling.manage` (Spatie, como `courses.manage`), asignado al rol administrador por seeder; docentes ven su horario con `scheduling.view-own`.

## D7. Migración de asignaciones existentes
- **Decisión**: Cada fila de `course_teacher` genera una programación con `period_id` nulo y sin bloques, estado "por programar", listada en "Por programar".
