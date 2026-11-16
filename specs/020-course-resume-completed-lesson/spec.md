# Feature Specification: Posicionamiento por Defecto en la Última Lección Completada al Entrar al Curso con Avance

**Feature Branch**: `020-course-resume-completed-lesson`  
**Created**: 2026-11-16  
**Status**: Draft  
**Input**: User description: "cuando se entra al curso y este tiene un % de avance no se esta mostrando el ultimo curso marcado como completado, es decir si alguien entra a un curso que ha estado haciendo por defecto le debe posiccionar en el ultimo curso completado"

## Clarifications

### Session 2026-11-16
- Q: ¿Al ingresar a un curso con avance parcial (por ejemplo, 50% de avance), el visor debe posicionarse en la última lección marcada como completada o en la primera lección pendiente por estudiar? → A: Opción A: Posicionarse exactamente en la última lección completada (ej. si completó 1 y 2 de 5, abre directamente en la lección 2).
- Q: Cuando el usuario pulsa el botón "✓ Marcar completada" dentro del visor, ¿la pantalla debe permanecer en la lección recién completada o debe avanzar automáticamente a la siguiente diapositiva? → A: Opción A: Permanecer en la lección actual con el estado actualizado (el usuario avanza a la siguiente lección pulsando "Siguiente →" o con las flechas cuando decida continuar).

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Reanudación en la última lección completada al entrar a un curso con avance (Priority: P1) 🎯 MVP

Como participante (estudiante o docente autoinscrito) o profesor que ingresa a un curso que ya cuenta con un porcentaje de avance registrado (> 0%),  
quiero que al entrar al visor de contenidos la pantalla se posicione por defecto en la última lección que fue marcada como completada,  
para poder revisar dónde me quedé o continuar con el temario sin tener que navegar manualmente desde la primera diapositiva.

**Why this priority**: Es la necesidad nuclear reportada por el usuario. Cuando un curso muestra progreso en el catálogo, "Mis Cursos" o "Mis Clases", entrar al visor y ver la primera diapositiva genera confusión y fricción innecesaria.

**Independent Test**:
- Tomar un curso con 5 lecciones donde las lecciones 1 y 2 están completadas (40% de avance).
- Ingresar al visor del curso mediante `/my/courses/{id}` o `/my/classes/{offering}/viewer`.
- Verificar que la diapositiva mostrada activamente en pantalla es la lección 2 (la última completada), con su información y estado reflejados en el encabezado y en el temario.

**Acceptance Scenarios**:

1. **Dado que** un usuario tiene un curso con avance previo donde las lecciones 1, 2 y 3 están marcadas como completadas y las lecciones 4 y 5 pendientes,
   **Cuando** entra al curso desde "Mis Cursos", "Mis Clases" o enlace directo,
   **Entonces** el visor se posiciona automáticamente en la lección 3 como diapositiva activa (`activeLessonIndex` correspondiente a la lección 3).
2. **Dado que** un estudiante está inscrito en una clase programada (`CourseOffering`) donde el profesor ha marcado hasta la lección 4 como dictadas,
   **Cuando** el estudiante entra al visor de la clase en `/my/courses/{course}`,
   **Entonces** el visor del estudiante muestra la lección 4 como diapositiva activa de entrada.
3. **Dado que** un usuario completó el 100% de las lecciones del curso,
   **Cuando** vuelve a ingresar al visor del curso,
   **Entonces** se posiciona en la última lección del curso (la última completada).

---

### User Story 2 - Robustez y compatibilidad multiplataforma del cálculo de reanudación (Priority: P1)

Como usuario que accede desde cualquier navegador web (Safari en Mac, Chrome, Firefox o navegadores móviles),  
quiero que el visor calcule el índice de reanudación de manera infalible y reactiva,  
para que el posicionamiento no falle por incompatibilidad de métodos recientes de JavaScript ni por la reutilización de componentes en la navegación SPA de Inertia.

**Why this priority**: Si el algoritmo de búsqueda de la última lección completada usa métodos no soportados globalmente (como `findLastIndex` sin polyfill en entornos cliente) o no reacciona ante actualizaciones de propiedades en Inertia, el visor se desborda y vuelve silenciosamente a la diapositiva 1 (índice 0).

**Independent Test**:
- Evaluar el algoritmo de resolución de índice con navegación cliente de Inertia entre cursos con y sin avance.
- Verificar que el cálculo se actualiza correctamente tanto al montar el componente como si cambian las propiedades del curso.

**Acceptance Scenarios**:

1. **Dado que** el componente `CourseViewer.vue` se monta en un entorno de navegador estándar,
   **Cuando** evalúa la lista de lecciones del curso,
   **Entonces** utiliza un algoritmo de búsqueda inversa iterativa tolerante a fallos que garantiza retornar el índice de la última lección con `is_completed: true` sin lanzar excepciones de función no definida.
2. **Dado que** un usuario navega entre diferentes cursos dentro de la misma sesión SPA de Inertia,
   **Cuando** pasa de un curso A a un curso B,
   **Entonces** el índice activo se recalcula inmediatamente con el estado del curso B y no conserva el índice del curso anterior.

---

### User Story 3 - Inicio en la primera lección para cursos sin avance (Priority: P2)

Como usuario que se matricula por primera vez en un curso o ingresa a una clase con 0% de avance,  
quiero que el visor se posicione en la lección 1 de la unidad 1,  
para comenzar mi aprendizaje desde el inicio de forma ordenada.

**Why this priority**: Evita inconsistencias cuando no existen registros previos en `lesson_progress`.

**Independent Test**:
- Abrir un curso recién publicado o donde ninguna lección esté completada (`0%` de progreso).
- Verificar que la diapositiva inicial es la lección 1 (índice 0).

**Acceptance Scenarios**:

1. **Dado que** un curso no tiene ninguna lección marcada como completada (`completed_lessons === 0`),
   **Cuando** cualquier usuario autorizado entra al visor,
   **Entonces** el visor muestra la diapositiva 1 de la unidad 1 (`activeLessonIndex === 0`).

---

## Edge Cases

- **Lecciones completadas en orden no lineal**: Si por alguna razón una lección posterior (ej. lección 4) fue completada pero una anterior (ej. lección 2) quedó desmarcada, el visor debe posicionarse en la **última lección completada en orden curricular secuencial** (la de mayor índice en el curso).
- **Curso con unidades vacías o lecciones archivadas**: Las lecciones archivadas (`is_archived: true`) no deben formar parte del temario plano ni alterar el conteo de diapositivas activas.
- **Transición inmediata al marcar lección**: Al pulsar "✓ Marcar completada", la pantalla DEBE permanecer en la lección actual reflejando el cambio de estado a completada. El usuario continúa a la siguiente lección pulsando manualmente "Siguiente →" o con las teclas de navegación.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El visor `CourseViewer.vue` DEBE determinar el índice de la lección inicial buscando de forma inversa (de fin a inicio) la última lección con `is_completed === true`.
- **FR-002**: El algoritmo de búsqueda inversa DEBE implementarse mediante un bucle iterativo backward compatible (`for (let i = list.length - 1; i >= 0; i--)`), garantizando funcionamiento idéntico en todos los navegadores sin depender de polyfills externos.
- **FR-003**: Cuando se localice una lección completada, el visor DEBE asignar su índice a `activeLessonIndex` antes de renderizar la primera diapositiva.
- **FR-004**: Si ninguna lección está completada, el visor DEBE asignar `activeLessonIndex = 0`.
- **FR-005**: El visor DEBE sincronizar el cálculo reactivamente ante cambios en `props.course` o navegación Inertia (mediante `watch` o ciclo de vida reactivo).
- **FR-006**: El temario lateral (drawer) DEBE marcar como seleccionada la lección activa posicionada por defecto.
- **FR-007**: Los endpoints backend (`/my/courses/{course}` y `/my/classes/{offering}/viewer`) DEBEN suministrar el arreglo de unidades y lecciones ordenadas estrictamente por posición (`Unit::orderBy('position')` y `Lesson::orderBy('position')`) con el booleano `is_completed` exacto.

---

## Key Entities

- **Lesson**: Lección individual con identificador, unidad, posición y bandera `is_completed: boolean`.
- **FlattenedLesson**: Estructura aplanada de lecciones con su índice global relativo dentro del curso (`indexInCourse: 0..N-1`).
- **CourseStudyData**: Contrato de datos entregado por el backend conteniendo `units`, `lessons`, `progress` y estado de oferta.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: En el 100% de los accesos a cursos con avance mayor a 0%, la diapositiva visualizada al cargar la pantalla es exactamente la última lección marcada como completada.
- **SC-002**: Cero errores de ejecución en consola JavaScript al calcular el índice de reanudación en cualquier navegador web.
- **SC-003**: Las pruebas automatizadas (Vitest para el componente frontend y Pest para los controladores backend) validan y garantizan la persistencia de esta conducta.

---

## Assumptions

- El usuario define "último curso completado" en su mensaje como "última lección completada dentro del curso al que se ingresa", en concordancia con el flujo del visor de estudio y clase.
- Los cursos estructuran su temario de forma secuencial mediante unidades y lecciones con atributo `position`.
