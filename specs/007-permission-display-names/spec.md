# Feature Specification: Nombres Descriptivos de Permisos en Gestión de Roles

**Feature Branch**: `007-permission-display-names`

**Created**: 2026-11-04

**Status**: Draft

**Input**: User description: "en la gestion de permisos, los permisos deben tener un nombr y esto es lo que hay que mostrar al usuario en la plataforma"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visualización de Nombres Amigables al Crear un Rol (Priority: P1)

Como Administrador del sistema que crea un nuevo rol personalizado, quiero ver cada permiso representado por un nombre claro, comprensible y en lenguaje natural (en lugar de códigos técnicos crudos como `courses.manage` o `scheduling.manage`), para poder seleccionar con precisión los privilegios adecuados sin dudar de sus implicaciones.

**Why this priority**: Es el punto de contacto principal donde se asignan permisos al dar de alta un rol. Mostrar nombres descriptivos previene asignaciones erróneas y facilita la adopción por usuarios no técnicos.

**Independent Test**: Puede probarse de forma independiente ingresando a la vista de creación de roles (`/roles/create`) y verificando que cada casilla o elemento de selección muestre un nombre amigable en español (ej. "Gestionar cursos", "Gestionar usuarios") en lugar del identificador técnico crudo.

**Acceptance Scenarios**:

1. **Given** un administrador en el formulario de creación de rol (`/roles/create`), **When** visualiza el catálogo de permisos disponibles para asignar, **Then** cada permiso se muestra con su nombre amigable y descriptivo en lenguaje natural.
2. **Given** un formulario con permisos listados por nombre amigable, **When** el administrador marca uno o varios permisos y envía el formulario, **Then** el rol se crea exitosamente conservando la asociación funcional correcta con los permisos seleccionados.

---

### User Story 2 - Visualización y Edición de Permisos en Roles Existentes (Priority: P2)

Como Administrador del sistema que administra roles en el listado general (`/roles`), quiero que al abrir el modal de permisos o ver los detalles de un rol se listen los nombres descriptivos de los permisos asignados y disponibles, para auditar y actualizar de forma rápida y comprensible la seguridad de cualquier rol.

**Why this priority**: Permite la revisión continua y modificación de permisos de roles ya existentes, garantizando consistencia en toda la experiencia de usuario.

**Independent Test**: Puede probarse abriendo el modal de asignación de permisos de cualquier rol en la tabla de roles y verificando que tanto la lista de selección como las etiquetas de visualización usen los nombres amigables.

**Acceptance Scenarios**:

1. **Given** la lista de roles en la plataforma, **When** el administrador abre el modal para editar los permisos de un rol, **Then** la lista de opciones presenta los nombres amigables de cada permiso con su estado (marcado/desmarcado).
2. **Given** un rol con permisos asignados, **When** el administrador guarda cambios en la selección de permisos mostrados con sus nombres amigables, **Then** los permisos se actualizan correctamente en el sistema y se reflejan al reabrir el modal.

---

### User Story 3 - Categorización y Claridad Contextual de Permisos (Priority: P3)

Como Administrador del sistema que gestiona una lista creciente de permisos, quiero que los permisos con sus nombres amigables estén ordenados o agrupados por módulo temático (ej. Cursos, Usuarios, Programación, Evaluaciones), para localizarlos rápidamente sin saturación cognitiva.

**Why this priority**: A medida que el sistema escala con más módulos, la organización agrupada de nombres descriptivos mejora la usabilidad y legibilidad.

**Independent Test**: Puede probarse verificando que los permisos aparezcan ordenados alfabéticamente o agrupados por módulo funcional en las interfaces de selección.

**Acceptance Scenarios**:

1. **Given** el catálogo de permisos en la interfaz de usuario, **When** el administrador consulta los permisos disponibles, **Then** los permisos se presentan organizados por categoría o módulo funcional junto con su nombre descriptivo.

---

### Edge Cases

- ¿Qué ocurre si se agrega un nuevo permiso al sistema sin haberle asignado aún un nombre descriptivo explícito? El sistema debe contar con un mecanismo de respaldo (fallback) que formatee amigablemente el identificador (ej. reemplazando puntos y guiones por espacios y aplicando capitalización) para nunca mostrar un código crudo o cadena vacía.
- ¿Qué ocurre si dos permisos tienen nombres descriptivos similares? El sistema debe garantizar nombres únicos o incluir el módulo de contexto para evitar confusiones al usuario.
- ¿Qué ocurre si un usuario o administrador intenta inspeccionar las peticiones de red? La interfaz envía las claves funcionales internas requeridas por el backend, manteniendo total retrocompatibilidad con las políticas y directivas de seguridad existentes.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Cada permiso del sistema DEBE poseer un nombre descriptivo (display name) legible en lenguaje natural para el usuario humano.
- **FR-002**: Las interfaces de gestión de roles (creación de rol `/roles/create` y modal de edición de permisos en `/roles`) DEBEN mostrar prominentemente el nombre descriptivo de cada permiso en lugar de su clave técnica cruda.
- **FR-003**: El sistema DEBE mantener un mecanismo de fallback que genere un nombre legible por defecto en caso de que un permiso carezca de nombre descriptivo personalizado.
- **FR-004**: Los permisos en la interfaz de asignación DEBEN poder mostrarse organizados lógicamente por módulo o categoría (ej. Usuarios, Roles, Cursos, Contenido, Programación, etc.).
- **FR-005**: La persistencia y sincronización de permisos DEBE mantener intacta la compatibilidad con los identificadores técnicos internos utilizados por el motor de autorización y políticas de la plataforma.
- **FR-006**: La visualización de roles y sus permisos DEBE permitir al administrador identificar con claridad qué permisos específicos tiene otorgados cada rol sin ambigüedades terminológicas.

### Key Entities *(include if feature involves data)*

- **Permission (Permiso)**: Entidad que representa una capacidad o privilegio de seguridad en el sistema. Posee un identificador interno (clave técnica única del sistema), un nombre descriptivo visible para el usuario (display name), una descripción opcional y un módulo o grupo funcional asociado.
- **Role (Rol)**: Conjunto de permisos asignados a un perfil institucional (Administrador, Docente, Gestor, etc.) cuyos permisos asignados se visualizan mediante sus nombres legibles.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los permisos disponibles en la interfaz de gestión de roles se muestran con nombres legibles y comprensibles en español.
- **SC-002**: Cero apariciones de identificadores técnicos crudos (como `courses.manage`, `scheduling.manage`, etc.) como texto principal visible para el usuario final en las pantallas de roles.
- **SC-003**: Los administradores pueden identificar y seleccionar los permisos requeridos para un rol en menos de 1 minuto sin necesidad de recurrir a soporte técnico ni documentación técnica externa.
- **SC-004**: Total retrocompatibilidad: las autorizaciones de usuario, gates y validaciones de backend continúan operando con 100% de éxito sin alteraciones en la lógica de seguridad existente.

## Assumptions

- Los identificadores técnicos existentes (`users.manage`, `courses.manage`, `scheduling.manage`, etc.) permanecen invariables en el backend para preservar la estabilidad de las directivas de autorización.
- El idioma principal para la visualización de los nombres descriptivos es el español, en concordancia con el resto de la interfaz de Open-Edu.
- La funcionalidad no altera la lógica de bloqueo o inactivación de roles establecida en `REQ-015`.
- Se reutilizan los componentes visuales existentes del sistema de diseño (Checkboxes, Cards, Modales) para renderizar los nombres de los permisos.

