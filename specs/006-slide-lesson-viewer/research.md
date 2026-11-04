# Phase 0 Research: Visor de Lecciones en Modo Diapositivas y Clases para Docentes

## 1. Contexto Actual de Visualización de Lecciones

- **Componente**: `resources/js/pages/Study/CourseViewer.vue`.
- **Estructura previa**: Lista vertical de todas las unidades con todas las lecciones dentro de cada unidad desplegadas de arriba a abajo.
- **Problema**: Dificultad para concentrarse en una lección específica, scroll excesivo y falta de experiencia tipo diapositiva o presentación de clase.

## 2. Decisión Arquitectónica: Modelo de Diapositivas

- Se crea un array aplanado de lecciones ordenadas (`flattenedLessons`), manteniendo la referencia a su unidad correspondiente.
- Se mantiene un puntero activo `activeLessonIndex` (por defecto la primera lección no completada, o la primera del curso).
- La diapositiva renderiza únicamente la lección activa con su contenido (RichText, imagen, video embebido o documento).
- Controles:
  - Botón **Anterior**: `activeLessonIndex > 0 ? activeLessonIndex - 1 : null`.
  - Botón **Siguiente**: `activeLessonIndex < total - 1 ? activeLessonIndex + 1 : null`.
  - Navegación con teclado: Event listener en `window` para `ArrowRight` (siguiente) y `ArrowLeft` (anterior).

## 3. Decisión de Navegación Docente y Clases

- **Módulo Clases**: Ruta `/my/classes`.
  - Controlador: `App\Modules\Scheduling\Infrastructure\Http\TeacherClassController`.
  - Utiliza `CourseOffering` filtrado por el docente autenticado (`whereHas('teachers', ...)` o sesiones).
  - Al hacer clic en una clase, se accede a `/my/classes/{offering}/viewer` que renderiza `Study/CourseViewer` reutilizando el visor de diapositivas con flag `isTeacherView: true`.
- **Acceso a Cursos (`/courses`)**:
  - Restringido en el menú `AppLayout.vue` verificando el permiso explícito `courses.view` / `courses.manage`.
  - Si un docente tiene permiso para entrar a `/courses`, `CoursePolicy` garantiza que solo pueda entrar a editar si `$course->teachers()->whereKey($user->id)->exists()`.

