# Feature Specification: Gestión de Roles (crear e inactivar)

**Feature Branch**: `005-role-management`

**Created**: 2026-11-04

**Status**: Draft

**Input**: User description: "en la gestion de roles, debe permitir generar nuevos roles, inactivar, los usuarios con roles inactivos no pueden iniciar sesion"

## Clarifications

### Session 2026-11-04

- Q: ¿Qué roles deben quedar protegidos y no poder inactivarse nunca? → A: Solo Administrador; Gestor de cursos, Docente, Estudiante y roles nuevos son inactivables.
- Q: ¿Qué pasa con los usuarios al inactivar un rol con usuarios asignados? → A: Se permite tras confirmación que muestra cuántos usuarios quedarán bloqueados; conservan el rol.
- Q: ¿Qué pueden hacer los roles nuevos? → A: Solo tienen los permisos asignados sobre funciones existentes; sin pantallas ni flujos propios.
- Q: ¿Cómo se comunica el bloqueo por rol inactivo? → A: En el login se muestra el mensaje genérico de credenciales inválidas (sin revelar el estado del rol); las sesiones abiertas continúan hasta expirar.
- Q: ¿Se puede crear un rol con el nombre de uno inactivo? → A: No; el nombre es único aunque el rol esté inactivo y se indica reactivar el existente.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Crear un nuevo rol (Priority: P1)

El administrador crea un rol nuevo indicando nombre y descripción, y le asigna permisos desde la gestión de roles existente. El rol queda activo y disponible para asignarlo a usuarios.

**Why this priority**: Sin poder crear roles, la gestión queda limitada a los roles predefinidos.

**Independent Test**: Crear un rol "Coordinador", asignarlo a un usuario y verificar que aparece en el listado y en la asignación de usuarios.

**Acceptance Scenarios**:

1. **Given** un administrador autenticado, **When** completa nombre y descripción válidos y guarda, **Then** el rol aparece en el listado con estado Activo.
2. **Given** un rol existente con nombre "Profesor", **When** intenta crear otro con el mismo nombre (sin distinguir mayúsculas, esté activo o inactivo), **Then** el sistema lo rechaza con un mensaje claro.
3. **Given** un usuario sin permisos de administración, **When** intenta crear un rol, **Then** el acceso es denegado.

---

### User Story 2 - Inactivar y reactivar un rol (Priority: P1)

El administrador inactiva un rol sin eliminarlo, y puede reactivarlo luego. Un rol inactivo no se puede asignar a usuarios nuevos.

**Why this priority**: Permite retirar accesos de un grupo sin perder historial ni configuración.

**Independent Test**: Inactivar un rol, verificar su estado en el listado y que no es seleccionable al asignar roles; reactivarlo y verificar lo contrario.

**Acceptance Scenarios**:

1. **Given** un rol activo, **When** el administrador lo inactiva, **Then** su estado cambia a Inactivo y conserva permisos y usuarios asociados.
2. **Given** un rol inactivo, **When** el administrador lo reactiva, **Then** vuelve a estar disponible.
3. **Given** un rol inactivo, **When** se asigna un rol a un usuario, **Then** el rol inactivo no aparece como opción.

---

### User Story 3 - Bloqueo de inicio de sesión por rol inactivo (Priority: P1)

Los usuarios cuyo rol está inactivo no pueden iniciar sesión; las sesiones ya abiertas no se interrumpen y terminan al expirar.

**Why this priority**: Es el efecto de seguridad central de inactivar un rol.

**Independent Test**: Con un usuario de rol activo, inactivar el rol y comprobar que el login falla con mensaje claro; reactivar y comprobar que el login funciona.

**Acceptance Scenarios**:

1. **Given** un usuario con credenciales correctas y rol inactivo, **When** intenta iniciar sesión, **Then** se le deniega el acceso con el mismo mensaje genérico de credenciales inválidas, sin revelar que su rol está inactivo.
2. **Given** un usuario con sesión ya abierta, **When** su rol se inactiva, **Then** su sesión continúa hasta expirar y no puede iniciar una nueva.
3. **Given** un rol reactivado, **When** sus usuarios inician sesión, **Then** acceden normalmente.

---

### Edge Cases

- Inactivar el rol Administrador: el sistema debe impedirlo para evitar bloqueo total.
- Cada usuario tiene un único rol; si ese rol está inactivo, el usuario queda bloqueado.
- Rol inactivo con usuarios asignados: sus asignaciones se conservan y se restablecen al reactivarlo.
- Roles predefinidos (Administrador, Gestor de cursos, Docente, Estudiante): no se eliminan; solo Administrador queda protegido de la inactivación.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE permitir a un administrador crear roles con nombre (obligatorio, único sin distinguir mayúsculas, incluyendo roles inactivos; si coincide con uno inactivo se indica reactivarlo) y descripción opcional.
- **FR-002**: Todo rol nuevo DEBE crearse en estado Activo.
- **FR-003**: El sistema DEBE permitir configurar los permisos de un rol nuevo mediante la gestión de permisos existente; un rol nuevo solo accede a las funciones existentes permitidas por sus permisos, sin pantallas ni flujos propios.
- **FR-004**: El sistema DEBE permitir inactivar y reactivar un rol sin eliminar sus datos ni asignaciones.
- **FR-004b**: Al inactivar un rol con usuarios asignados, el sistema DEBE pedir confirmación indicando cuántos usuarios quedarán bloqueados; sin confirmar no se inactiva.
- **FR-005**: El listado de roles DEBE mostrar el estado (Activo/Inactivo) de cada rol.
- **FR-006**: Los roles inactivos NO DEBEN ofrecerse como opción al asignar roles a usuarios.
- **FR-007**: El sistema DEBE denegar el inicio de sesión a usuarios cuyo rol esté inactivo, mostrando el mismo mensaje genérico de credenciales inválidas, sin revelar el estado del rol.
- **FR-008**: Las sesiones ya abiertas NO se interrumpen al inactivar un rol; terminan al expirar y no se pueden renovar mediante un nuevo inicio de sesión.
- **FR-009**: El sistema DEBE impedir inactivar el rol Administrador (único rol protegido).
- **FR-010**: Solo usuarios con permiso de gestión de roles DEBEN poder crear, inactivar o reactivar roles.
- **FR-011**: El sistema DEBE registrar quién y cuándo creó, inactivó o reactivó un rol.

### Key Entities

- **Rol**: nombre, descripción, estado (Activo/Inactivo), indicador de rol de sistema, permisos asociados.
- **Usuario**: persona con un único rol; su capacidad de iniciar sesión depende del estado de ese rol.
- **Registro de auditoría de rol**: acción, rol afectado, administrador responsable, fecha y hora.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador crea un rol nuevo y lo deja operativo en menos de 2 minutos.
- **SC-002**: El 100% de los intentos de login de usuarios con rol inactivo son rechazados.
- **SC-003**: Pasada la expiración de la sesión, el 100% de los usuarios con rol inactivo no pueden volver a acceder.
- **SC-004**: Tras reactivar un rol, sus usuarios pueden iniciar sesión sin intervención adicional en el 100% de los casos.
- **SC-005**: 0 casos de bloqueo total de la administración por inactivar roles críticos.

## Assumptions

- La gestión de roles y permisos ya existe; esta funcionalidad la amplía con creación y estado.
- Los roles existentes pasan a Activo al introducir el estado.
- Cada usuario tiene un único rol (modelo actual).
- La eliminación definitiva de roles queda fuera de alcance; solo se inactivan.
- Administrador es el único rol protegido contra inactivación; Gestor de cursos, Docente, Estudiante y roles nuevos son inactivables.
- Aplica a todos los métodos de acceso de la plataforma.
- Requerimientos relacionados en Obsidian: [[REQ-010 - Simplificación de Roles y Aula Virtual ONG Un Día de Esperanza]], [[REQ-008 - Matriz de Roles y Privilegios en Chamilo LMS]].
