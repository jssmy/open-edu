# Feature Specification: Sistema de Gestión de Aprendizaje (LMS)

**Feature Branch**: `001-lms-learning-platform`

**Created**: 2026-11-02

**Status**: Draft

**Input**: User description: "Plataforma web LMS para administrar cursos, estudiantes, contenidos educativos, evaluaciones y seguimiento del progreso académico, para organizaciones que gestionan sus propios cursos y estudiantes de forma centralizada."

## Clarifications

### Session 2026-11-02

- Q: ¿Cuántos intentos puede tener un estudiante en cada evaluación? → A: Configurable por evaluación (por defecto 1); el resultado oficial es el del último intento.
- Q: ¿Qué acceso tiene el estudiante con inscripción suspendida o cancelada? → A: Suspendida: sin acceso a contenido ni evaluaciones. Cancelada: sin acceso a contenido ni evaluaciones, pero puede consultar su historial de progreso y resultados.
- Q: ¿Los Gestores y Docentes ven todos los cursos y estudiantes o solo los asignados? → A: Gestor: todos los cursos y estudiantes. Docente: solo sus cursos asignados y los estudiantes inscritos en ellos.
- Q: ¿Cómo se crean las cuentas de estudiantes y cómo reciben su primer acceso? → A: Un usuario autorizado las crea y asigna una contraseña temporal que el estudiante MUST cambiar en su primer acceso; no hay auto-registro.
- Q: ¿Qué pasa con una lección que tiene progreso registrado cuando un docente quiere eliminarla? → A: Solo se puede archivar (no eliminar); el avance histórico se conserva.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Acceso seguro y gestión de usuarios, roles y permisos (Priority: P1)

Cualquier persona con cuenta inicia y cierra sesión, y recupera su contraseña si la olvida. El
Administrador crea y gestiona usuarios, asigna uno de los roles (Administrador, Gestor de cursos,
Docente, Estudiante) y controla los permisos de cada rol.

**Why this priority**: Sin identidad y control de permisos ningún otro módulo puede operar de forma segura.

**Independent Test**: Un Administrador crea un usuario con un rol, este inicia sesión y solo ve las
operaciones permitidas para su rol; luego recupera su contraseña.

**Acceptance Scenarios**:

1. **Given** un usuario activo con credenciales válidas, **When** inicia sesión, **Then** accede al sistema con las opciones de su rol.
2. **Given** credenciales inválidas, **When** intenta iniciar sesión, **Then** recibe un mensaje claro sin revelar cuál dato falló.
3. **Given** un usuario que olvidó su contraseña, **When** solicita recuperarla y completa el proceso, **Then** puede iniciar sesión con la nueva contraseña.
4. **Given** un usuario sin permiso para una operación, **When** intenta realizarla, **Then** el sistema la rechaza.
5. **Given** un usuario desactivado, **When** intenta iniciar sesión, **Then** se le deniega el acceso.

---

### User Story 2 - Gestión de cursos y estructura de contenidos (Priority: P1)

Un Gestor de cursos (o Administrador) crea, edita, consulta y elimina cursos con nombre, descripción
y objetivos, los publica o desactiva, y organiza el contenido en unidades o secciones con lecciones.
El Docente administra los contenidos de los cursos que tiene asignados.

**Why this priority**: Los cursos y sus contenidos son el núcleo de valor del LMS.

**Independent Test**: Crear un curso con unidades y lecciones (texto, documento, imagen, enlace, video), publicarlo y verificarlo.

**Acceptance Scenarios**:

1. **Given** un usuario autorizado, **When** crea un curso con los datos obligatorios, **Then** el curso queda registrado en estado no publicado.
2. **Given** un curso con unidades y lecciones, **When** el usuario reordena unidades o lecciones, **Then** el nuevo orden se conserva.
3. **Given** un curso no publicado, **When** se publica, **Then** queda disponible para estudiantes inscritos.
4. **Given** un curso publicado, **When** se desactiva, **Then** los estudiantes dejan de acceder a su contenido y evaluaciones.
5. **Given** un docente, **When** intenta editar un curso que no tiene asignado, **Then** la operación es rechazada.
6. **Given** un curso con inscripciones, **When** un usuario intenta eliminarlo, **Then** el sistema pide confirmación y advierte del impacto sobre inscripciones y resultados.

---

### User Story 3 - Gestión de estudiantes e inscripción a cursos (Priority: P1)

Un usuario autorizado registra y administra estudiantes (consultar, editar, activar, desactivar) y los
inscribe directamente en cursos. Puede consultar los estudiantes de cada curso, los cursos de cada
estudiante y gestionar el estado de cada inscripción.

**Why this priority**: Sin estudiantes inscritos no hay consumo de contenido ni seguimiento.

**Independent Test**: Registrar un estudiante, inscribirlo en dos cursos y verificar ambas vistas (por curso y por estudiante).

**Acceptance Scenarios**:

1. **Given** datos válidos, **When** se registra un estudiante, **Then** queda creado y activo.
2. **Given** un estudiante y un curso, **When** un usuario autorizado lo inscribe, **Then** la inscripción queda activa y visible en ambas consultas.
3. **Given** un estudiante ya inscrito en un curso, **When** se intenta inscribirlo de nuevo, **Then** el sistema lo impide y lo informa.
4. **Given** una inscripción, **When** su estado cambia (p. ej. activa, suspendida, cancelada), **Then** el acceso del estudiante se ajusta al nuevo estado.
5. **Given** un estudiante desactivado, **When** intenta acceder, **Then** se le deniega el acceso y su historial se conserva.

---

### User Story 4 - El estudiante estudia el contenido de sus cursos (Priority: P2)

El Estudiante ve sus cursos, navega unidades y lecciones, consume los contenidos y marca o registra
lecciones completadas. Solo accede a cursos en los que está inscrito (inscripción activa) y que están publicados.

**Why this priority**: Es la experiencia central del estudiante y alimenta el seguimiento.

**Independent Test**: Un estudiante inscrito en un curso publicado recorre una lección y esta queda completada en su progreso.

**Acceptance Scenarios**:

1. **Given** un estudiante inscrito en un curso publicado, **When** abre el curso, **Then** ve las unidades y lecciones.
2. **Given** un estudiante no inscrito, **When** intenta acceder a un curso, **Then** se le deniega.
3. **Given** una lección abierta, **When** la completa, **Then** queda registrada como completada y el progreso se actualiza.

---

### User Story 5 - Evaluaciones (Priority: P2)

El Docente crea evaluaciones asociadas a un curso con preguntas y alternativas, define puntaje por
pregunta y criterio de aprobación. El Estudiante inscrito las desarrolla y obtiene su resultado, que
queda registrado y consultable.

**Why this priority**: Permite medir el aprendizaje, pero depende de cursos e inscripciones.

**Independent Test**: Crear una evaluación de 3 preguntas, resolverla como estudiante y verificar puntaje y aprobado/reprobado.

**Acceptance Scenarios**:

1. **Given** un docente asignado al curso, **When** crea una evaluación con preguntas, alternativas, respuesta correcta, puntajes y nota mínima, **Then** queda guardada.
2. **Given** una evaluación disponible, **When** el estudiante inscrito la envía, **Then** se calcula el puntaje y se indica si aprobó según el criterio.
3. **Given** una evaluación enviada, **When** se consulta el resultado, **Then** estudiante y personal autorizado ven puntaje, estado y fecha.
4. **Given** un curso desactivado o una inscripción no activa, **When** el estudiante intenta rendir la evaluación, **Then** se le deniega.
5. **Given** una evaluación sin preguntas válidas (sin alternativa correcta), **When** se intenta publicar, **Then** el sistema lo impide.

---

### User Story 6 - Seguimiento del aprendizaje y reportes (Priority: P3)

Docentes, Gestores y Administradores consultan el avance por curso y por estudiante (lecciones
completadas y pendientes, resultados de evaluaciones), y generan reportes de estudiantes inscritos,
avance académico y resultados. El Estudiante consulta solo su propio progreso.

**Why this priority**: Aporta visibilidad de gestión sobre lo anterior.

**Independent Test**: Con datos de inscripción, progreso y resultados, generar cada reporte y comprobar que coincide con los datos registrados y respeta permisos.

**Acceptance Scenarios**:

1. **Given** un curso con estudiantes, **When** un usuario autorizado consulta el progreso, **Then** ve por estudiante lecciones completadas, pendientes y porcentaje de avance.
2. **Given** un docente, **When** consulta reportes, **Then** solo ve los cursos que tiene asignados.
3. **Given** un estudiante, **When** consulta su progreso, **Then** ve únicamente sus propios datos.
4. **Given** filtros (curso, estudiante, estado), **When** se genera un reporte, **Then** el resultado respeta los filtros y puede exportarse.

---

### Edge Cases

- Un curso se desactiva con estudiantes a mitad de una evaluación: la evaluación en curso se maneja según una regla definida (ver Assumptions).
- Se elimina o modifica contenido/evaluación de un curso con progreso o resultados ya registrados: el historial MUST conservarse o advertirse.
- Estudiante desactivado con inscripciones activas.
- Cambio de rol de un usuario con sesión abierta.
- Intento de inscribir a un estudiante en un curso no publicado.
- Un docente es desasignado de un curso con evaluaciones creadas.
- Recuperación de contraseña con enlace vencido o ya usado.
- Reporte sin datos: se muestra un estado vacío claro.
- Evaluación con puntajes que no suman el total esperado o criterio de aprobación fuera de rango.
- Contenido en formato o tamaño no admitido.

## Requirements *(mandatory)*

### Functional Requirements

**Autenticación y usuarios**
- **FR-001**: El sistema MUST permitir iniciar y cerrar sesión a usuarios activos.
- **FR-002**: El sistema MUST permitir recuperar la contraseña mediante un proceso de restablecimiento con vigencia limitada.
- **FR-003**: Los Administradores MUST poder crear, editar, consultar, activar y desactivar usuarios.
- **FR-004**: El sistema MUST soportar los roles Administrador, Gestor de cursos, Docente y Estudiante, y permitir al Administrador gestionar los permisos asociados a cada rol.
- **FR-005**: El sistema MUST validar el permiso del usuario en cada operación y denegar las no autorizadas, independientemente de lo que muestre la interfaz.

**Cursos**
- **FR-006**: Usuarios autorizados MUST poder crear, editar, consultar y eliminar cursos con nombre, descripción, objetivos y otros datos descriptivos.
- **FR-007**: Un curso MUST poder publicarse y desactivarse; solo los cursos publicados son accesibles para estudiantes.
- **FR-008**: El sistema MUST permitir asignar docentes a cursos; un docente solo ve y gestiona los cursos asignados y los estudiantes inscritos en ellos. El Gestor de cursos ve y gestiona todos los cursos y estudiantes según sus permisos.
- **FR-009**: Los cursos MUST organizarse en unidades o secciones que contienen lecciones ordenables. Una lección con progreso registrado MUST poder solo archivarse (no eliminarse); el avance histórico se conserva y la lección archivada deja de mostrarse a los estudiantes y de contar para el porcentaje de avance.
- **FR-010**: Al eliminar un curso con inscripciones o resultados, el sistema MUST exigir confirmación y realizar una eliminación lógica que conserve el historial (inscripciones, progreso y resultados) y oculte el curso de los listados.

**Contenidos**
- **FR-011**: Usuarios autorizados MUST poder crear unidades y lecciones y asociar contenidos de tipo texto, documento, imagen, enlace y video.
- **FR-012**: Los estudiantes con inscripción activa en un curso publicado MUST poder acceder a su contenido.

**Estudiantes e inscripciones**
- **FR-013**: Usuarios autorizados MUST poder registrar, consultar, editar, activar y desactivar estudiantes. Al registrar un estudiante, el usuario autorizado asigna una contraseña temporal y el sistema MUST obligar al estudiante a cambiarla en su primer acceso.
- **FR-014**: Usuarios autorizados MUST poder inscribir directamente estudiantes en cursos; no existen procesos institucionales de matrícula ni períodos académicos.
- **FR-015**: Un estudiante MUST poder estar inscrito en varios cursos y un curso MUST poder tener varios estudiantes.
- **FR-016**: El sistema MUST impedir inscripciones duplicadas del mismo estudiante en el mismo curso.
- **FR-017**: El sistema MUST permitir consultar los estudiantes de un curso y los cursos de un estudiante.
- **FR-018**: Cada inscripción MUST tener un estado gestionable (como mínimo: activa, suspendida, cancelada). Solo la inscripción activa da acceso a contenido y evaluaciones; con inscripción suspendida el estudiante no accede a nada del curso; con inscripción cancelada no accede a contenido ni evaluaciones, pero puede consultar su historial de progreso y resultados.

**Evaluaciones**
- **FR-019**: Docentes y usuarios autorizados MUST poder crear evaluaciones asociadas a un curso, con preguntas, alternativas y respuesta correcta.
- **FR-020**: El sistema MUST permitir configurar el puntaje de cada pregunta y el criterio de aprobación. El puntaje total MUST ser la suma de los puntajes de las preguntas y la nota mínima de aprobación MUST estar entre 0 y el puntaje total.
- **FR-021**: Los estudiantes con acceso MUST poder desarrollar y enviar evaluaciones, hasta el número de intentos configurado para cada evaluación (por defecto 1); al agotarlos, el sistema MUST impedir nuevos intentos.
- **FR-022**: El sistema MUST calcular y registrar el resultado (puntaje, aprobado/reprobado, fecha) de cada intento y permitir su consulta; el resultado oficial para progreso y reportes es el del último intento, conservando el historial de intentos.
- **FR-023**: El acceso a evaluaciones MUST depender de una inscripción activa y de que el curso esté publicado.

**Seguimiento y reportes**
- **FR-024**: El sistema MUST registrar el avance del estudiante por lección (completada/pendiente) y calcular el progreso por curso.
- **FR-025**: Usuarios autorizados MUST poder consultar progreso y resultados por curso y por estudiante; el estudiante solo los propios.
- **FR-026**: El sistema MUST ofrecer reportes de estudiantes inscritos, avance académico y resultados de evaluaciones, con filtros y posibilidad de exportación.
- **FR-027**: Los reportes MUST respetar los permisos y el alcance del usuario (un docente solo ve sus cursos asignados; un gestor y un administrador ven todos).

**Transversales**
- **FR-028**: El sistema MUST mostrar mensajes claros de éxito y error, y estados de carga y vacíos.
- **FR-029**: El sistema MUST registrar los eventos relevantes de seguridad y administración (inicios de sesión, cambios de rol, inscripciones).

### Key Entities

- **Usuario**: persona con acceso al sistema; tiene un rol, estado (activo/inactivo) y credenciales.
- **Rol / Permiso**: rol con un conjunto de permisos que define las operaciones permitidas.
- **Estudiante**: usuario con rol Estudiante y datos personales/de contacto; activo o inactivo.
- **Curso**: unidad formativa con nombre, descripción, objetivos y estado (no publicado, publicado, desactivado); tiene docentes asignados.
- **Unidad/Sección**: agrupación ordenada de lecciones dentro de un curso.
- **Lección**: pieza de contenido ordenada (texto, documento, imagen, enlace o video).
- **Inscripción**: relación entre un estudiante y un curso con un estado.
- **Evaluación**: conjunto de preguntas de un curso con puntaje total y criterio de aprobación.
- **Pregunta / Alternativa**: enunciado con puntaje y opciones, de las cuales se marca la correcta.
- **Resultado de evaluación**: intento de un estudiante con respuestas, puntaje, estado y fecha; una evaluación define su número máximo de intentos.
- **Progreso**: registro de lecciones completadas por estudiante y curso.
- **Reporte**: consulta filtrable y exportable de inscripciones, avance o resultados.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un usuario autorizado puede crear un curso con una unidad y una lección en menos de 5 minutos.
- **SC-002**: Un usuario autorizado puede inscribir a un estudiante en un curso en menos de 1 minuto.
- **SC-003**: El 100% de los intentos de operaciones no permitidas para un rol son rechazados.
- **SC-004**: En una prueba de usabilidad con 5 usuarios estudiantes, al menos 4 (80%) localizan y abren una lección de su curso en su primer intento, sin ayuda.
- **SC-005**: El resultado de una evaluación se muestra al estudiante en menos de 5 segundos tras enviarla, y el puntaje coincide al 100% con las reglas configuradas.
- **SC-006**: El progreso y los reportes reflejan una lección completada o un resultado nuevo en menos de 1 minuto.
- **SC-007**: Un reporte de seguimiento de un curso de hasta 500 estudiantes se genera en menos de 30 segundos.
- **SC-008**: Con 500 usuarios concurrentes, el 95% de las páginas responde en menos de 2 segundos.
- **SC-009**: Ningún estudiante accede a contenido o evaluaciones de cursos en los que no tiene inscripción activa o que no están publicados.

## Glosario

- **Gestor de cursos**: rol que administra cursos, contenidos e inscripciones (identificador de rol: `gestor_de_cursos`). En tablas y contratos puede abreviarse "Gestor".
- **Docente**: rol asignado a cursos concretos. **Estudiante**: usuario con rol Estudiante.

## Assumptions

- Es una única organización (instalación) que gestiona sus propios cursos y estudiantes; no es multi-organización.
- Los usuarios son creados por usuarios autorizados con contraseña temporal de cambio obligatorio; no hay auto-registro público.
- La plataforma se usa desde navegadores web de escritorio y móviles (diseño responsive).
- Los estados de inscripción mínimos son activa, suspendida y cancelada; pueden ampliarse.
- Las evaluaciones se califican automáticamente (preguntas de alternativas).
- Una evaluación en curso al desactivarse el curso puede enviarse y su resultado se conserva, pero no se permiten nuevos intentos.
- El historial (progreso, resultados) se conserva al desactivar estudiantes o cursos.
- Los reportes se exportan en formatos de uso común (por ejemplo, CSV o PDF).
- Los videos se incorporan mediante enlace o archivo subido dentro de límites razonables de tamaño.
- Fuera de alcance: períodos académicos, matrícula institucional, pagos, certificados, foros y mensajería, y calificación manual de preguntas abiertas.
- Restricciones técnicas (monolito Laravel + Vue.js, PostgreSQL) se definen en la constitución y el plan, no en esta especificación.
