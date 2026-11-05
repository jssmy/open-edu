# Feature Specification: Notificaciones de Operaciones vía Modal Alert

**Feature Branch**: `008-modal-alert-notifications`

**Created**: 2026-11-04

**Status**: Ready

**Input**: User description: "todos los mensajes de la operaciones exitosas o fallidas deben mostrarse como un modal alert usa el componente del alert que creamos"

## Clarifications

### Session 2026-11-04
- Q: ¿Cómo prefieres que se dispare el modal de notificación en la plataforma? → A: Solo disparos explícitos llamando al composable desde cada formulario o acción.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Notificación Modal para Operaciones Exitosas (Priority: P1)

Como usuario que realiza cualquier acción en la plataforma (por ejemplo: crear un rol, guardar cambios de permisos, matricular a un estudiante, crear un usuario o actualizar una programación), quiero que el mensaje de éxito se presente en una ventana modal tipo AlertDialog en el centro de la pantalla, con icono verde de confirmación y un botón para aceptar y continuar, para tener absoluta certeza de que la operación se completó exitosamente sin que el aviso pase desapercibido.

**Why this priority**: Es la retroalimentación más frecuente en las operaciones de la plataforma y resuelve directamente la visibilidad de los mensajes de confirmación frente a los antiguos banners estáticos.

**Independent Test**: Realizar una operación exitosa (como actualizar permisos de un rol o crear un rol) y verificar que en lugar de un banner plano en la parte superior, aparece el modal AlertDialog con variante `success`, el texto de confirmación y el botón "Entendido" / "Aceptar" invocado explícitamente desde la acción o formulario.

**Acceptance Scenarios**:

1. **Given** un usuario que completa exitosamente una operación en la plataforma, **When** el handler del formulario o acción invoca el composable de notificación, **Then** se abre el modal AlertDialog con variante `success`, título "Operación exitosa" y el mensaje descriptivo.
2. **Given** el modal AlertDialog de éxito abierto, **When** el usuario presiona el botón de confirmación ("Entendido" o "Aceptar") o pulsa la tecla Escape, **Then** el diálogo se cierra suavemente y el usuario continúa en su vista actual.

---

### User Story 2 - Notificación Modal para Operaciones Fallidas o Errores (Priority: P2)

Como usuario que interactúa con la plataforma y experimenta un error operativo o rechazo en una acción (por ejemplo: conflicto de negocio, fallo de servidor, o error general devuelto por el backend), quiero que el error se presente de forma prominente en un modal AlertDialog con variante `danger`, explicando lo ocurrido y con un botón de cierre, para comprender inmediatamente la causa del problema y no quedar desorientado.

**Why this priority**: Evita que los fallos queden escondidos en textos diminutos o pasen inadvertidos cuando el usuario no revisa partes superiores o inferiores de la página.

**Independent Test**: Enviar una acción que falle y verificar que el handler onError de la acción dispara el modal AlertDialog con variante `danger` y el mensaje de error correspondiente.

**Acceptance Scenarios**:

1. **Given** una acción que no pudo completarse y genera un error en el sistema, **When** el handler de error del formulario/acción invoca el diálogo de alerta, **Then** se presenta el modal AlertDialog con variante `danger`, título "No se pudo completar la operación" y la explicación del error.
2. **Given** el modal AlertDialog de error abierto, **When** el usuario hace clic en "Entendido", **Then** el modal se cierra permitiendo corregir los datos o reintentar.

---

### User Story 3 - Modal de Alerta Informativo con Solo Botón de Confirmación (Priority: P3)

Como usuario que recibe un mensaje modal que es meramente informativo o de notificación de estado (no una pregunta de confirmación o bifurcación), quiero que el AlertDialog muestre únicamente un botón positivo (ej. "Entendido" o "Aceptar") y oculte el botón redundante de "Cancelar", para no inducir a confusión sobre si la acción ya ocurrió o puede revertirse en ese instante.

**Why this priority**: Mejora la claridad ergonómica: una notificación de una acción ya ejecutada (éxito o fallo) no necesita opción "Cancelar", únicamente confirmación de lectura.

**Independent Test**: Verificar que al notificarse un éxito o error informativo mediante el composable, el modal muestra únicamente un botón de acción principal y no el botón secundario "Cancelar".

**Acceptance Scenarios**:

1. **Given** una alerta modal de notificación disparada por un mensaje de operación, **When** se renderiza el diálogo, **Then** solo se muestra el botón de confirmación/cierre y el botón de cancelar permanece oculto.

---

### Edge Cases

- ¿Qué sucede si ocurren múltiples mensajes en secuencia rápida? La cola de peticiones existente en `useAlertDialog` encola y despacha cada diálogo secuencialmente sin solapamientos.
- ¿Qué sucede con los errores específicos de campos de formulario (como formato inválido en un input específico)? Los errores específicos a nivel de campo continúan mostrándose junto al input respectivo para guiar la corrección directa, mientras que los fallos generales y confirmaciones de operación se canalizan al modal.
- ¿Qué sucede si se omite el `cancelLabel` en las opciones de alerta? El composable y componente reconocen cuando el diálogo es solo informativo y no renderizan el botón cancelar.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Las acciones y formularios de la plataforma DEBEN invocar explícitamente el composable de alertas (`requestAlertDialog` o helper especializado como `notifySuccess` / `notifyError`) para mostrar confirmaciones de éxito en un modal `AlertDialog` con variante `success`.
- **FR-002**: Las acciones y formularios de la plataforma DEBEN invocar explícitamente el composable de alertas ante errores de operación para mostrar el mensaje en un modal `AlertDialog` con variante `danger`.
- **FR-003**: El componente `AlertDialog` y el composable `useAlertDialog` DEBEN admitir la configuración para ocultar el botón secundario (`cancelLabel: null` o cuando no se proporcione) cuando el diálogo sea de notificación pura (mostrando únicamente el botón "Entendido" / "Aceptar").
- **FR-004**: Los banners estáticos redundantes en las vistas de formulario y layout DEBEN retirarse en favor de la confirmación modal explícita.
- **FR-005**: El diálogo modal DEBE ser accesible mediante teclado (foco atrapado, cierre con tecla Escape o tecla Enter sobre el botón principal).
- **FR-006**: Las llamadas a confirmaciones interactivas existentes (diálogos con confirmar y cancelar para acciones destructivas) DEBEN conservar su funcionamiento intacto.

### Key Entities *(include if feature involves data)*

- **NotificationAlert**: Estructura de notificación que incluye la variante (`success`, `danger`, `warning`, `info`), el título de la alerta, el mensaje detallado y la configuración de botones (`confirmLabel` y `cancelLabel` opcional/nullable).
- **AlertDialogState**: Estado reactivo global que gestiona la apertura, cierre y cola secuencial de los diálogos modales en la interfaz.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Las operaciones de formulario y acciones clave disparan un modal `AlertDialog` para retroalimentar al usuario.
- **SC-002**: 0 banners estáticos de éxito duplicados en el flujo visual principal de la plataforma.
- **SC-003**: En alertas de notificación pura, el usuario solo visualiza 1 botón de confirmación ("Entendido" / "Aceptar"), eliminando clics erróneos en botones de cancelación inexistentes.
- **SC-004**: Cero regresiones en los flujos de confirmación destructiva existentes que usan dos botones.

## Assumptions

- Se utiliza el componente del sistema de diseño ya existente (`AlertDialog.vue`, `useAlertDialog.ts`, `AlertDialogHost.vue`).
- Los disparos se gestionan de forma explícita en cada formulario o servicio desde el cliente Vue, permitiendo un control granular sobre el momento exacto y contexto de la notificación.
- No se requiere una librería externa de alertas tipo SweetAlert o Toast; se estandariza con el sistema de diseño propio de Open-Edu.
