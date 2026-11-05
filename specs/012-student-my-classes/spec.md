# Feature Specification: Módulo de Mis Clases del Estudiante con Restricciones de Fechas y Estado

**Feature Branch**: `012-student-my-classes`

**Created**: 2026-11-04

**Status**: Clarified

**Input**: User description: "para el estudiante en vez de tener http://localhost:8000/my/courses debe tener mis clases, este modulo de cursos no existe para el estudiante. Entonces en el modulo se debe mostrar todas las clases matriculadas el estudiante, se debe poner avisos como 5 cursos proximos para empezar 5 cursos comenzados. Solo se puede acceder al curso si se cumple con la fecha de inicio programada, cuando el curso finaliza el estudiante puede seguir accediendo a los cursos pero ya no puedo interactuar con el curso, es decir no puede marcar su progreso, no puede rendir las evaluaciones. En el modulo de clases se debe filtrar por el estado de las clases, primero mostrar las clases que ya hayan iniciado"

## Clarification Decisions

- **URL y Enrutamiento**: El estudiante accede a `/my/classes`. Si un estudiante intenta ingresar a `/my/courses`, es redirigido automáticamente (302) a `/my/classes`. La vista de docentes se mantiene separada y protegida por permisos y roles.
- **Acceso Previo al Inicio (`starts_on > hoy`)**: La tarjeta de la clase se muestra en Mis Clases pero el botón de acción principal aparece inhabilitado con un badge distintivo "Inicia el DD/MM/AAAA". Si el usuario intenta forzar la URL directa del visor del curso (`/my/courses/{course}` o similar), la política/controlador devuelve un error 403 Forbidden indicando que la clase inicia en la fecha programada.
- **Modo Consulta para Clases Finalizadas (`ends_on < hoy`)**: El visor de contenidos muestra un banner informativo superior: *"Este curso finalizó el DD/MM/AAAA. Modo de consulta"*. Se ocultan/deshabilitan los botones para marcar lección como completada y para iniciar evaluaciones. En la sección de evaluaciones solo se muestra el historial de intentos previos con sus calificaciones. Si se intenta forzar la API (`complete`, `take`, `submit`), el backend rechaza la petición con 403 Forbidden.
- **Diseño de Avisos Superiores**: En la parte superior de `/my/classes`, antes del catálogo principal filtrable, se ubican dos secciones/carruseles de tarjetas compactas:
  1. *Clases comenzadas* (hasta 5 clases en curso con su avance y acceso directo).
  2. *Próximas a empezar* (hasta 5 clases agendadas a futuro ordenadas por cercanía cronológica).

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Reemplazo del Módulo "Mis Cursos" por "Mis Clases" y Visualización Priorizada (Priority: P1)

Como estudiante matriculado en ofertas académicas,  
quiero acceder a la sección "Mis clases" en `/my/classes` (con redirección de `/my/courses` y menú actualizado) para ver todas las clases en las que estoy inscrito con sus datos (código de grupo, horario, modalidad, docente, portada y avance),  
para tener una visión clara de mis periodos de formación ordenada y filtrable según el estado académico.

**Why this priority**: Es la base arquitectónica y visual que reemplaza el concepto abstracto de cursos por el concepto operativo de clases/programaciones (`course_offerings`).

**Independent Test**:
- Iniciar sesión como estudiante.
- Verificar que la navegación no muestra "Mis cursos", sino "Mis clases" apuntando a `/my/classes`.
- Si se navega directamente a `/my/courses`, el sistema redirige a `/my/classes`.
- Al acceder a `/my/classes`, se listan todas las ofertas/clases matriculadas del estudiante.
- El listado permite filtrar por estado (`all`, `in_progress`, `scheduled`, `finished`) y por defecto prioriza visualmente las clases que ya han iniciado (`in_progress`), seguidas de las programadas a empezar y finalizadas.

**Acceptance Scenarios**:
1. **Given** un estudiante con matrículas activas en clases, **When** ingresa al sistema y abre la navegación principal, **Then** visualiza el enlace a "Mis clases" (`/my/classes`) y no existe acceso a "Mis cursos".
2. **Given** un estudiante que navega manualmente a `/my/courses`, **When** se procesa la solicitud, **Then** es redirigido a `/my/classes`.
3. **Given** un estudiante con clases en diferentes estados (en curso, por iniciar, finalizadas), **When** carga la vista de clases, **Then** las clases que ya iniciaron aparecen primero en el orden de visualización.
4. **Given** el selector de estado de la vista, **When** el estudiante elige un estado específico (ej. "En curso", "Próximas a empezar", "Finalizadas"), **Then** el listado se filtra mostrando únicamente las clases coincidentes.

---

### User Story 2 - Resumen de Avisos: Clases Próximas a Empezar y Clases Comenzadas (Priority: P2)

Como estudiante,  
quiero ver dos secciones de tarjetas compactas en la parte superior con hasta 5 clases comenzadas y hasta 5 clases próximas a empezar,  
para enfocarme inmediatamente en las materias que estoy llevando activamente y anticiparme a las fechas de inicio de mis próximas clases.

**Why this priority**: Reduce la fricción operativa y proporciona alertas proactivas al estudiante en cuanto ingresa a su panel de estudio.

**Independent Test**:
- Matricular a un estudiante en 6 clases futuras y 6 clases en curso.
- Cargar la vista `/my/classes`.
- Validar que se muestren las dos secciones compactas superiores con un tope de 5 clases próximas y 5 clases comenzadas con sus respectivas fechas de inicio/fin.

**Acceptance Scenarios**:
1. **Given** un estudiante con clases agendadas a futuro (`starts_on > hoy`), **When** accede a Mis Clases, **Then** visualiza el bloque compacto superior "Próximas a empezar" con hasta 5 elementos ordenados por la fecha de inicio más próxima.
2. **Given** un estudiante con clases que ya iniciaron (`starts_on <= hoy <= ends_on`), **When** accede a Mis Clases, **Then** visualiza el bloque compacto superior "Clases comenzadas / En curso" con hasta 5 elementos activos.

---

### User Story 3 - Bloqueo de Acceso Previo a la Fecha de Inicio (Priority: P1)

Como institución educativa y estudiante,  
quiero que el contenido de un curso solo sea accesible a partir de la fecha de inicio programada (`starts_on`), mostrando el botón inhabilitado en la tarjeta y bloqueando la URL directa,  
para garantizar que el estudiante no acceda de forma prematura a materiales o evaluaciones antes de la apertura oficial de la clase.

**Why this priority**: Integridad pedagógica y cumplimiento estricto del calendario académico.

**Independent Test**:
- Verificar que la tarjeta de una clase futura (`starts_on > hoy`) muestra el botón "Iniciar curso" o "Entrar" deshabilitado con un badge "Inicia el DD/MM/AAAA".
- Intentar acceder directamente por URL al visor del curso de dicha clase futura.
- Verificar que el sistema prohíbe el acceso con un error 403 Forbidden indicando que la clase inicia en la fecha programada.

**Acceptance Scenarios**:
1. **Given** una clase con fecha de inicio posterior a hoy, **When** el estudiante visualiza su tarjeta en `/my/classes`, **Then** el botón de acción está deshabilitado y se muestra un badge informando la fecha de inicio.
2. **Given** una clase con fecha de inicio posterior a hoy, **When** el estudiante intenta forzar la URL directa del visor de lecciones, **Then** el sistema responde con 403 Forbidden denegando el acceso.
3. **Given** una clase cuya fecha de inicio ya fue alcanzada o superada (`starts_on <= hoy`), **When** el estudiante hace clic en entrar/continuar, **Then** el botón está activo y puede acceder al visor normalmente.

---

### User Story 4 - Modo Lectura / Acceso Restringido en Cursos Finalizados (Priority: P2)

Como estudiante que culminó un periodo académico,  
quiero poder seguir revisando los materiales y contenidos de los cursos ya finalizados en modo consulta (sin botones de completar lección ni rendir evaluaciones, y con banner informativo),  
para preservar el registro académico inmutable de la clase culminada sin perder el material de consulta.

**Why this priority**: Resuelve la necesidad de consulta histórica del estudiante protegiendo la inmutabilidad de notas y asistencias tras el cierre de ciclo.

**Independent Test**:
- Configurar una clase con `ends_on < hoy` (estado finalizado).
- Acceder al visor del curso con el estudiante matriculado.
- Comprobar que en la cabecera del visor se renderiza el banner: *"Este curso finalizó el DD/MM/AAAA. Modo de consulta"*.
- Verificar que se ocultan los botones para marcar lección como completada y para rendir evaluaciones.
- Intentar forzar peticiones HTTP `POST /lessons/{lesson}/complete` o `POST /assessments/{assessment}/take`: deben ser rechazadas con 403 Forbidden.

**Acceptance Scenarios**:
1. **Given** una clase finalizada (`ends_on < hoy`), **When** el estudiante accede al visor de contenidos, **Then** se muestra el banner de modo consulta superior y se visualizan los documentos, diapositivas e información del curso.
2. **Given** una clase finalizada, **When** el estudiante visualiza una lección o evaluación, **Then** los botones para completar lección o rendir evaluación no se encuentran disponibles y solo se visualiza el historial de intentos con notas.
3. **Given** una clase finalizada, **When** se envía una solicitud para marcar progreso o tomar evaluación, **Then** el backend responde con 403 Forbidden por haber concluido el curso.

---

### Edge Cases

- **Estudiante matriculado en el mismo curso en dos programaciones distintas**: Si una finalizó y otra está en curso, los permisos de interacción se determinan por la matrícula activa vigente del estudiante.
- **Clase sin fecha de fin definida (`ends_on` nulo)**: Si solo tiene `starts_on`, permanece accesible e interactiva una vez superada la fecha de inicio.
- **Diferencia horaria / Fechas límites**: La validación de fechas debe basarse en la fecha local del sistema (formato `Y-m-d`).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE sustituir en la interfaz de navegación del estudiante el enlace a "Mis cursos" por "Mis clases" (`/my/classes`), y redirigir `/my/courses` a `/my/classes`.
- **FR-002**: La pantalla de Mis Clases DEBE listar las ofertas académicas (`course_offerings`) en las que el estudiante tiene matrícula activa, mostrando: portada, nombre del curso, código de grupo, modalidad, docentes, fechas de inicio y fin, porcentaje de avance y lecciones completadas.
- **FR-003**: La pantalla de Mis Clases DEBE mostrar dos secciones de tarjetas compactas en la parte superior:
  - Hasta 5 clases próximas a empezar (`starts_on > hoy`), ordenadas por cercanía cronológica.
  - Hasta 5 clases comenzadas / en curso (`starts_on <= hoy <= ends_on`).
- **FR-004**: La pantalla DEBE permitir el filtrado por estado de clase (`all`, `in_progress`, `scheduled`, `finished`) y mostrar por defecto primero las clases iniciadas (`in_progress`).
- **FR-005**: Si `starts_on > hoy`, la tarjeta de la clase DEBE mostrar el botón inhabilitado con un badge indicando la fecha de inicio, y la política de acceso al visor DEBE bloquear con 403 Forbidden cualquier acceso directo.
- **FR-006**: Si `ends_on < hoy`, el estudiante PUEDE ver el contenido del curso, pero el visor DEBE mostrar un banner de "Modo de consulta", ocultar los botones de interacción y el backend DEBE prohibir con 403 Forbidden marcar lecciones como completadas y rendir evaluaciones.

### Key Entities

- **Enrollment**: Relaciona al estudiante con el curso y la programación académica (`course_offering_id`).
- **CourseOffering**: Define `group_code`, `starts_on`, `ends_on`, `modality` y el cálculo de su estado efectivo (`published`, `in_progress`, `finished`).
- **LessonProgress**: Registro de lecciones completadas por el alumno, bloqueado para escritura cuando la clase ha finalizado.
- **AssessmentAttempt**: Registro de intentos y evaluaciones del alumno, bloqueado para toma y envío cuando la clase ha finalizado.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% de los accesos a cursos antes de su fecha de inicio (`starts_on`) son bloqueados para el estudiante tanto en UI como en backend.
- **SC-002**: 100% de los intentos de marcar lecciones o rendir evaluaciones en clases finalizadas son rechazados, manteniendo el visor en modo consulta con su banner informativo.
- **SC-003**: La vista de Mis Clases presenta las clases comenzadas en la parte superior y permite alternar filtros con un solo clic.
- **SC-004**: Los bloques de aviso resumen hasta 5 clases comenzadas y hasta 5 próximas a empezar con sus fechas visibles.

## Assumptions

- Se mantiene la compatibilidad de roles: el estudiante accede con su cuenta habitual y rol `estudiante`.
- Si el estudiante cuenta con matrículas históricas directas a cursos sin `course_offering_id`, se asocian a la oferta correspondiente o se consideran con fecha inmediata para no romper datos legados.
