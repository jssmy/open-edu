# Data Model: Visor de Lecciones y Clases de Docente

## Relaciones Existentes Aprovechadas

```
User (docente) 
  └── teacher_course_offering (pivote) ──> CourseOffering (Clase programada)
                                                └── course_id ──> Course
                                                                    └── units ──> lessons
```

- **CourseOffering**:
  - `id`: int
  - `course_id`: int (FK -> `courses.id`)
  - `code`: string (Código de clase / grupo)
  - `starts_on`, `ends_on`: dates
  - `is_active`: bool
  - Relación `teachers`: many-to-many con `User`.
  - Relación `course`: belongsTo con `Course`.

- **Course**:
  - `id`: int
  - `title`, `description`: string
  - `cover_image_path`: ?string
  - Relación `units`: hasMany ordenadas por posición.

- **Unit**:
  - `id`: int
  - `course_id`: int
  - `title`: string
  - `position`: int
  - Relación `lessons`: hasMany ordenadas por posición.

- **Lesson**:
  - `id`: int
  - `unit_id`: int
  - `title`: string
  - `content_type`: enum (`text`, `image`, `video`, `document`, `link`)
  - `content`: json / text
  - `position`: int

