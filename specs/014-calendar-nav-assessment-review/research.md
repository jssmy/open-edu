# Research & Technical Decisions: Navegación Temporal y Revisión de Evaluaciones

## Decision 1: API de FullCalendar para Navegación y Período Visible

- **Decision**: Acceder a la instancia del calendario vía `ref` en `<FullCalendar ref="calendarRef" />` llamando a `getApi()` para ejecutar `.prev()`, `.next()`, `.today()`. Escuchar el evento o hook `datesSet` de FullCalendar para mantener actualizada una variable reactiva `periodTitle` (ej. "noviembre de 2026", "2 – 8 de nov. de 2026", "4 de nov. de 2026") generada por la API o formateada con `toLocaleDateString`.
- **Rationale**: FullCalendar calcula con precisión matemática los saltos de fechas según la vista activa: en `dayGridMonth` avanza 1 mes, en `timeGridWeek` avanza 1 semana y en `timeGridDay` avanza 1 día. Usar su API nativa evita cálculos manuales de fechas con riesgo de errores en cambios de mes o año.
- **Alternatives Considered**:
  - Manipular `initialDate` manualmente desde Vue: Descartado porque requiere recalcular días según la vista activa y recrear el componente, perdiendo transiciones suaves.

## Decision 2: Autorización y Parámetros para `GET /assessments/{assessment}/review`

- **Decision**: 
  1. Si el usuario autenticado tiene el rol `estudiante`, el endpoint resuelve automáticamente su propio `student_id` (o valida que el `student_id` consultado coincida con su perfil de alumno).
  2. Si el usuario es docente o administrador (`assessments.view` o `students.view`), permite especificar `?student_id={id}` para revisar los intentos de ese alumno en particular.
- **Rationale**: Respeta la privacidad y seguridad de las calificaciones escolares, evitando que un estudiante revise los intentos de otros compañeros mientras permite que los docentes auditen a cualquier estudiante de sus clases.
- **Alternatives Considered**:
  - Reutilizar `Assessments/Results.vue`: Descartado según lo acordado en clarificación (Opción B: crear `Assessments/Review.vue`), para tener una vista especializada y limpia con cada pregunta, sus opciones completas y la respuesta marcada por el estudiante.

## Decision 3: Interfaz de Solo Lectura con Retroalimentación Visual

- **Decision**: En `Assessments/Review.vue`, las opciones de respuesta se renderizan como lista de alternativas estáticas (tarjetas o radio buttons deshabilitados / chips). Si el intento está terminado:
  - La opción marcada por el alumno se resalta visualmente.
  - Se muestra un icono/badge verde o rojo indicando si fue la respuesta correcta o incorrecta y los puntos asignados a la pregunta.
  - Si el intento está `in_progress` y el usuario es estudiante, se exhibe un banner de aviso con el botón "Continuar evaluación" que redirige a `/assessments/{id}/take`.
- **Rationale**: Brinda la mejor experiencia de usuario didáctica para estudiantes y docentes, cumpliendo a cabalidad con la solicitud del usuario.

## Decision 4: Optimización de Consultas SQL y Proyecciones en Base de Datos

- **Decision**:
  1. **Evitar ordenamientos pesados por SQL (`orderByRaw` CASE)**:
     - En `TeacherClassController::index()`, el ordenamiento condicional (`CASE WHEN starts_on IS NULL THEN 2 WHEN starts_on >= CURRENT_DATE THEN 0 ELSE 1 END`) traslada la carga a la base de datos sin poder aprovechar índices convencionales sobre `starts_on`.
     - Alternativa optimizada: Traer los registros de la consulta filtrados y ordenarlos en memoria con Laravel Collections (`collect()->sort()`), igual a como se implementó eficientemente en `GetStudentClasses`.
  2. **Proyección estricta de columnas en relaciones SQL (`select:id,name,...`)**:
     - En `TeacherClassController::index()`, `CourseOffering::with(['course:id,name,description,cover_path,status', 'teachers:id,name', 'course.units:id,course_id', 'course.units.lessons:id,unit_id'])`. Al evitar traer campos pesados no utilizados como `objectives`, `intro_video_id`, `content` de lecciones o metadatos irrelevantes, se reduce drásticamente el payload de base de datos a memoria PHP.
  3. **Optimización en `AssessmentController::review`**:
     - Cargar relaciones con selección puntual de columnas: `course:id,name`, `attempts:id,assessment_id,student_id,status,score,passed,started_at,submitted_at`, y `attempt.answers:id,attempt_id,question_id,choice_id`.
     - Evitar re-consultas redundantes de usuario/estudiante seleccionando `users:id,name,email`.
- **Rationale**: Reduce tiempos de I/O en PostgreSQL, uso de buffer y serialización en PHP, mejorando el rendimiento bajo concurrencia.


