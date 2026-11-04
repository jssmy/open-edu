# Feature Specification: Módulo de programación académica (CourseOffering, sesiones) y matrícula de estudiantes

**Feature Branch**: `004-student-class-enrollment`

**Created**: 2026-11-03

**Status**: Draft

**Input**: User description: "debe haber un modulo para matricular a los estudiantes en las clases, esta opion tiene roles los cuales por ahora solo el administrador debe tener. Durante la matricula puede ver los cursos disponibles, cuando da click al curso se abre un modal de la programacion academica en donde se detalla la programacion adademica, horarios, docentes, dias, cuando se vaya a matricular se valida el cruce de horarios y cupos disponibles. Mientras va matriculando al alumno puede ver su horario en tiempo real, se tiene la opcion de desmatricular en algun curso"

## Clarifications

### Session 2026-11-03

- Q: ¿Cómo se definen los cupos y la estructura de la programación académica y sus horarios? → A: Mediante `CourseOffering` (edición/grupo de un curso con nombre/código de grupo, fechas de inicio y fin, capacidad máxima de estudiantes, modalidad presencial/virtual/híbrida, estado `borrador | publicada | en curso | finalizada | cancelada` y un único docente asignado al grupo) compuesto por `Sessions` individuales (con fecha, hora de inicio y fin, aula opcional, enlace de videoconferencia opcional y estado, sin campo de título/tema ni atadura a lecciones).
- Q: ¿Quiénes deben poder ejecutar la matrícula y ver las programaciones disponibles en esta entrega? → A: Se controla mediante permisos de rol y por ahora solo el Administrador lo tiene activo por defecto (selecciona al alumno y gestiona su matrícula), pudiendo asignarse luego a otros roles o al estudiante.
- Q: ¿Cómo debe integrarse el nuevo modelo de `CourseOffering` y `Sessions` con el módulo actual de Programación Académica (`Scheduling`) y con la inscripción previa en el detalle del curso/estudiante? → A: Se unifica y evoluciona el módulo `Scheduling` actual al modelo `CourseOffering` + `Sessions`, y se centraliza el registro de matrículas en el nuevo módulo vinculándolas a un `CourseOffering` (dejando los listados en el detalle de curso y estudiante en modo consulta).
- Q: Al programar las sesiones (`Sessions`) de un `CourseOffering`, ¿cómo debe poder registrarlas el usuario? → A: Creación y edición manual de cada sesión individual una por una indicando únicamente su fecha exacta, hora de inicio y fin, aula/enlace y estado (asociadas al docente único asignado al grupo y sin campo de título/tema ni vínculo a lecciones).
- Q: ¿Cómo debe representarse el "Periodo de matrícula" dentro de cada `CourseOffering`? → A: No debe haber restricción por periodo de matrícula ni bloqueo por periodos; el administrador puede programar las clases por meses (`starts_on` a `ends_on`) e ir editándolas libremente.
- Q: Dado que no habrá periodos de matrícula y las programaciones (`CourseOffering`) se definen directamente por fechas de inicio y fin a lo largo de meses, ¿qué debe ocurrir con la gestión de "Periodos académicos" creada anteriormente en Programación Académica? → A: Se elimina por completo la entidad y pantalla de Periodos Académicos (`academic_periods`); `CourseOffering` usa únicamente sus propias fechas de inicio y fin (`starts_on` y `ends_on`).
- Q: ¿Cuáles deben ser los estados permitidos para una sesión individual (`Session`) y cómo deben comportarse las sesiones canceladas frente a los cruces de horario? → A: Estados `programada`, `en_curso`, `finalizada` y `cancelada`; los estados `en_curso` y `finalizada` cambian de forma automática cuando se cumplen la fecha/hora de inicio y de finalización (tanto en `Session` como en `CourseOffering` publicada), y las sesiones en estado `cancelada` no generan cruce de horario.
- Q: Cuando se desmatricula a un estudiante de un curso (`CourseOffering`), ¿debe poder volver a matricularse más adelante en la misma u otra programación de ese curso y qué ocurre con su progreso de lecciones? → A: Al desmatricular se libera la vacante y el horario, permitiendo volver a matricularlo después en la misma u otra programación (`CourseOffering`) del curso y conservando su progreso de lecciones.
- Q: En el modal de "Nueva programación académica", ¿cómo se selecciona el curso y cómo se asigna el código de grupo (`group_code`)? → A: El selector de curso utiliza el desplegable con buscador reutilizable (`SearchableSelect` con máximo 5 opciones visibles y scroll vertical) y el código de grupo (`group_code`) se autogenera secuencialmente por curso (`GRP-01`, `GRP-02`, etc.) mostrándose en modo solo lectura.

### Session 2026-11-04

- Q: ¿Cómo debe vincularse cada sesión programada con las lecciones configuradas en el curso seleccionado dentro de "Nueva programación académica"? → A: No debe existir el campo "Título o tema" ni referencia a las lecciones en las sesiones programadas; el docente tiene libertad para avanzar una o más lecciones durante el dictado de sus clases sin acoplar ni romper la programación horaria.
- Q: ¿Cuántos docentes pueden asignarse a un grupo (`CourseOffering`) en la programación académica? → A: Cada grupo (`CourseOffering`) se asigna a **un solo docente** (`teacher_id`), quien es el responsable de todas las sesiones programadas de ese grupo.
- Q: ¿Cómo debe comportarse el calendario de Programación académica al hacer clic en una sesión programada y qué información visual debe distinguir a cada bloque? → A: Al hacer clic sobre una programación en el calendario (`ScheduleCalendar`) se abre el modal de edición de esa programación (`Editar programación académica`); además, cada docente se distingue con un color diferente (sin mostrar una leyenda de docentes fuera del calendario) y cada ítem/bloque dentro del calendario renderiza en líneas separadas y visibles el curso/grupo, el nombre del docente y el aula (o modalidad virtual) sin truncarlos en una sola línea.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Gestionar programaciones académicas (CourseOffering) y sus sesiones individuales (Priority: P1)

El administrador gestiona diferentes programaciones académicas (`CourseOffering`) para un mismo curso, seleccionando el curso mediante el desplegable con buscador (`SearchableSelect`), recibiendo un código de grupo autogenerado secuencialmente por curso (`GRP-01`, `GRP-02`, etc.) y definiendo para cada edición su fecha de inicio y fecha de finalización (pudiendo abarcar varios meses y editarse en cualquier momento), modalidad (presencial, virtual o híbrida), capacidad máxima de estudiantes, estado (borrador, publicada, en curso, finalizada o cancelada) y un único docente asignado al grupo (seleccionado mediante `SearchableSelect`). Dentro de cada programación, crea y edita manualmente cada sesión individual (`Session`) indicando fecha exacta, hora de inicio y fin, aula o enlace de videoconferencia y estado (sin campo de título/tema ni atadura a lecciones del curso, dejando libre al docente cómo desarrollar las lecciones en clase).

**Why this priority**: Proporciona la oferta académica estructurada (grupos, cupos máximos, docente asignado y sesiones con fecha y hora) sobre la cual opera la matrícula.

**Independent Test**: Crear una programación académica (`CourseOffering`) en estado publicada con capacidad máxima de 30 estudiantes, un docente asignado y tres sesiones individuales en fechas y aulas distintas; luego editar una sesión y verificar que la programación y sus sesiones quedan actualizadas y disponibles para matrícula.

**Acceptance Scenarios**:

1. **Given** un curso activo seleccionado en el desplegable con buscador (`SearchableSelect`), **When** se crea o edita una programación académica (`CourseOffering`) con código de grupo autogenerado secuencialmente por curso (`GRP-01`, `GRP-02`, etc.), fechas de inicio/fin, modalidad (presencial, virtual o híbrida), capacidad máxima, estado y un único docente asignado, **Then** la programación se guarda sin bloqueos por periodo y mantiene sincronizados los docentes del curso.
2. **Given** una programación académica con su docente asignado, **When** se registran o editan manualmente sesiones individuales (`Sessions`) indicando fecha exacta, hora de inicio y fin, y aula (si es presencial/híbrida) o enlace de videoconferencia (si es virtual/híbrida), sin pedir título/tema ni lección, **Then** cada sesión queda asociada a la programación y a su docente admitiendo distintos horarios y aulas entre sesiones, validando que no exista traslape del docente o del aula en la misma fecha y hora.
3. **Given** sesiones programadas visibles en el calendario de Programación académica (`ScheduleCalendar`), **When** el usuario visualiza el calendario o hace clic sobre un bloque programado, **Then** cada docente se muestra con un color distintivo, cada ítem/bloque dentro del calendario exhibe en líneas separadas y visibles el horario, el curso/grupo, el docente (`Docente: ...`) y el aula (`Aula: ...` o modalidad virtual) sin truncarse en una sola línea, y al hacer clic sobre el bloque se abre el modal de edición (`Editar programación académica`) de ese grupo.

---

### User Story 2 - Explorar cursos disponibles, ver el modal de programación académica y matricular validando cupos y cruces (Priority: P1)

Durante el proceso de matrícula, el usuario con permiso de matrícula (por defecto el administrador) selecciona al estudiante, visualiza los cursos disponibles y, al hacer clic en un curso, se abre un modal con el detalle de sus programaciones académicas (`CourseOffering`) disponibles (mostrando grupo, modalidad, docentes, sesiones/horarios, fechas/días y cupos disponibles). Al seleccionar una programación para matricular al estudiante, el sistema valida que tenga cupos disponibles según su capacidad máxima y que sus sesiones no tengan cruce de horario en fecha y hora con las sesiones de otras programaciones en las que el estudiante ya esté matriculado.

**Why this priority**: Es el núcleo del flujo de matrícula de estudiantes en clases.

**Independent Test**: Abrir el modal de un curso con dos programaciones disponibles, intentar matricular en una con cruce de horario en alguna sesión o sin vacantes (verificando el rechazo) y luego matricular en una programación válida (verificando el descuento de vacante).

**Acceptance Scenarios**:

1. **Given** el catálogo de cursos en el módulo de matrícula con un estudiante activo seleccionado, **When** se hace clic en un curso disponible, **Then** se abre un modal detallando sus programaciones académicas (`CourseOffering`), sesiones con fechas y horarios, docentes, modalidad y vacantes disponibles.
2. **Given** una programación habilitada para matrícula (`publicada` o `en curso`) con vacantes disponibles y sin cruce de horario en sus sesiones para el estudiante, **When** se confirma la matrícula, **Then** el estudiante queda matriculado en esa programación y las vacantes disponibles disminuyen en uno.
3. **Given** un estudiante matriculado en una programación que tiene una sesión en la misma fecha y franja horaria traslapada con otra programación, **When** se intenta matricularlo en la segunda programación, **Then** el sistema rechaza la matrícula indicando el cruce de horario.
4. **Given** una programación académica que alcanzó su capacidad máxima de estudiantes (`0` vacantes disponibles), **When** se intenta matricular a otro estudiante, **Then** el sistema rechaza la matrícula por falta de cupos.

---

### User Story 3 - Visualizar el horario del estudiante en tiempo real y desmatricular de un curso (Priority: P2)

Mientras se realiza la matrícula del estudiante, se muestra su horario actualizado en tiempo real con las sesiones de todas las programaciones en las que está inscrito. Además, se ofrece la opción de desmatricular al estudiante de algún curso/programación, liberando el cupo inmediatamente y actualizando su horario en tiempo real.

**Why this priority**: Permite armar y ajustar el horario del estudiante con retroalimentación visual inmediata.

**Independent Test**: Matricular al estudiante en una programación y observar que sus sesiones aparecen en tiempo real en el calendario del estudiante; luego desmatricularlo de ese curso y comprobar que las sesiones desaparecen del calendario y la vacante se recupera.

**Acceptance Scenarios**:

1. **Given** la vista de matrícula de un estudiante, **When** se matricula en una programación académica, **Then** las sesiones de dicha programación se reflejan inmediatamente en el horario en tiempo real del estudiante.
2. **Given** un estudiante con al menos un curso matriculado, **When** se ejecuta la opción de desmatricular en ese curso y se confirma la acción, **Then** se cancela/retira la matrícula, se libera la vacante en el `CourseOffering` y el horario en tiempo real del estudiante se actualiza al instante.

---

### Edge Cases

- Programación en estado `borrador`, `finalizada` o `cancelada`: no admite nuevas matrículas.
- Solicitudes concurrentes por la última vacante de un `CourseOffering`: se procesa de forma transaccional para que nunca se supere la capacidad máxima de estudiantes.
- Sesiones consecutivas en la misma fecha (ej. 08:00–10:00 y 10:00–12:00): se permiten sin considerarse cruce de horario.
- Estudiante ya matriculado activamente en otra programación (`CourseOffering`) del mismo curso: se impide la doble matrícula activa en el mismo curso.
- Sesión presencial sin aula o sesión virtual sin enlace de videoconferencia cuando corresponda: se valida según la modalidad.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST unificar y evolucionar el módulo de programación académica (`Scheduling`) hacia `CourseOffering` y eliminar la entidad/pantalla de Periodos Académicos (`academic_periods`), permitiendo gestionar y editar libremente múltiples programaciones académicas para un mismo curso (`Course`) a lo largo de meses usando sus propias fechas (`starts_on` y `ends_on`), conteniendo: curso asociado (seleccionado en el modal mediante el desplegable con buscador `SearchableSelect`), código del grupo autogenerado secuencialmente por curso (`GRP-01`, `GRP-02`, etc., mostrado en modo solo lectura), fecha de inicio, fecha de finalización, capacidad máxima de estudiantes, modalidad (`presencial`, `virtual` o `híbrida`) y estado (`borrador`, `publicada`, `en_curso`, `finalizada` o `cancelada`, donde `en_curso` y `finalizada` se actualizan automáticamente al cumplirse las fechas de inicio y fin).
- **FR-002**: Cada programación académica (`CourseOffering`) MUST asignarse a **un único docente** (`teacher_id`, seleccionado mediante `SearchableSelect`), quien imparte las sesiones de ese grupo, manteniendo sincronizada la relación de docentes asignados al curso en modo lectura en el detalle del curso.
- **FR-003**: Cada programación académica (`CourseOffering`) MUST contener sesiones individuales (`Sessions`) creadas y editadas manualmente una por una, especificando únicamente: programación asociada, fecha exacta, hora de inicio, hora de finalización, docente asignado al grupo, aula (cuando corresponda), enlace de videoconferencia (cuando corresponda) y estado de la sesión (`programada`, `en_curso`, `finalizada` o `cancelada`, donde `en_curso` y `finalizada` transicionan automáticamente al cumplirse la fecha y hora de inicio y fin), sin incluir un campo "Título o tema" ni acoplar la sesión a lecciones específicas del curso (para que el docente gestione libremente el avance de lecciones en sus clases), admitiendo diferentes horarios y aulas entre sesiones y validando que ni el docente ni el aula física tengan traslapes en la misma fecha y hora entre sesiones no canceladas. En el calendario (`ScheduleCalendar`), cada docente MUST representarse con un color diferente (sin mostrar leyenda de docentes sobre el calendario), cada ítem/bloque dentro del calendario MUST renderizar visiblemente en líneas separadas la información del curso/grupo, del docente y del aula (o modalidad virtual) sin truncarlos en una sola línea, y al hacer clic sobre un bloque en Programación académica MUST abrirse el modal de edición de dicha programación.
- **FR-004**: El módulo de matrícula MUST centralizar la inscripción de estudiantes en un `CourseOffering` (dejando las vistas de detalle de curso y estudiante como consulta) y estar protegido por permisos asignables a roles, habilitado por defecto únicamente para el rol Administrador, permitiendo habilitarlo posteriormente a otros roles o a los estudiantes.
- **FR-005**: Durante la matrícula, el sistema MUST mostrar los cursos disponibles y, al hacer clic en un curso, abrir un modal detallando sus programaciones académicas (`CourseOffering`), sesiones con fechas y horarios, docentes, modalidad y cupos disponibles (calculados a partir de la capacidad máxima menos las matrículas activas), incluyendo un botón "Cancelar".
- **FR-006**: Al matricular a un estudiante en una programación académica (`CourseOffering`), el sistema MUST validar de forma transaccional que: (a) el estudiante esté activo, (b) la programación esté en estado disponible para matrícula (`publicada` o `en curso`), (c) existan vacantes disponibles sin exceder la capacidad máxima de estudiantes, (d) el estudiante no tenga otra matrícula activa en el mismo curso, y (e) ninguna sesión activa de la programación tenga cruce de horario (misma fecha y horas traslapadas) con las sesiones de las demás programaciones activas del estudiante.
- **FR-007**: Mientras se matricula al estudiante, el sistema MUST mostrar su horario en tiempo real reflejando las sesiones de todas sus programaciones matriculadas.
- **FR-008**: El sistema MUST permitir desmatricular al estudiante de algún curso/programación previa confirmación (`AlertDialog`), actualizando en tiempo real su horario, liberando inmediatamente la vacante en el `CourseOffering`, conservando el progreso de lecciones del estudiante y permitiendo que pueda volver a matricularse posteriormente en la misma u otra programación del curso.

### Key Entities

- **Curso (`Course`)**: Curso general que ofrece la institución (nombre, descripción, contenido educativo, estado, información general); puede tener múltiples programaciones (`CourseOffering`).
- **Programación Académica (`CourseOffering`)**: Edición o grupo específico de un curso con código/nombre de grupo autogenerado, fecha de inicio, fecha de finalización, capacidad máxima de estudiantes, modalidad (`presencial`, `virtual`, `híbrida`), estado (`borrador`, `publicada`, `en curso`, `finalizada`, `cancelada`) y un único docente asignado.
- **Sesión (`Session` / `CourseSession`)**: Clase individual programada manualmente dentro de un `CourseOffering` (persistida como `CourseSession` / `course_sessions` para no colisionar con las sesiones HTTP) con fecha exacta, hora de inicio, hora de fin, docente del grupo, aula opcional, enlace de videoconferencia opcional y estado de la sesión (sin campo de título/tema ni referencia a lecciones).
- **Matrícula (`Enrollment`)**: Inscripción de un estudiante en un `CourseOffering` específico de un curso, sujeta a validación de vacantes y cruces de horario entre sesiones.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador completa la matrícula de un estudiante en una programación académica (`CourseOffering`) verificando su horario en tiempo real en menos de 1 minuto.
- **SC-002**: El 100% de los intentos de matrícula con cruce de horario entre sesiones o sin vacantes disponibles en el `CourseOffering` son rechazados antes de guardarse.
- **SC-003**: El horario del estudiante en pantalla se actualiza en menos de 1 segundo tras matricular o desmatricular un curso.
- **SC-004**: El 100% de las desmatrículas liberan de inmediato la vacante en el `CourseOffering` correspondiente.

## Assumptions

- No existen periodos de matrícula cerrados ni bloqueos por periodo: el administrador puede programar clases por meses y editar `CourseOffering` y `Sessions` cuando lo requiera.
- Las sesiones consecutivas en una misma fecha donde la hora de fin de una coincide con la hora de inicio de otra no constituyen cruce de horario.
- La visualización del horario en tiempo real utiliza el componente de calendario estandarizado del sistema de diseño (`ScheduleCalendar` / FullCalendar).
