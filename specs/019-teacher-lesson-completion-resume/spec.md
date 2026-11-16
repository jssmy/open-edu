# Feature Specification: Marcado de Lección Completada y Reanudación de Avance para Docentes

**Feature Branch**: `019-teacher-lesson-completion-resume`

**Created**: 2026-11-16

**Status**: Draft

**Input**: User description: "cuando el docente entra a dictar la clase tambien tiene la opcion de marcarlo como completado asi como es estudiante son la misma opcion. Y cuando vuelven a entrar debe posicionarse en el ultimo que haya dejado marcado como completado"

## Clarifications

### Session 2026-11-16
- Q: ¿Cómo se diferencia el avance entre cursos con programación académica y cursos asíncronos/offline? → A: En cursos con programación académica (con horario y docente asignado en `CourseOffering`), el docente marca la lección dictada en su clase y dicha completitud se refleja para todos los estudiantes matriculados en ese grupo/oferta. En cursos asíncronos u offline (sin programación académica, como docentes autoinscritos en capacitaciones), el marcado es personal e individual y afecta únicamente al participante inscrito. En ambos casos, al reingresar al visor se posiciona en la última lección marcada como completada.
- Q: En cursos con programación académica, ¿los estudiantes también tienen botón para marcar lecciones o el control es exclusivo del docente? → A: Control exclusivo del docente en clases programadas (Opción A). Solo el docente asignado puede marcar o desmarcar lecciones en `/my/classes/{offering}/viewer`; los estudiantes matriculados en esa oferta ven el avance dictado en modo lectura en `/my/courses/{course}` (sin botón de marcado individual para evitar desincronizaciones con el ritmo de clase). En cursos asíncronos/offline, cada participante conserva su botón individual de marcado.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Docente marca lecciones en clases con programación académica (Priority: P1)

Como docente dictando una clase asignada con horario y grupo programado (`CourseOffering`),
quiero marcar la lección que acabo de dictar como completada en el visor de clase,
para que tanto yo como todos los estudiantes inscritos en este grupo veamos reflejado el avance oficial de las lecciones impartidas.

**Why this priority**: Es el flujo central de las clases sincronizadas en vivo: el avance curricular del grupo lo conduce el docente al dictar la sesión, y los alumnos del grupo deben ver actualizada la cobertura temática de su clase.

**Independent Test**: Iniciar sesión como docente de un grupo programado, abrir el visor en `/my/classes/{offering}/viewer`, marcar la Lección 1 como completada. Iniciar sesión como estudiante inscrito en ese grupo y abrir `/my/courses/{course}`; verificar que la Lección 1 aparece marcada como completada y el avance del grupo coincide.

**Acceptance Scenarios**:

1. **Dado que** un docente dicta una clase asignada (`CourseOffering`) y marca una lección como completada en `/my/classes/{offering}/viewer`, **Cuando** cualquier estudiante inscrito en ese grupo ingresa a su visor del curso (`/my/courses/{course}`), **Entonces** visualiza dicha lección como completada con la barra de progreso del curso actualizada al avance del grupo, sin mostrar botón de marcado individual (modo sincronizado con el docente).
2. **Dado que** el docente se encuentra dictando la clase y marcó una lección por error, **Cuando** pulsa la opción de desmarcar en el visor de clase, **Entonces** la lección revierte a estado pendiente tanto para el docente como para los estudiantes del grupo.

---

### User Story 2 - Participante en curso asíncrono/offline marca su propio avance individual (Priority: P2)

Como usuario (por ejemplo un docente autoinscrito en una capacitación o un curso sin programación académica ni horarios fijos),
quiero marcar las lecciones que voy estudiando a mi propio ritmo como completadas,
para llevar mi avance personal exclusivo sin afectar a otros usuarios ni depender de un horario de dictado.

**Why this priority**: Garantiza la total independencia y privacidad del aprendizaje autónomo/asíncrono en cursos de capacitación institucional y onboarding docente.

**Independent Test**: Un docente se autoinscribe en un curso de capacitación desde `/courses`, ingresa al visor `/my/courses/{course}`, marca lecciones a su ritmo y verifica que su porcentaje y lecciones completadas son exclusivamente suyos.

**Acceptance Scenarios**:

1. **Dado que** un usuario está inscrito en un curso asíncrono (sin oferta académica grupal vinculada), **Cuando** pulsa "Marcar completada" en una lección, **Entonces** la lección se registra como completada únicamente para su usuario (`user_id` / `student_id`), sin reflejarse en ningún otro participante.

---

### User Story 3 - Reanudación automática en la última lección completada (Priority: P1)

Como docente o estudiante que vuelve a ingresar al visor de clase o curso,
quiero que la pantalla se posicione automáticamente en la última lección que se dejó marcada como completada,
para continuar la enseñanza o el estudio de inmediato sin perder tiempo buscando manualmente en el temario.

**Why this priority**: Requerimiento explícito del usuario para agilizar el inicio de clases y sesiones de estudio.

**Independent Test**: Tener lecciones 1 y 2 completadas y lección 3 pendiente. Salir y reingresar al visor; comprobar que la lección 2 (la última completada) se proyecta inmediatamente como diapositiva activa.

**Acceptance Scenarios**:

1. **Dado que** un grupo de clase o curso registra lecciones completadas (ej. 1, 2 y 3), **Cuando** el docente o estudiante abre el visor, **Entonces** el visor selecciona y proyecta automáticamente la última lección completada (lección 3).
2. **Dado que** no existe ninguna lección completada aún en la clase o curso, **Cuando** se abre el visor, **Then** se proyecta de forma predeterminada la primera lección (unidad 1, lección 1).
3. **Dado que** todas las lecciones del curso están completadas, **When** se abre el visor, **Then** se proyecta la última lección del curso.

---

### Edge Cases

- **Lecciones no consecutivas**: Si se marcan lecciones alternas (ej. lecciones 1 y 3), al reingresar el visor se posiciona en la lección completada de mayor orden en el temario (lección 3).
- **Curso sin lecciones publicadas**: Muestra un estado amigable indicando que no hay contenido sin errores de índice.
- **Acceso concurrente**: Múltiples estudiantes visualizando la clase del docente reciben el avance de lecciones completadas sin bloqueos ni colisiones.
- **Clase programada finalizada**: Al concluir la fecha límite del grupo, el visor opera en modo de lectura y se bloquea la modificación de completitud.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE distinguir entre dos modalidades de curso:
  - **Cursos con Programación Académica (`CourseOffering`)**: con horario, fechas y docente asignado.
  - **Cursos Asíncronos / Offline**: sin horarios programados, para estudio autónomo o capacitaciones docentes.
- **FR-002**: En cursos con programación académica, cuando el docente asignado marca una lección como completada desde `/my/classes/{offering}/viewer`, el sistema DEBE registrar la completitud vinculada a la oferta académica (`course_offering_id`), de modo que todos los estudiantes matriculados en dicho grupo vean la lección como completada.
- **FR-003**: En cursos con programación académica, el docente DEBE disponer de la opción de desmarcar (toggle) una lección completada en caso de corrección pedagógica durante la clase.
- **FR-004**: En cursos con programación académica, el visor de contenidos para los estudiantes (`/my/courses/{course}`) DEBE ocultar el botón de marcado manual de lección, reflejando exclusivamente el avance dictado por el docente para preservar la sincronización de la cohorte.
- **FR-005**: En cursos asíncronos / offline, cuando un participante (estudiante o docente autoinscrito) marca una lección como completada desde `/my/courses/{course}`, el sistema DEBE registrar el avance de forma estrictamente individual (`user_id` / `student_id`), afectando únicamente al usuario que lo realizó.
- **FR-006**: Al cargar el visor de diapositivas (tanto en `/my/classes/{offering}/viewer` como en `/my/courses/{course}`), el sistema DEBE calcular dinámicamente la última lección completada en el orden del temario y posicionar la diapositiva activa en dicha lección.
- **FR-007**: Si ninguna lección ha sido completada en el curso o clase, el visor DEBE inicializarse en la primera lección disponible (índice 0).
- **FR-008**: El visor en modo docente DEBE reflejar la barra de progreso curricular de la clase y mostrar los iconos de verificación `✓` en el drawer del temario para las lecciones completadas del grupo.

### Key Entities *(include if feature involves data)*

- **Progreso de Lección (`LessonProgress`)**:
  - En cursos con programación académica: contiene `course_offering_id` y `lesson_id` (completitud de grupo).
  - En cursos asíncronos: contiene `user_id` o `student_id` y `lesson_id` con `course_offering_id = null` (completitud individual).
- **Oferta de Clase (`CourseOffering`)**: Representa la cohorte con horarios, calendario y docente asignado.
- **Matrícula (`Enrollment`)**: Vincula al participante con el curso y opcionalmente con su `course_offering_id`.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los estudiantes de un grupo programado ven actualizada la lección completada por su docente en su visor de curso en tiempo real o en su siguiente visita.
- **SC-002**: En cursos asíncronos, el 100% de los avances de lecciones se mantienen privados y no interfieren con otros inscritos.
- **SC-003**: En el 100% de aperturas de visor con historial previo, el visor reanuda automáticamente en la última lección completada.
- **SC-004**: Tiempo de búsqueda de lección previa reducido a 0 clics para docentes y estudiantes.

## Assumptions

- En cursos con programación académica, los estudiantes están vinculados a su grupo mediante `Enrollment.course_offering_id`.
- Los cursos de capacitación docente creados según [[REQ-034 - Categorias de Cursos Audiencia Docente-Estudiante y Emision de Certificados]] operan bajo la modalidad asíncrona/offline (autoinscripción directa, sin `CourseOffering`).

