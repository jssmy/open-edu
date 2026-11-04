# Feature Specification: Visor de Lecciones en Modo Diapositivas y Módulo de Clases para Docentes

**Feature Branch**: `006-slide-lesson-viewer`

**Created**: 2026-11-04

**Status**: Clarified

**Input**: User description: "en al vista de alumno cada leccion debe mostrarse como diapositiva, es decir debe haber botone para continuar a la siguiente leccion, el docente tambien tiene la misma vista y solo tiene el modulo de cursos donde puede modificar los cursos solo si le dieron acceso a el"

**Obsidian Source**: [[REQ-016 - Visor de Lecciones en Diapositivas y Restriccion Docente]]

---

## Clarifications & Architectural Decisions

A partir del proceso de clarificación (`/speckit-clarify`), se acordaron las siguientes definiciones clave:

1. **Permisos y Acceso al Módulo Cursos (`/courses`)**:
   - El docente **solo** ve el módulo de Cursos en su navegación si se le ha otorgado explícitamente el permiso (`courses.view` / `courses.manage`).
   - Sin este permiso, no puede ver el menú de Cursos ni realizar ninguna acción sobre la definición general de cursos.
   - Si tiene permiso de Cursos, solo puede editar/modificar los cursos en los que tiene asignación de acceso como docente.

2. **Nuevo Módulo "Clases" para Docentes (`/classes` o `/my/classes`)**:
   - Debe existir un módulo dedicado llamado **"Clases"**.
   - En este módulo, el docente visualiza las clases que tiene programadas/asignadas.
   - Cada clase se representa con una tarjeta (Card) del curso que enseña.
   - Al hacer clic o entrar al detalle de la clase, se abre el visor del curso en formato de **diapositivas**.

3. **Visor de Lecciones en Diapositivas (Común para Alumno y Docente)**:
   - Es la **misma vista interactiva** tanto para el docente (accediendo desde su clase) como para el estudiante (matriculado en el curso).
   - Muestra una única lección por diapositiva activa con botones claros de navegación ("Anterior" y "Siguiente").
   - La navegación es **libre** (no bloquea el avance si la lección no ha sido completada).
   - Dispone de un panel lateral o drawer colapsable con el temario completo de lecciones para salto directo e indicador de progreso.
   - El estudiante cuenta con el botón de "Marcar como completada", mientras que el docente utiliza la vista para proyectar, validar y guiar la clase.

---

## User Scenarios & Testing

### User Story 1 - Visor de Lecciones en Modo Diapositiva (Priority: P1)

Como estudiante matriculado en un curso o docente impartiendo su clase,  
quiero ver cada lección presentada como una diapositiva individual con controles "Anterior" y "Siguiente" y un panel lateral con el temario,  
para que pueda avanzar o saltar fluidamente entre temas con un enfoque visual limpio y sin distracciones.

**Why this priority**: Es el núcleo de la experiencia de enseñanza-aprendizaje tanto en el aula virtual como en la proyección de clase.

**Independent Test**:
Acceder a la ruta del visor (como estudiante matriculado o docente de la clase) y comprobar:
- Se muestra exactamente una diapositiva/lección activa.
- Botones "Anterior" y "Siguiente" permiten transicionar libremente.
- El panel lateral de lecciones permite saltar directamente a cualquier lección.

**Acceptance Scenarios**:
1. **Given** un estudiante matriculado o docente de clase con 6 lecciones en 2 unidades, **When** abre el visor, **Then** visualiza la lección seleccionada en formato diapositiva única, con botones de navegación y el temario lateral disponible.
2. **Given** que el usuario está en una diapositiva intermedia, **When** presiona "Siguiente", **Then** avanza a la lección sucesiva sin recargar la página.
3. **Given** un estudiante en una diapositiva no completada, **When** decide avanzar a la siguiente sin presionar "Marcar como completada", **Then** el sistema permite avanzar libremente (navegación no restrictiva).
4. **Given** un estudiante en cualquier lección, **When** presiona "Marcar como completada", **Then** se actualiza su progreso en tiempo real y la lección refleja el check de completitud en el panel lateral.

---

### User Story 2 - Módulo "Clases" para Docentes (Priority: P1)

Como docente de la institución,  
quiero contar con un módulo llamado "Clases" en mi menú de navegación donde vea tarjetas de las clases y cursos que tengo asignados para enseñar,  
para que pueda ingresar directamente al detalle y abrir la vista de diapositivas de cada curso.

**Why this priority**: Brinda al docente un espacio de trabajo natural enfocado en su labor de enseñanza, separado de la administración del catálogo de cursos.

**Independent Test**:
Iniciar sesión con rol `docente`, verificar la presencia del módulo "Clases" en el menú lateral, ver las tarjetas de cursos que enseña, y acceder al detalle para abrir las diapositivas.

**Acceptance Scenarios**:
1. **Given** un usuario docente con clases asignadas, **When** ingresa a "Clases", **Then** visualiza tarjetas con la información de los cursos que imparte.
2. **Given** un docente en el módulo "Clases", **When** entra al detalle de una clase, **Then** se despliega el visor de lecciones en modo diapositivas con el contenido del curso.

---

### User Story 3 - Restricción de Acceso y Visibilidad del Módulo Cursos (Priority: P2)

Como administrador del sistema,  
quiero que el módulo de "Cursos" solo sea accesible para docentes si cuentan explícitamente con los permisos requeridos (`courses.view` / `courses.manage`), y que aun con permiso solo puedan modificar los cursos que tienen asignados,  
para resguardar el catálogo central y evitar ediciones indebidas.

**Why this priority**: Cumple con la regla de menor privilegio y privacidad institucional.

**Independent Test**:
Verificar que un docente estándar no tiene en su menú el módulo "Cursos" si carece del permiso, y si cuenta con el permiso, que solo pueda editar cursos asignados.

**Acceptance Scenarios**:
1. **Given** un docente sin permiso explícito de gestión o visualización global de cursos, **When** ingresa a la plataforma, **Then** NO visualiza la opción "Cursos" en la barra de navegación lateral.
2. **Given** un docente sin permiso de cursos, **When** intenta ingresar por URL a `/courses`, **Then** recibe una respuesta 403 Forbidden.
3. **Given** un docente con permiso de cursos asignado a Curso A pero no a Curso B, **When** intenta editar Curso B (`/courses/{b}/edit`), **Then** la acción es denegada por `CoursePolicy`.

---

## Edge Cases

- **Docente sin clases programadas**: El módulo "Clases" debe mostrar un empty-state elegante ("No tienes clases programadas actualmente").
- **Clase con curso sin lecciones**: Al abrir el detalle/visor, mostrar un estado indicativo ("Este curso aún no tiene lecciones creadas").
- **Tipos de contenido variados en diapositivas**: Soporte adecuado para texto enriquecido, videos embebidos/subidos, PDFs/documentos e imágenes, manteniendo el marco de la diapositiva sin desbordes.
- **Teclas de acceso rápido**: Flechas izquierda y derecha del teclado pueden alternar opcionalmente las diapositivas anterior y siguiente para mayor comodidad del docente al proyectar.

---

## Requirements

### Functional Requirements

- **FR-001**: El visor de lecciones DEBE mostrar una única lección activa a la vez con transiciones en formato diapositiva.
- **FR-002**: El visor DEBE incluir controles "Anterior" y "Siguiente", permitiendo navegación libre entre lecciones sin obligar a completar previamente la diapositiva.
- **FR-003**: El visor DEBE incluir un panel o drawer lateral colapsable con el árbol de unidades y lecciones para salto directo.
- **FR-004**: El sistema DEBE proveer un módulo de navegación llamado "Clases" para docentes, mostrando las ofertas/clases asignadas con tarjetas de curso.
- **FR-005**: Al entrar al detalle de una clase en el módulo de Clases, el sistema DEBE abrir el visor de diapositivas idéntico al del estudiante.
- **FR-006**: El módulo de "Cursos" (`/courses`) DEBE estar oculto para docentes salvo que se les asigne explícitamente el permiso correspondiente.
- **FR-007**: Si un docente cuenta con permiso para acceder a Cursos, el backend (`CoursePolicy`) DEBE restringir la edición y modificación de contenidos únicamente a los cursos donde esté asignado.

### Key Entities

- **CourseOffering / Class**: Clase programada asignada a un docente y vinculada a un curso base.
- **Course**: Catálogo académico con unidades y lecciones.
- **Lesson**: Elemento individual proyectado como diapositiva en el visor.
- **LessonProgress**: Registro de completitud de cada estudiante por lección.

---

## Success Criteria

### Measurable Outcomes

- **SC-001**: 100% de las lecciones del visor se consumen en formato diapositiva individual interactiva.
- **SC-002**: El docente accede a sus cursos a través del módulo de "Clases" y utiliza la misma interfaz de diapositivas que el alumno.
- **SC-003**: El módulo general de "Cursos" no es visible para docentes sin permisos asignados.
- **SC-004**: Cero posibilidades de modificación de cursos ajenos por parte de docentes sin asignación.
