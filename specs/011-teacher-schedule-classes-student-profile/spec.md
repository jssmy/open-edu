# Feature Specification: Horario por Curso, Clases Próximas y Ficha Integral de Estudiante

**Feature Branch**: `011-teacher-schedule-classes-student-profile`

**Created**: 2026-11-04

**Status**: Draft

**Input**: User description: "en http://localhost:8000/my/schedule el schedule el color del item de la programacion academica cambia por curso para el docente y el admin. en http://localhost:8000/my/classes los cursos deben mostrarse por el mas proximo a empezar. en http://localhost:8000/students/2 el docente puede ver inforamcion del estududiante, los cursos matriculados, el proceso de sus cursos, las evaluaciones y sus notas."

## Clarifications

### Session 2026-11-04

- Q: ¿Cómo debe agruparse y presentarse el progreso y las evaluaciones en la ficha del estudiante (`/students/{studentId}`)? → A: Por Curso integrado: cada curso matriculado muestra su barra de progreso y, anidado dentro de la tarjeta del curso, el desglose de evaluaciones rendidas con sus notas.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Colorización de Bloques por Curso en Horario Académico (Priority: P1)

Como docente o administrador de la plataforma  
Quiero que cada bloque o evento en el calendario de horarios (`/my/schedule` y vista administrativa de horarios) tenga un color diferenciado por curso  
Para que pueda distinguir visualmente a qué curso pertenece cada sesión programada de manera rápida e intuitiva.

**Why this priority**: Es la necesidad primordial de distinción visual en la agenda semanal tanto para profesores como para administradores, evitando confusiones entre clases simultáneas o continuas.

**Independent Test**: Acceder a `/my/schedule` con un docente o administrador que tenga asignadas sesiones de al menos 2 cursos distintos y verificar que los bloques de calendario de un mismo curso comparten el mismo color y difieren del color del otro curso.

**Acceptance Scenarios**:

1. **Given** un docente o administrador autenticado con sesiones de múltiples cursos en su agenda, **When** visita `/my/schedule` (o el calendario general de horarios), **Then** las sesiones del mismo curso tienen asignada la misma tonalidad cromática consistente y cursos diferentes tienen tonalidades distintas.
2. **Given** un bloque de sesión en el calendario, **When** se visualiza la información de la sesión, **Then** se mantiene legible el texto del bloque (curso, docente, aula/modalidad) con contraste adecuado sobre el color asignado.

---

### User Story 2 - Ordenamiento de Clases Docentes por Proximidad de Inicio (Priority: P2)

Como docente con grupos y cursos asignados  
Quiero ver la lista de mis clases en `/my/classes` ordenada de forma ascendente priorizando el grupo o clase más próximo a iniciar  
Para que pueda acceder inmediatamente al curso que debo dictar a continuación sin tener que buscar manualmente entre clases futuras o pasadas.

**Why this priority**: Optimiza directamente la rutina diaria del docente al preparar e ingresar a dictar su siguiente sesión.

**Independent Test**: Configurar clases con diferentes fechas de inicio (`starts_on`) y verificar que en `/my/classes` se listen en primer lugar las clases cuyo inicio es más inminente a la fecha actual.

**Acceptance Scenarios**:

1. **Given** un docente con varias ofertas de clase asignadas con fechas de inicio futuras y pasadas, **When** accede a `/my/classes`, **Then** las clases aparecen ordenadas cronológicamente con la clase más próxima a empezar en la primera posición.
2. **Given** clases sin fecha explícita de inicio o con igual fecha de inicio, **When** se renderiza la lista, **Then** se ordenan de manera consistente según su programación temporal o identificador sin romper la secuencia cronológica.

---

### User Story 3 - Vista Detallada de Estudiante para Docente: Matrículas, Progreso y Evaluaciones (Priority: P3)

Como docente de la institución  
Quiero ingresar a la ficha del estudiante (ej. `/students/{studentId}`) y consultar su información general, sus cursos matriculados presentados de forma integrada con su avance porcentual y el desglose de evaluaciones y notas por cada curso  
Para poder realizar un seguimiento académico riguroso y personalizado de cada alumno a mi cargo dentro del contexto de cada asignatura.

**Why this priority**: Brinda al docente visibilidad pedagógica completa sobre el rendimiento del estudiante (asistencia, avances y notas de evaluaciones).

**Independent Test**: Iniciar sesión como docente y abrir `/students/{studentId}` de un estudiante matriculado; verificar que se visualizan los datos del estudiante, las tarjetas de cursos integradas con la barra de progreso formativo y, dentro de cada curso, la lista de evaluaciones con sus notas y estados de aprobación.

**Acceptance Scenarios**:

1. **Given** un docente autorizado consultando `/students/{studentId}`, **When** se carga la vista, **Then** se visualizan los datos personales/académicos del alumno y la lista de cursos en los que está matriculado.
2. **Given** cada curso matriculado, **When** el docente inspecciona la tarjeta del curso, **Then** visualiza la barra de progreso (porcentaje y conteo de lecciones) y el desglose de las evaluaciones de dicho curso con sus notas obtenidas, número de intento y estado de aprobación.
3. **Given** que el docente solo tiene permiso de lectura o visualización sobre la ficha del estudiante, **When** consulta la página, **Then** no se le presentan controles administrativos de edición no autorizados (como modificar datos personales si no cuenta con permiso específico de gestión).

---

### Edge Cases

- **Estudiante sin evaluaciones o intentos rendidos**: Si un estudiante está matriculado en un curso pero no ha completado intentos de evaluación, la sección dentro de la tarjeta del curso debe mostrar un estado descriptivo ("Sin evaluaciones rendidas") sin errores ni calificaciones indefinidas.
- **Cursos finalizados o pasados en `/my/classes`**: Las clases que ya han iniciado o finalizado deben ubicarse de forma predecible luego de las clases en curso o próximas a iniciar.
- **Cursos con el mismo color o colisión de paleta**: Si existen más cursos que colores disponibles en la paleta base del calendario, el sistema debe asignar colores de manera cíclica y determinista basada en el identificador del curso.
- **Restricción de privacidad del estudiante**: Un docente sólo debe poder visualizar datos de rendimiento de estudiantes asociados a sus clases o cursos institucionales permitidos según las políticas de autorización (`canTeacherView`).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE asignar un color único y consistente por curso a los bloques de sesiones en el calendario de horarios para docentes y administradores.
- **FR-002**: El sistema DEBE preservar la legibilidad y contraste de los textos (nombre del curso, código de grupo, docente, aula/enlace) dentro del bloque coloreado por curso.
- **FR-003**: El sistema DEBE ordenar las clases en `/my/classes` colocando en primer orden aquellas cuya fecha y hora de inicio sean las más próximas a la fecha actual.
- **FR-004**: El sistema DEBE permitir a los docentes con permiso de visualización acceder al detalle del estudiante en `/students/{studentId}`.
- **FR-005**: La vista `/students/{studentId}` DEBE presentar la información básica del estudiante (nombre, correo, identificación, estado).
- **FR-006**: La vista `/students/{studentId}` DEBE mostrar los cursos matriculados de forma integrada, incluyendo en cada curso su barra de progreso formativo (porcentaje y lecciones completadas).
- **FR-007**: La vista `/students/{studentId}` DEBE exhibir, dentro de cada tarjeta de curso, las evaluaciones correspondientes con sus calificaciones obtenidas, número de intento y estado de aprobación.

### Key Entities *(include if feature involves data)*

- **CourseOffering / Schedule Session**: Representa la sesión y oferta horaria; incluye asociación al curso base para determinar la identidad cromática del bloque en el calendario.
- **StudentRecord**: Entidad de datos del estudiante que agrupa datos personales y estado de actividad.
- **CourseProgress**: Representación del avance del estudiante a través de los módulos y lecciones del curso.
- **AssessmentAttempt**: Registro de intento de evaluación de un estudiante con puntaje obtenido, calificación de aprobación y fecha de entrega asociado al curso correspondiente.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% de los bloques de horarios del mismo curso en `/my/schedule` comparten el mismo color distintivo.
- **SC-002**: En `/my/classes`, la primera tarjeta de clase listada corresponde siempre a la oferta activa o más próxima a iniciar cronológicamente.
- **SC-003**: El docente puede consultar el progreso y las notas de un estudiante en `/students/{id}` agrupados por curso en un único panel integrado sin necesidad de navegar a múltiples pantallas desconectadas.
- **SC-004**: Los tiempos de carga de la ficha del estudiante con progreso y evaluaciones se mantienen en menos de 1 segundo en condiciones normales de uso.

## Assumptions

- Se reutiliza la paleta de diseño institucional del sistema de diseño para garantizar accesibilidad y contraste WCAG AA en los bloques del calendario.
- El cálculo de progreso de cursos se apoya en el módulo de progreso existente (`App\Modules\Progress`).
- Las evaluaciones y notas mostradas corresponden a los intentos del estudiante registrados en el módulo de evaluaciones (`App\Modules\Assessments`).
- La regla de autorización permite al docente ver información académica de estudiantes a los que imparte clases o que pertenecen a su ámbito docente (`canTeacherView`).
