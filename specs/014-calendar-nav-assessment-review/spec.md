# Feature Specification: Navegación Temporal en Calendarios y Detalle de Evaluaciones en Modo Consulta

**Feature Branch**: `014-calendar-nav-assessment-review`

**Created**: 2026-11-04

**Status**: Draft

**Input**: User description: "en los calendario debe haber un boton de sigiente, entonces si tiene seleccionado mes debe ir al siguinete mes y mostrar la programacion del siguiente mes, pasa lo mismo para dia y semana. http://localhost:8000/students/2 aquie se muestran las evaluaciones rendidas, entonces se puede ir al detalle de las evalucoens y ver las preguntas y respuestas pero sinpoder editaras, estga misma es para la vista del estudiante y docente donde pueden las evaluaciones terminadas ya sean terinadas o en curso pero no pueden editarlas, cuando es estudiante en caso este en curso debe haber una opcion que te redirija a rendir el examen"

## Clarifications

### Session 2026-11-04
- Q: ¿Qué ruta o pantalla debe utilizarse para ver el detalle de preguntas y respuestas de la evaluación en modo consulta? → A: Opción B: Crear una ruta y página exclusiva nueva (ej. `/assessments/{id}/review?student_id={id}`) separada de `/results`.
- Q: ¿Qué nivel de detalle de respuestas debe visualizarse en la página de revisión (`/assessments/{id}/review`)? → A: Opción A: Mostrar el enunciado, las opciones de respuesta, la opción marcada por el alumno, y si está terminada indicar si fue correcta o incorrecta con su puntaje.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Controles de Navegación Temporal en el Calendario (Priority: P1)

Como docente, estudiante o administrador académico que visualiza el calendario interactivo de horarios (`ScheduleCalendar`), quiero disponer de botones de navegación temporal ("Anterior", "Hoy", "Siguiente") junto con una etiqueta clara del período visible (ej. "Noviembre 2026", "02 - 08 Nov 2026", "04 Nov 2026") para desplazarme dinámicamente según la vista activa (mes, semana o día) y consultar las programaciones académicas correspondientes a ese período.

**Why this priority**: Actualmente el calendario no cuenta con controles de paginación/avance temporal en su cabecera personalizada, impidiendo explorar semanas pasadas o futuras tanto en vista de mes, semana como diaria.

**Independent Test**: Abrir cualquier pantalla con calendario (ej. `/scheduling`, `/my/schedule`). Presionar el botón "Siguiente" en vista semanal y verificar que avanza 1 semana; cambiar a vista mensual y presionar "Siguiente" para avanzar 1 mes; cambiar a vista diaria y avanzar 1 día. Verificar que "Anterior" retrocede y "Hoy" regresa a la fecha presente.

**Acceptance Scenarios**:

1. **Dado que** el usuario está en la vista **Semanal**, **Cuando** hace clic en "Siguiente", **Entonces** el calendario avanza 7 días mostrando los bloques y fechas de la semana siguiente y actualizando el título del rango visible.
2. **Dado que** el usuario está en la vista **Mensual**, **Cuando** hace clic en "Siguiente", **Entonces** el calendario muestra el mes calendario siguiente con sus respectivas sesiones programadas.
3. **Dado que** el usuario está en la vista **Diaria**, **Cuando** hace clic en "Siguiente", **Entonces** el calendario avanza al día siguiente.
4. **Dado que** el usuario ha avanzado o retrocedido fechas, **Cuando** presiona el botón "Hoy", **Entonces** el calendario vuelve inmediatamente a la fecha actual conservando la vista activa.

---

### User Story 2 - Detalle de Evaluación en Modo Consulta (Solo Lectura) en Ruta Exclusiva de Revisión (Priority: P2)

Como docente, administrador o estudiante que consulta la ficha del alumno en `/students/{studentId}` (o historial de cursos), quiero hacer clic en una evaluación para acceder a una ruta y página exclusiva `/assessments/{id}/review?student_id={studentId}` donde pueda revisar el enunciado de cada pregunta, sus opciones disponibles, la opción marcada por el alumno y, si la evaluación está terminada, el indicador de si fue correcta o incorrecta con su puntaje asignado, todo en estricto modo de solo lectura sin posibilidad de editar.

**Why this priority**: Proporciona una pantalla dedicada, limpia y libre de formularios de edición para la retroalimentación y auditoría de la prueba, asegurando que ni el docente ni el alumno modifiquen accidental o intencionalmente las respuestas.

**Independent Test**: Desde la ficha de estudiante `/students/{id}`, hacer clic en una evaluación rendida o en curso. El sistema debe navegar a `/assessments/{id}/review?student_id={id}` mostrando las preguntas, las opciones elegidas por el estudiante, su corrección (si está finalizada) y puntajes, sin ningún campo modificable ni botón de guardar.

**Acceptance Scenarios**:

1. **Dado que** un docente o estudiante hace clic en una evaluación desde `/students/{studentId}`, **Cuando** el navegador carga `/assessments/{id}/review?student_id={id}`, **Entonces** se muestran las preguntas con sus enunciados, opciones y respuestas del estudiante en modo solo lectura.
2. **Dado que** la evaluación tiene estado "terminada", **Cuando** el usuario la inspecciona, **Entonces** se visualiza para cada pregunta si la respuesta fue correcta o incorrecta junto al puntaje obtenido y el total del examen.
3. **Dado que** la evaluación está "en curso" y el usuario es docente o administrador, **Cuando** accede a la revisión, **Entonces** observa las respuestas guardadas hasta el momento en modo lectura para seguimiento académico sin poder editarlas.

---

### User Story 3 - Acción de Continuar Rendición para Evaluaciones en Curso (Estudiantes) (Priority: P3)

Como estudiante que visualiza una evaluación que aún se encuentra "en curso", quiero disponer de un botón visible ("Continuar evaluación" / "Rendir examen") en la vista de revisión o en la ficha para ser redirigido directamente a la pantalla de resolución activa del examen (`/assessments/{id}/take`).

**Why this priority**: Evita que un estudiante quede bloqueado si una evaluación quedó en progreso, ofreciendo una ruta clara y directa para reanudar su examen antes de que finalice el tiempo permitido.

**Independent Test**: Acceder como estudiante a una evaluación con estado `in_progress`. Verificar que se presenta un botón destacado "Rendir examen" / "Continuar evaluación" que redirige a `/assessments/{id}/take`.

**Acceptance Scenarios**:

1. **Dado que** el usuario autenticado es un estudiante y una evaluación tiene intento activo con estado "en curso", **Cuando** visualiza la evaluación en `/assessments/{id}/review`, **Entonces** se muestra un botón destacado que redirige a `/assessments/{assessmentId}/take`.
2. **Dado que** el usuario es un docente o administrador, **Cuando** la evaluación está en curso, **Entonces** puede consultar el estado y avance de las respuestas sin ver la opción de rendir el examen.

---

### Edge Cases

- **Navegación temporal sin eventos**: Si el usuario navega a un mes o semana futura sin programaciones, el calendario debe renderizarse fluidamente mostrando los días vacíos sin errores.
- **Evaluación sin intento registrado**: Si un docente hace clic en una evaluación que aún no ha sido rendida ("Sin rendir"), la página de revisión debe indicar con claridad que el alumno aún no inicia ni registra respuestas en la evaluación.
- **Evaluación con tiempo expirado**: Si una evaluación en curso ya excedió el tiempo límite, el botón de continuar no debe permitir reanudarla y debe reflejar el estado correspondiente.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El componente `ScheduleCalendar` DEBE incluir controles de navegación interactiva: botón "Anterior", botón "Siguiente", botón "Hoy" y el texto de la fecha o período actual visible.
- **FR-002**: El avance del botón "Siguiente" y retroceso del botón "Anterior" en `ScheduleCalendar` DEBE adaptarse automáticamente a la vista seleccionada (1 mes en `dayGridMonth`, 1 semana en `timeGridWeek`, 1 día en `timeGridDay`).
- **FR-003**: En la ficha del estudiante `/students/{studentId}`, cada tarjeta o elemento de evaluación DEBE incluir un enlace directo a la nueva ruta de revisión `/assessments/{id}/review?student_id={studentId}`.
- **FR-004**: Se DEBE proveer la ruta `GET /assessments/{assessment}/review` renderizando la página Vue dedicada `Assessments/Review.vue`.
- **FR-005**: La página `Assessments/Review.vue` DEBE renderizar en modo estricto de solo lectura: el título, instrucciones, enunciados de preguntas, opciones, respuestas marcadas por el estudiante y, si el intento está finalizado, el estado de acierto/error y puntaje por pregunta.
- **FR-006**: Cuando el usuario autenticado sea el estudiante y la evaluación cuente con un intento con estado "en curso", la página DEBE incluir un botón de acción destacado para redirigirlo a `/assessments/{id}/take`.
- **FR-007**: Si el usuario es docente o administrador, la vista DEBE ser estrictamente de consulta y supervisión docente, sin opción de rendir el examen ni de alterar respuestas.

### Key Entities *(include if feature involves data)*

- **ScheduleCalendar Controls**: Estado de fecha activa (`currentDate`), vista activa (`currentView`) y API de FullCalendar (`prev()`, `next()`, `today()`).
- **Student Assessment Attempt**: Relación entre `Student`, `Assessment` y `AssessmentAttempt` con sus respuestas (`AssessmentAnswer`), puntajes y estado (`in_progress` o `completed`).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Los usuarios pueden desplazarse entre meses, semanas y días en el calendario con un solo clic y tiempo de respuesta inferior a 300 ms.
- **SC-002**: El 100% de los accesos al detalle de evaluación desde la ficha de estudiante garantizan la integridad de los datos evitando cualquier modificación accidental de preguntas o respuestas.
- **SC-003**: Los estudiantes con exámenes en curso pueden retomar su intento en un solo clic desde la vista de consulta hacia la interfaz de toma de examen.

## Assumptions

- El motor de calendario `FullCalendar` instalado en la aplicación expone la API estándar `.next()`, `.prev()`, `.today()` y `.getDate()`.
- La información de preguntas y respuestas del intento del estudiante se encuentra persistida en la base de datos de evaluaciones (`assessments`, `questions`, `assessment_attempts`, `assessment_answers`).
