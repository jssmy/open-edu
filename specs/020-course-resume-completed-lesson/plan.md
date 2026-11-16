# Implementation Plan: Posicionamiento por Defecto en la Última Lección Completada

**Branch**: `020-course-resume-completed-lesson` | **Spec**: [spec.md](specs/020-course-resume-completed-lesson/spec.md) | **Obsidian Reference**: `[[REQ-036]]`

## Proposed Changes

### Frontend (`resources/js/pages/Study/CourseViewer.vue`)

- **Helper robusto de resolución de índice**:
  Implementar la función utilitaria pura `resolveLastCompletedLessonIndex(lessons: FlattenedLesson[]): number` con recorrido decreciente (`for (let i = lessons.length - 1; i >= 0; i--)`), eliminando la dependencia frágil de `Array.prototype.findLastIndex` que causa fallos silenciosos en WebKit/navegadores antiguos.
- **Inicialización y reactividad en navegación Inertia SPA**:
  - Inicializar `activeLessonIndex = ref(resolveLastCompletedLessonIndex(flattenedLessons.value))`.
  - Agregar un `watch(() => props.course.id, ...)` para recalcular la posición cada vez que el estudiante cambia de curso en navegación cliente SPA sin recarga completa de página.
  - Asegurar que durante `onMounted` se valide la concordancia del índice con los ítems aplanados.
- **Estabilidad de vista durante 'Marcar completada'**:
  - Validar que al pulsar "✓ Marcar completada" en vivo, la llamada asíncrona a `toggleCompletion` no mueva el índice actual a menos que el usuario pulse explícitamente "Siguiente →" o seleccione un ítem en el drawer de lecciones.

### Testing Frontend (`resources/js/__tests__/CourseViewerResume.test.ts`)

- Añadir pruebas unitarias y de integración de componentes en Vitest:
  - Estudiante ingresa con 0% de avance: índice resuelto es 0 (primera lección).
  - Estudiante con avance parcial (ej. lecciones 0, 1 y 3 completadas): índice resuelto es 3 (última completada).
  - Estudiante con curso al 100%: índice resuelto es la última lección del curso (`lessons.length - 1`).
  - Navegación entre dos cursos con diferente historial de avance vía reactividad de `props.course.id`.
  - Acción "Marcar completada" mantiene la lección actual seleccionada.

## Verification Plan

### Automated Tests
1. **Vitest**:
   - `PATH="/Users/jssmy/.nvm/versions/node/v22.23.4/bin:$PATH" npx vitest run resources/js/__tests__/CourseViewerResume.test.ts`
2. **Pest Feature Tests**:
   - `vendor/bin/pest tests/Feature/Study/SlideViewerResumeTest.php tests/Feature/Progress/StudentClassProgressSyncTest.php`
3. **Frontend Build**:
   - `PATH="/Users/jssmy/.nvm/versions/node/v22.23.4/bin:$PATH" npm run build`
4. **Code Quality**:
   - `vendor/bin/pint --format agent` (si hay cambios PHP)

### Manual Verification
1. Abrir un curso con avance previo (ej. 3 lecciones completadas).
2. Verificar que el visor carga automáticamente en la 3ra lección completada, mostrando su contenido y marcado en el sidebar/drawer.
3. Marcar la siguiente lección como completada; confirmar que la vista no salta automáticamente de diapositiva.
