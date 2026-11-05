# Feature Specification: Estado de Carga, Spinner y Deshabilitación en Botones de Operación

**Feature Branch**: `009-button-spinner-loading-state`

**Created**: 2026-11-04

**Status**: Ready

**Input**: User description: "todas las operaciones lanzadas desde un bottn deben tener spiner, es decir el botton se deshabilida \"guardando\" y con icono de spiner"

## Clarifications

### Session 2026-11-04
- Q: ¿Cómo prefieres que Button maneje el texto dinámico ("Guardando...") durante la carga? → A: Automático con prop opcional `loadingText` (si se pasa muestra ese texto, si no conserva el contenido del slot o usa fallback "Guardando...").

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Indicador Visual de Spinner en Componente Base Button (Priority: P1) 🎯 MVP

Como usuario de cualquier formulario o acción en la plataforma, cuando presiono un botón para ejecutar una operación, quiero que aparezca inmediatamente un icono de spinner animado giratorio dentro del botón y que este quede inhabilitado para clics, para tener confirmación instantánea de que el sistema recibió la orden y está trabajando en ella.

**Why this priority**: Es la base transversal del sistema de diseño (`Button.vue`). Al implementar el spinner visual y la deshabilitación en el componente raíz, se garantiza que cualquier botón que use `loading` muestre el spinner de inmediato de forma estándar y consistente.

**Independent Test**: Montar el componente `Button` con `:loading="true"` y comprobar que:
1. Renderiza un SVG animado con la clase `animate-spin`.
2. Posee el atributo `disabled` y `aria-busy="true"`.
3. No emite eventos de clic.

**Acceptance Scenarios**:

1. **Given** un botón con `loading=false`, **When** cambia a `loading=true`, **Then** aparece el icono de spinner animado antes del contenido y el botón se desactiva contra nuevas pulsaciones.
2. **Given** un botón en estado `loading=true`, **When** el usuario hace clic repetidamente sobre él, **Then** ninguna acción adicional se dispara y el puntero muestra cursor no permitido.

---

### User Story 2 - Texto Dinámico de Progreso en Botones de Guardado (Priority: P2)

Como usuario que ejecuta una acción de persistencia (como guardar permisos o crear un registro), quiero que mientras el proceso esté activo, el botón muestre automáticamente el texto de progreso configurado (por ejemplo, "Guardando..." o el texto especificado en la prop `loadingText`), para tener una comunicación humana y comprensible del proceso en curso.

**Why this priority**: Complementa al spinner aportando claridad semántica inequívoca a la operación específica que se está llevando a cabo sin requerir ternarios repetitivos en cada plantilla.

**Independent Test**: Montar un `Button` con `:loading="true"` y `loadingText="Guardando..."` y verificar que el texto renderizado coincide con `loadingText` junto al spinner.

**Acceptance Scenarios**:

1. **Given** un botón con `loadingText="Guardando..."` y `loading=true`, **When** se renderiza, **Then** muestra el spinner giratorio y el texto "Guardando...".
2. **Given** el modal de permisos en `/roles`, **When** el usuario presiona "Guardar y cerrar", **Then** el botón pasa a estado de carga, muestra el spinner animado, cambia su etiqueta a "Guardando..." y se deshabilita hasta recibir respuesta.

---

### User Story 3 - Integración en Operaciones Modales y Acciones Asíncronas (Priority: P3)

Como Administrador que gestiona roles, permisos y configuraciones en ventanas modales, quiero que todos los botones de acción transaccional de los modales (guardar permisos, confirmar operaciones, etc.) mantengan sincronizado su estado reactivo de carga mientras el backend responde, evitando el cierre prematuro o dobles peticiones.

**Why this priority**: Asegura que las ventanas modales y operaciones asíncronas respeten el ciclo de vida completo de la petición antes de cerrar o notificar.

**Independent Test**: En el modal de permisos de `/roles`, pulsar "Guardar y cerrar" y comprobar que mientras la llamada PUT está en tránsito, el modal no se cierra prematuramente y los botones quedan protegidos contra doble interacción.

**Acceptance Scenarios**:

1. **Given** una acción asíncrona en un modal, **When** se inicia la petición de red, **Then** el botón de guardar activa el spinner y deshabilita tanto el botón de guardar como el botón de cancelar para prevenir estados inconsistentes durante la sincronización.
2. **Given** la finalización de la petición (éxito o fallo), **Then** el estado de carga se apaga y el botón recupera su estado natural.

---

### Edge Cases

- ¿Qué sucede si la red es extremadamente lenta o la petición toma varios segundos? El spinner continúa girando y el botón permanece deshabilitado hasta que se complete o aborte la petición.
- ¿Qué sucede si la petición falla con error del servidor? El botón apaga inmediatamente el spinner, se vuelve a habilitar y restaura su texto original para que el usuario pueda corregir o reintentar.
- ¿Qué sucede si el botón no define `loadingText`? Muestra el spinner animado junto al slot por defecto del botón, conservando la flexibilidad de uso.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El componente base `Button.vue` DEBE renderizar un icono de spinner animado (`svg` con `animate-spin` y `aria-hidden="true"`) cuando la propiedad `loading` sea verdadera.
- **FR-002**: El componente base `Button.vue` DEBE admitir la propiedad opcional `loadingText?: string` para reemplazar dinámicamente el texto del botón por el mensaje de progreso mientras `loading` esté activo (por defecto fallback a "Guardando..." cuando corresponda o conservación del slot).
- **FR-003**: El componente base `Button.vue` DEBE deshabilitarse nativamente (`disabled` y clase `disabled:cursor-not-allowed`) e indicar `aria-busy="true"` mientras `loading` sea verdadero.
- **FR-004**: El botón "Guardar y cerrar" en el modal de permisos de roles (`Roles/Index.vue`) DEBE contar con estado de carga reactivo (`savingPermissions` o equivalente) enlazado a la prop `:loading` y `loading-text="Guardando..."`.
- **FR-005**: Mientras una operación de guardado en modal esté en curso (`loading: true`), los botones secundarios (como "Cancelar") DEBEN deshabilitarse temporalmente para evitar interrupciones o estados a medio sincronizar.
- **FR-006**: Todos los formularios que utilizan `useForm` de Inertia DEBEN enlazar la prop `:loading="form.processing"` a su botón principal de envío.

### Key Entities *(include if feature involves data)*

- **ButtonProps**: Interfaz de propiedades del componente `Button` que incluye `variant`, `type`, `loading` (boolean), `loadingText` (string opcional) y `disabled` (boolean).
- **AsyncOperationState**: Estado reactivo en las vistas (`processing: boolean`) que gobierna la sincronización entre el ciclo de vida de la petición HTTP y la retroalimentación visual del botón.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% de los botones que tengan `loading === true` renderizan un spinner SVG animado visible.
- **SC-002**: 0 eventos de clic o dobles envíos procesados mientras un botón se encuentra en estado `loading`.
- **SC-003**: Transición inmediata (< 16ms) al estado con spinner y deshabilitación al pulsar el botón de guardar.
- **SC-004**: El botón de guardado en el modal de permisos de roles refleja visiblemente "Guardando..." con spinner durante toda la petición PUT.

## Assumptions

- Se utiliza el componente existente `Button.vue` ubicado en `resources/js/design-system/Button.vue`.
- Tailwind CSS v4 provee la utilidad estándar de animación `animate-spin` para iconos SVG.
- La lógica no altera los contratos de backend de los endpoints de la API.
