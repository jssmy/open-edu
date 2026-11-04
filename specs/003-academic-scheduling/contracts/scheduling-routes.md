# Contrato HTTP/UI: Programación académica (Inertia)

Todas bajo `auth`; autorización en backend (Policy/Gate), no solo en UI.

| Método | Ruta | Permiso | Descripción |
|---|---|---|---|
| GET | `/scheduling` | `scheduling.manage` | Calendario y lista; filtros `?course=&teacher=&classroom=&period=` |
| GET | `/scheduling/pending` | `scheduling.manage` | Lista "Por programar" (paginada) |
| POST | `/scheduling/schedules` | `scheduling.manage` | Crea programación: `course_id, teacher_id, academic_period_id, modality, classroom_id?, blocks[{day_of_week,starts_at,ends_at}]` |
| PUT | `/scheduling/schedules/{id}` | `scheduling.manage` | Edita/reasigna; mueve bloques (arrastre) |
| DELETE | `/scheduling/schedules/{id}` | `scheduling.manage` | Elimina (con `AlertDialog`) |
| CRUD | `/scheduling/classrooms` | `scheduling.manage` | Aulas |
| CRUD | `/scheduling/periods` | `scheduling.manage` | Periodos |
| GET | `/my/schedule` | `scheduling.view-own` | Horario del docente autenticado |

**Errores de validación (422)**: `teacher_conflict`, `classroom_conflict` (incluyen curso y franja en conflicto), `classroom_required`, `invalid_time_range`, `period_read_only`.

**Retirado**: `PUT /courses/{course}/teachers` (`courses.teachers`).

**UI**:
- `ScheduleCalendar` (FullCalendar: semanal/diaria, selección y arrastre, es) emite `select`, `move`; el backend valida y responde.
- `SearchableSelect` (dropdown reutilizable con buscador, máximo 5 docentes visibles con scroll interno `max-h-[13.75rem]`) para la selección de docente en "Nueva programación académica".
- Todos los modales incluyen botón explícito `Cancelar`.
