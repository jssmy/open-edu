# Data Model & State Flow: Programación Académica Basada en Docente

## Entidades Relevantes

### 1. User (Docente)
- `id`: number
- `name`: string
- Rol: `docente`
- `is_active`: boolean (true)

### 2. CourseOffering (Oferta / Grupo)
- `id`: number
- `course_id`: number
- `group_code`: string
- `starts_on`: string (YYYY-MM-DD)
- `ends_on`: string (YYYY-MM-DD)
- `max_students`: number
- `modality`: `'in_person' | 'virtual' | 'hybrid'`
- `status`: `'draft' | 'published' | 'in_progress' | 'finished' | 'cancelled'`
- Relaciones:
  - `teachers`: Colección de `User` asignados como docentes.
  - `sessions`: Colección de `CourseSession`.

### 3. CourseSession (Sesión de Clase)
- `id`: number
- `course_offering_id`: number
- `teacher_id`: number
- `classroom_id`: number | null
- `session_date`: string (YYYY-MM-DD)
- `starts_at`: string (HH:MM)
- `ends_at`: string (HH:MM)
- `status`: `'scheduled' | 'in_progress' | 'finished' | 'cancelled'`

## Flujo de Estado y Parámetros en `/scheduling`

```mermaid
flowchart TD
    A[Usuario entra a /scheduling] --> B{¿Param teacher presente?}
    B -- No --> C[Asignar primer docente activo por defecto]
    B -- Sí --> D[Usar docente recibido o vacío para todos]
    C --> E[Filtrar calendario y tabla con teacher_id]
    D --> E
    E --> F[Renderizar Calendario con bloques del docente]
    E --> G[Renderizar Tabla con ofertas del docente]
    H[Usuario cambia docente en SearchableSelect superior] --> I[Inertia visit con nuevo teacher_id]
    I --> E
    J[Usuario aplica filtros en tabla inferior q/course/classroom/status] --> K[Inertia visit manteniendo teacher_id y actualizando tabla]
    K --> G
```
