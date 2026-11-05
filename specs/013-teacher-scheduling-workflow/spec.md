# Feature Specification: Programación Académica Basada en Docente y Reorganización de Filtros

**Feature Branch**: `013-teacher-scheduling-workflow`

**Created**: 2026-11-04

**Status**: Draft

**Input**: User description: "en http://localhost:8000/scheduling en la programacion academica en vez poner los filtros se debe poner un select para el docente, es decir para hacer la programaciona academica se debe hacer por docente por el cual se necesita el dropdow para seleccionar el docente algual se gestionara su programacion academica los fistros deben estar relacionado a las tablas de la parte inferior"

## Clarifications

### Session 2026-11-04
- Q: ¿Cuál debe ser el comportamiento inicial del selector de docente al ingresar a /scheduling? → A: Cargar por defecto el primer docente disponible y permitir una opción "Todos los docentes" para ver la programación global.
- Q: ¿El selector de docente superior debe sincronizar automáticamente la tabla inferior o actuar de forma independiente? → A: Filtrado sincronizado. Al cambiar de docente en la cabecera, la tabla inferior también se filtra automáticamente para mostrar solo las ofertas de ese docente.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Selección Principal de Docente para Gestión de Horario (Priority: P1)

Como gestor o coordinador académico en `/scheduling`, quiero seleccionar un docente específico desde un selector principal en la cabecera del módulo para visualizar y gestionar directamente su programación y carga horaria en el calendario semanal interactivo, con la posibilidad opcional de ver la programación global de todos los docentes.

**Why this priority**: Es el núcleo del nuevo flujo de trabajo operativo. La planificación académica pasa de una vista genérica multipropósito a una gestión enfocada por docente, permitiendo ver sus bloques, disponibilidad y posibles conflictos con claridad inmediata.

**Independent Test**: Seleccionar un docente desde el selector superior de la vista `/scheduling`. El calendario debe actualizarse para mostrar exclusivamente los bloques y sesiones programadas del docente seleccionado (o todos si se elige "Todos los docentes"), permitiendo crear o ajustar bloques horarios asignados a él.

**Acceptance Scenarios**:

1. **Dado que** el gestor ingresa a `/scheduling`, **Cuando** carga la página, **Entonces** el selector superior de docente se inicializa por defecto con el primer docente disponible (o el indicado en el parámetro de URL) y el calendario exhibe su programación semanal.
2. **Dado que** el gestor desea ver una panorámica general, **Cuando** selecciona la opción "Todos los docentes" en el dropdown superior, **Entonces** el calendario refleja los bloques de todos los docentes y permite una vista consolidada.
3. **Dado que** el gestor selecciona a un docente específico en el desplegable superior, **Cuando** se confirma la selección, **Entonces** el calendario semanal refleja inmediatamente los horarios asignados a dicho docente.
4. **Dado que** se abre el modal de creación de nueva sesión/programación desde el calendario (clic en celda o botón "Nueva programación"), **Cuando** hay un docente preseleccionado en la cabecera, **Entonces** dicho docente aparece precargado y vinculado por defecto en el formulario.

---

### User Story 2 - Filtros Contextuales y Sincronizados para la Tabla Inferior de Programaciones (Priority: P2)

Como administrador académico, quiero que la barra de filtros de búsqueda (curso, estado, aula, texto libre) se ubique directamente sobre la tabla inferior de listado de programaciones y esté sincronizada con el docente seleccionado arriba, de modo que pueda acotar y auditar las ofertas formativas con precisión.

**Why this priority**: Asegura coherencia integral en la pantalla: la selección del docente gobierna tanto el calendario como la tabla de ofertas subyacente, mientras que los filtros específicos de la tabla permiten refinar la búsqueda por curso, aula o estado.

**Independent Test**: Seleccionar un docente en la cabecera y constatar que la tabla inferior muestra únicamente sus programaciones; luego aplicar un filtro adicional de aula o estado sobre la tabla y verificar que los resultados se acotan respetando el docente activo.

**Acceptance Scenarios**:

1. **Dado que** el usuario visualiza la tabla inferior de programaciones académicas, **Cuando** inspecciona la interfaz, **Entonces** encuentra los controles de filtrado (búsqueda por texto, filtro de curso, filtro de aula y filtro de estado) situados inmediatamente encima de la tabla.
2. **Dado que** el usuario cambia el docente en el selector superior, **Cuando** se actualiza la vista, **Entonces** la tabla inferior se sincroniza automáticamente mostrando las ofertas del docente seleccionado (o todas si se seleccionó "Todos los docentes").
3. **Dado que** el usuario aplica filtros adicionales en la sección de la tabla (por curso, aula, estado o texto), **Cuando** presiona filtrar o limpiar filtros, **Entonces** la tabla refleja los resultados combinados de dichos filtros manteniendo el alcance del docente activo.

---

### Edge Cases

- **Docente sin horarios**: Al seleccionar un docente sin bloques programados, el calendario debe mostrarse vacío y amigable, con un estado o indicador que invite a crear su primera clase o bloque.
- **Cambio de docente con modal abierto**: Si el usuario cambia de docente en la vista principal mientras interactúa con el calendario, el contexto de trabajo se actualiza limpiando borradores no guardados.
- **Acceso rápido con parámetro en URL**: Si se accede a `/scheduling?teacher={id}`, el selector superior de docente debe inicializarse automáticamente con dicho docente.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El encabezado de gestión de `/scheduling` DEBE incorporar un selector desplegable accesible y buscable para seleccionar al docente objetivo cuya programación se administrará, incluyendo la opción "Todos los docentes" e inicializándose por defecto con el primer docente disponible.
- **FR-002**: El calendario semanal interactivo DEBE reflejar los bloques horarios y sesiones pertenecientes al docente seleccionado en el dropdown superior (o todas si se seleccionó "Todos los docentes").
- **FR-003**: Al crear una nueva programación o bloque desde la vista o calendario teniendo un docente específico seleccionado, el sistema DEBE precargar automáticamente dicho docente en el formulario.
- **FR-004**: Los controles de filtrado general (búsqueda de texto, curso, aula, estado) DEBEN ubicarse directamente sobre la tabla inferior de programaciones, sirviendo como herramientas de consulta y filtrado de las filas de dicha tabla.
- **FR-005**: La selección del docente en el selector superior DEBE sincronizar automáticamente el listado de la tabla inferior, filtrando las ofertas para coincidir con el docente seleccionado.
- **FR-006**: La tabla inferior DEBE mantener sus acciones de gestión (editar, eliminar, ver sesiones) para las programaciones listadas.

### Key Entities *(include if feature involves data)*

- **Docente (Teacher/User)**: Usuario con rol docente, seleccionado como sujeto de la planificación semanal.
- **Programación / Oferta (CourseOffering / Session)**: Clases y sesiones asignadas al docente con día, hora de inicio/fin y aula o modalidad virtual.
- **Filtros de Tabla**: Parámetros de consulta (curso, aula, estado, texto) asociados al listado tabular inferior combinados con el docente activo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Los coordinadores pueden cambiar de docente y visualizar su horario semanal y su tabla de ofertas en menos de 2 segundos.
- **SC-002**: El 100% de las nuevas programaciones creadas desde el calendario toman por defecto al docente actualmente seleccionado, eliminando selecciones redundantes.
- **SC-003**: La separación de responsabilidades visuales (Docente/Calendario arriba, Filtros/Tabla abajo con sincronización de docente) reduce la confusión operativa y permite una auditoría inmediata por docente.

## Assumptions

- Se mantiene la compatibilidad con el backend de resolución de conflictos de horarios para docentes y aulas.
- Los docentes activos continúan siendo provistos en la carga de la vista desde el backend para poblar el dropdown de selección.
- Las sesiones y bloques del calendario ya disponen del identificador del docente para su filtrado reactivo o petición al servidor.
