# Research: Marcado de Lección Completada y Reanudación de Avance para Docentes

**Feature**: `019-teacher-lesson-completion-resume`
**Date**: 2026-11-16

## 1. Modelo Dual de Avance: Cursos Programados vs Cursos Asíncronos

### Decision
Soportar explícitamente las dos modalidades de avance en Open-Edu:
1. **Cursos con Programación Académica (`CourseOffering`)**:
   - Tienen horario, fechas de inicio/fin y docente asignado.
   - El docente dicta y marca las lecciones completadas desde `/my/classes/{offering}/viewer`.
   - Se persiste en `lesson_progress` con `course_offering_id` y `user_id` del docente (`student_id = null`).
   - Cuando cualquier estudiante matriculado en esa oferta académica abre el visor de su curso (`/my/courses/{course}`), la consulta de progreso incluye las lecciones completadas para su `course_offering_id`. De este modo, los estudiantes ven completadas las lecciones que su docente dictó en clase.
2. **Cursos Asíncronos / Offline / Autogestionados**:
   - No tienen programación académica ni horarios (ej. cursos de capacitación y onboarding para docentes autoinscritos según REQ-034).
   - El usuario cursa en `/my/courses/{course}` a su propio ritmo.
   - Se persiste en `lesson_progress` con `user_id` o `student_id` y `course_offering_id = null`.
   - El progreso es estrictamente personal y no impacta a ningún otro usuario.
3. **Reanudación Automática en la Última Completada**:
   - En ambos visores (`CourseViewer.vue`), se calcula `findLastIndex((l) => l.is_completed)`.
   - Si existe al menos una lección completada, el visor inicializa la diapositiva activa en el índice de dicha lección. Si ninguna está completada, inicializa en el índice 0.

---

## 2. Endpoint Unificado y Reversibilidad (Toggle) para Docentes

### Decision
Habilitar en `POST /lessons/{lesson}/complete` el soporte para el parámetro opcional `course_offering_id`. Si se envía `course_offering_id`, el backend verifica que el usuario autenticado sea docente asignado a la oferta (o administrador con permisos), y procesa el estado. Si se especifica `toggle = true` (o si la lección ya está completada y el docente solicita desmarcar), se elimina el registro de completitud de esa lección en la oferta. Para estudiantes, el marcado se mantiene idempotente y sin alteración.

### Rationale
1. Centraliza la mutación de estado de lecciones en `CompleteLesson` / `ProgressRepository`.
2. Proporciona a los docentes la flexibilidad necesaria durante la proyección en vivo: si accidentalmente marcaron la lección o si la clase terminó antes de concluir el tema, pueden desmarcarla en un solo clic.

### Alternatives Considered
- **Crear una ruta DELETE separada `/lessons/{lesson}/complete`**: Añadiría complejidad en el frontend y endpoints redundantes cuando una sola acción atómica `POST /lessons/{lesson}/complete` con `course_offering_id` y `action: 'complete' | 'uncomplete' | 'toggle'` cubre ambos casos limpiamente.

---

## 3. Algoritmo de Reanudación de la Última Lección Completada

### Decision
En `CourseViewer.vue` (tanto para `isTeacherView` como para vista de estudiante), calcular el índice de la diapositiva inicial:
```ts
const lastCompletedIndex = flattenedLessons.value.findLastIndex((l) => l.is_completed);
activeLessonIndex.value = lastCompletedIndex >= 0 ? lastCompletedIndex : 0;
```

### Rationale
- Si el usuario (docente o estudiante) completó las lecciones 1, 2 y 3: `findLastIndex` retorna 2 (la lección 3). El visor arranca en la lección 3 (la última dejada completada), permitiendo al usuario repasar el cierre anterior o hacer clic en "Siguiente" para continuar.
- Si ninguna lección está completada (`lastCompletedIndex === -1`): arranca en el índice 0 (primera lección de la primera unidad).
- Si todas están completadas: arranca en la última lección del curso.
- Respeta literalmente la indicación del usuario: *"cuando vuelven a entrar debe posicionarse en el ultimo que haya dejado marcado como completado"*.

### Alternatives Considered
- **Posicionar en la primera lección incompleta (`firstIncomplete`)**: Era la lógica que tenían los estudiantes previamente. Sin embargo, no cumplía el requerimiento explícito del usuario de posicionarse exactamente en la última marcada como completada.

---

## 4. Experiencia de Usuario y Visor en Modo Docente

### Decision
En `CourseViewer.vue`:
1. Cuando `isTeacherView === true`, mostrar la barra de progreso global del curso (`completed_lessons` de `total_lessons`), badges de estado `Completada` / `Pendiente` en la cabecera de la diapositiva, y los checks verdes `✓` en el temario lateral.
2. Botón de acción dual para docente:
   - Si la lección está pendiente: botón `"✓ Marcar completada"` (variante primaria/secundaria accesible).
   - Si la lección está completada: botón interactivo `"✓ Lección dictada"` que permite desmarcar con confirmación sutil (`"Desmarcar"`).
3. En `TeacherClassController@viewer`:
   - Consultar las lecciones completadas para la oferta (`course_offering_id`).
   - Mapear `'is_completed'` como booleano real basado en los registros de `lesson_progress`.
   - Calcular `progress` real (`completed_lessons`, `total_lessons`, `percentage`).

