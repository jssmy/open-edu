# Quickstart: Validación de Marcado de Lección y Reanudación de Avance para Docentes

**Feature**: `019-teacher-lesson-completion-resume`
**Date**: 2026-11-16

Guía de verificación rápida de los flujos principales de la funcionalidad para comprobar su correcto funcionamiento extremo a extremo.

## 1. Prerrequisitos y Configuración

1. Contar con un usuario con rol `docente` activo en el sistema.
2. Contar con un curso publicado con al menos 3 lecciones organizadas en unidades.
3. El docente debe estar asignado formalmente a una oferta de clase (`CourseOffering`) de dicho curso.

```bash
# Ejecutar migraciones si se aplicaron cambios al esquema de lesson_progress
php artisan migrate --force
```

---

## 2. Escenarios de Validación

### Escenario A: Marcado de Lección Completada por el Docente en Visor de Clase
1. Iniciar sesión como docente y navegar a `/my/classes`.
2. En la tarjeta de la clase asignada, hacer clic en la acción para dictar clase (abrir visor en `/my/classes/{offering}/viewer`).
3. Verificar que en la diapositiva activa se muestra la insignia de estado y el botón interactivo **"✓ Marcar completada"**.
4. Hacer clic en **"✓ Marcar completada"**:
   - **Resultado Esperado**: La lección cambia a estado "Completada", la barra de progreso general incrementa su porcentaje y en el drawer del temario aparece el check verde `✓`.

---

### Escenario B: Reanudación Automática en la Última Lección Completada
1. En el visor de la clase, marcar la **Lección 2** como completada (dejando la Lección 1 completada y la Lección 3 pendiente).
2. Salir del visor haciendo clic en `← Volver a mis clases`.
3. Volver a ingresar al visor de la misma clase desde `/my/classes`.
   - **Resultado Esperado**: El visor carga directamente posicionándose en la **Lección 2** (la última dejada completada), sin requerir navegación manual ni comenzar en la Lección 1.

---

### Escenario C: Desmarcado de Lección (Reversibilidad para Docentes)
1. En el visor de la clase, navegar a una lección completada (ej. Lección 2).
2. Hacer clic en la opción de desmarcar lección (**"✓ Lección completada (Desmarcar)"**).
   - **Resultado Esperado**: El estado de la lección cambia a "Pendiente", la barra de progreso descuenta la lección y se retira el check del temario.

---

### Escenario D: Reanudación en Visor de Estudiante
1. Iniciar sesión como estudiante inscrito en un curso y acceder a `/my/courses/{course}`.
2. Completar lecciones 1 y 2.
3. Salir y reingresar al visor del curso.
   - **Resultado Esperado**: El estudiante reanuda en la **Lección 2** (última completada). Si ninguna estuviera completada, el visor arranca en la Lección 1.

---

## 3. Comandos de Pruebas Automatizadas

```bash
# Ejecutar pruebas Pest de Feature vinculadas al visor y progreso
vendor/bin/pest tests/Feature/Scheduling/TeacherClassViewerTest.php tests/Feature/Progress/LessonCompletionTest.php

# Ejecutar pruebas Vitest de componentes frontend
npm run test
```

