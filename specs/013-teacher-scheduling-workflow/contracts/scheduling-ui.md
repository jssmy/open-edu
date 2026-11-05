# Contract: Scheduling UI & Controller

## Endpoint: `GET /scheduling`

### Query Parameters

| Parámetro | Tipo | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `teacher` | `string \| number` | No | ID del docente seleccionado para filtrar el horario y la tabla. Si no se envía en la primera carga, el sistema adopta el ID del primer docente activo disponible. Si se envía cadena vacía `""`, se muestran todos los docentes. |
| `course` | `string \| number` | No | ID del curso para filtrar la tabla inferior. |
| `classroom` | `string \| number` | No | ID del aula para filtrar la tabla inferior. |
| `status` | `string` | No | Estado de la oferta (`draft`, `published`, `in_progress`, `finished`, `cancelled`). |
| `q` | `string` | No | Texto de búsqueda por curso, grupo o docente. |
| `page` | `number` | No | Número de página para la tabla inferior. |

### Inertia Page Props (`Scheduling/Index`)

```typescript
interface Props {
    schedules: {
        data: OfferingRow[];
        meta: PageMeta;
    };
    calendarBlocks: CalendarScheduleBlock[];
    calendarOfferings?: OfferingRow[];
    filters: {
        q: string;
        course: string;
        teacher: string; // ID del docente seleccionado (o '' para todos)
        classroom: string;
        status: string;
    };
    courses: { id: number; name: string; next_group_code?: string }[];
    teachers: { id: number; name: string }[];
    classrooms: { id: number; name: string; capacity: number | null }[];
    pendingCount: number;
}
```

### Contrato de Eventos y Comportamiento UI

1. **Selector Superior de Docente (`SearchableSelect`)**:
   - `options`: `[{ value: '', label: 'Todos los docentes' }, ...teachers.map(t => ({ value: String(t.id), label: t.name }))]`
   - Evento `change`: Dispara la recarga de `/scheduling` conservando el resto de filtros pero reiniciando `page` a 1.
2. **Formulario de Creación de Programación / Bloque**:
   - Si `filters.teacher` tiene un docente seleccionado, el campo `form.teacher_id` se precarga automáticamente con dicho valor.
3. **Formulario de Filtros de la Tabla Inferior**:
   - Contiene: `Input` para `q`, `Select` para `course`, `Select` para `classroom`, `Select` para `status`, botones `Buscar` y `Limpiar`.
   - Se procesa con `goToPage(1)` manteniendo el valor activo de `filters.teacher`.
