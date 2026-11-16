# Research: Posicionamiento por Defecto en la Última Lección Completada al Entrar al Curso con Avance

**Feature**: `020-course-resume-completed-lesson`  
**Status**: Completed  
**Spec**: [spec.md](spec.md)

---

## 1. Contexto del Problema y Hallazgos

### Hallazgo 1: Incompatibilidad de `Array.prototype.findLastIndex`
- **Diagnóstico**: `Array.prototype.findLastIndex` es una especificación ECMAScript 2023. No se encuentra disponible en versiones de Safari anteriores a 15.4, navegadores embebidos o webviews heredadas.
- **Riesgo**: Vite compila para `target: "ESNext"` y no inyecta polyfills globales de runtime. Cuando un cliente sin soporte nativo evalúa `flattenedLessons.value.findLastIndex`, el motor lanza `TypeError: ...findLastIndex is not a function`, interrumpiendo el bloque `<script setup>` y dejando `activeLessonIndex.value` en su valor de fallback `0` (la primera lección).
- **Decisión**: Reemplazar `findLastIndex` por un algoritmo iterativo inverso universal:
  ```typescript
  function resolveLastCompletedLessonIndex(lessons: FlattenedLesson[]): number {
      for (let i = lessons.length - 1; i >= 0; i--) {
          if (lessons[i]?.is_completed) {
              return i;
          }
      }
      return 0;
  }
  ```

### Hallazgo 2: Reactividad y Ciclo de Vida en SPA con Inertia
- **Diagnóstico**: En la arquitectura SPA con Inertia.js, la navegación entre rutas que comparten el mismo componente (`Study/CourseViewer`) puede reutilizar la instancia del componente sin desmontarlo ni re-ejecutar el script inicial. Si `activeLessonIndex` solo se asigna de forma imperativa al inicio del script, una transición entre cursos o recarga de props puede mantener un índice desincronizado.
- **Decisión**:
  1. Inicializar `activeLessonIndex` con `ref(resolveLastCompletedLessonIndex(flattenedLessons.value))`.
  2. Implementar un observador (`watch`) sobre `() => props.course.id` para re-posicionar el índice automáticamente cuando se cargue un curso distinto.
  3. Ejecutar la sincronización también en `onMounted`.

### Hallazgo 3: Comportamiento al Marcar Lección en Vivo
- **Diagnóstico**: Al hacer clic en "✓ Marcar completada" mientras se visualiza una diapositiva, el usuario no debe experimentar un salto de diapositiva involuntario.
- **Decisión**: El visor debe preservar la diapositiva activa actual (`activeLessonIndex.value`). La lección actual actualiza su badge a "✓ Lección completada" (o "✓ Lección dictada"), y el usuario avanza a la siguiente lección mediante el botón "Siguiente →" o el teclado cuando decida continuar.

---

## 2. Decisiones de Arquitectura y Patrones

| Decisión | Alternativa Evaluada | Razón de la Elección |
| :--- | :--- | :--- |
| **Búsqueda inversa iterativa manual** | Instalar polyfill `core-js` | Cero dependencias añadidas, cumplimiento estricto del principio YAGNI y rendimiento $O(N)$ óptimo. |
| **Sincronización vía watcher reactivo** | Forzar desmontaje con `key` en Inertia | El watcher desacopla el visor de la configuración del router y asegura resiliencia ante cualquier mutación de props. |
| **Posicionamiento en la última completada** | Posicionar en la primera lección pendiente | Cumple con la instrucción directa del usuario y el requerimiento de negocio acordado en `[[REQ-036]]`. |

---

## 3. Matriz de Compatibilidad de Navegadores

- **Chrome / Edge**: Compatible (v1+ con bucle manual).
- **Safari / WebKit**: 100% compatible (evita fallo de Safari < 15.4).
- **Firefox / Gecko**: Compatible.
- **Navegadores móviles (iOS Safari / Android WebView)**: 100% compatible.

