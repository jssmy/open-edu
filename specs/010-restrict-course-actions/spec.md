# Feature Specification: Restricción de Acciones de Publicación y Eliminación de Cursos para Docentes

**Feature Branch**: `010-restrict-course-actions`

**Created**: 2026-11-04

**Status**: Ready

**Input**: User description: "el docente a pesar de no tener permiso para crear el cotntenido del curso sigue podiendo eliminar y cambiar el stado del curso en http://localhost:8000/courses"

**Related Requirements**: [[Requirements/REQ-020 - Restriccion de Acciones de Publicacion y Eliminacion de Cursos para Docentes]] y [[Requirements/REQ-016 - Visor de Lecciones en Diapositivas y Restriccion Docente]]

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Restringir controles administrativos de curso (Publicación y Eliminación) para docentes (Priority: P1)

Como docente asignado que visualiza un curso en el módulo de cursos (`/courses/{id}`), no debo tener acceso a cambiar el estado de publicación del curso ni a eliminar el curso, manteniendo la interfaz libre de acciones y controles administrativos que no corresponden a mi rol y permisos.

**Why this priority**: Es la vulnerabilidad funcional y de interfaz reportada: en `CourseController::show`, la bandera `canManageCourses` se computaba erróneamente en `true` si el docente estaba asignado al curso (`$request->user()->can('courses.manage') || ($request->user()->hasRole('docente') && $course->teachers()->whereKey($request->user()->id)->exists())`), lo que otorgaba privilegios de interfaz para publicar/desactivar y borrar el curso a cualquier docente asignado, independientemente de sus permisos institucionales o de si carece de permisos de contenido (`content.manage`).

**Independent Test**:
1. Iniciar sesión con un usuario docente asignado al curso pero sin permiso `courses.manage`.
2. Visitar `/courses/{id}`.
3. Verificar que la sección lateral "Publicación" (cambiar estado) NO se renderice.
4. Verificar que la sección "Zona de peligro" (botón "Eliminar curso") NO se renderice.
5. Iniciar sesión con un usuario administrador con permiso `courses.manage`, visitar `/courses/{id}` y verificar que ambas secciones sigan estando visibles y plenamente funcionales.

**Acceptance Scenarios**:

1. **Given** un usuario docente asignado a un curso pero sin el permiso global `courses.manage`, **When** accede a `/courses/{id}`, **Then** la prop `canManageCourses` es `false`, el panel lateral no muestra el selector de estado de publicación ni el botón de guardar estado, y el estado actual del curso se muestra únicamente como lectura (Badge en la cabecera).
2. **Given** un usuario docente asignado a un curso pero sin el permiso `courses.manage`, **When** accede a `/courses/{id}`, **Then** el panel lateral no muestra la sección de "Zona de peligro" ni el botón para eliminar el curso.
3. **Given** un usuario con permiso `courses.manage`, **When** accede a `/courses/{id}`, **Then** `canManageCourses` es `true` y visualiza tanto la sección de "Publicación" como la "Zona de peligro".

---

### User Story 2 - Blindaje de Endpoints Backend contra Modificaciones No Autorizadas (Priority: P1)

Como administrador de la plataforma, requiero que las rutas de backend para eliminar un curso (`DELETE /courses/{id}`) y modificar su estado (`PATCH /courses/{id}/status`) rechacen con código de error HTTP 403 Forbidden a cualquier usuario que no posea explícitamente el permiso `courses.manage`, evitando mutaciones no autorizadas vía API o peticiones HTTP directas.

**Why this priority**: Previene brechas de seguridad y asegura el principio de menor privilegio en el backend para salvaguardar la integridad de los datos académicos.

**Independent Test**:
1. Ejecutar peticiones HTTP autenticado como docente hacia `PATCH /courses/{id}/status` y `DELETE /courses/{id}`.
2. Comprobar que ambas responden taxativamente con HTTP 403 Forbidden y ningún cambio es persistido en la base de datos.

**Acceptance Scenarios**:

1. **Given** un docente autenticado sin permiso `courses.manage`, **When** envía una solicitud `PATCH /courses/{id}/status`, **Then** el servidor responde con 403 Forbidden y el estado del curso no cambia.
2. **Given** un docente autenticado sin permiso `courses.manage`, **When** envía una solicitud `DELETE /courses/{id}`, **Then** el servidor responde con 403 Forbidden y el curso no es eliminado.

---

### Edge Cases

- **Docente sin permiso de contenido (`content.manage`)**:
  - Si un docente no tiene `content.manage`, tampoco debe poder agregar/editar unidades o lecciones (`canManageContent` es `false`).
- **Docente que posee extraordinariamente `courses.manage`**:
  - Si a un usuario docente se le otorga explícitamente el permiso `courses.manage` mediante la matriz de roles/permisos, podrá acceder a los controles administrativos de publicación y eliminación.
- **Acceso a la vista de cursos (`/courses`)**:
  - En el listado general (`/courses`), el botón "Nuevo curso" debe seguir gobernado por `canManageCourses`, permaneciendo oculto para los docentes estándar.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El controlador `CourseController::show` DEBE evaluar la propiedad `canManageCourses` estrictamente mediante `$request->user()->can('courses.manage')`, eliminando la condición que otorgaba dicho permiso por el simple hecho de ser docente asignado (`$request->user()->hasRole('docente') && $course->teachers()->whereKey(...)`).
- **FR-002**: En la vista `resources/js/pages/Courses/Show.vue`, las secciones de "Publicación" y "Zona de peligro" DEBEN depender exclusivamente de `canManageCourses === true`.
- **FR-003**: Las rutas `courses.status` (`PATCH /courses/{course}/status`) y `courses.destroy` (`DELETE /courses/{course}`) DEBEN permanecer blindadas bajo el middleware `can:courses.manage`.
- **FR-004**: Los permisos de gestión de contenido (`canManageContent`) DEBEN mantenerse respetando las políticas de edición para docentes asignados en unidades y lecciones.

### Key Entities

- **Course**: Curso académico con nombre, descripción, estado (`draft`, `published`, `deactivated`), unidades, lecciones y docentes asignados.
- **User (Docente)**: Usuario con rol docente, vinculado como profesor en ofertas o cursos específicos, sin atribuciones administrativas globales.

---

## Clarifications & Confirmations

- **Visualización de Estado para Docentes**: El docente visualiza el estado actual del curso de forma exclusivamente informativa mediante el Badge en la cabecera (Borrador/Publicado/Desactivado). Se ocultan el selector interactivo y el botón de actualización en el panel lateral.
- **Alcance de Gestión del Docente**: El docente asignado mantiene toda la gestión didáctica y académica correspondiente a su curso (edición de unidades, lecciones y evaluaciones según sus permisos de contenido `content.manage` y edición de detalles autorizados), restringiendo de manera estricta y única las acciones globales de cambiar el estado institucional y destruir/eliminar el curso.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 0 docentes asignados sin permiso administrativo pueden ver u operar el selector de estado de publicación ni el botón de eliminar curso en `/courses/{id}`.
- **SC-002**: 100% de las pruebas automatizadas (Pest en backend y Vitest en frontend) pasando satisfactoriamente sin regresiones.
- **SC-003**: Cumplimiento estricto con los criterios de aceptación de [[Requirements/REQ-020 - Restriccion de Acciones de Publicacion y Eliminacion de Cursos para Docentes]].
