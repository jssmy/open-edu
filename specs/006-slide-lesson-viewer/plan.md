# Implementation Plan - Visor de Lecciones en Modo Diapositivas y Módulo de Clases para Docentes

## Technical Context

- **Stack**: Laravel 12 / PHP 8.4 (Backend Monolítico Modular con Arquitectura Hexagonal), Vue 3 / Inertia.js v3 (Frontend con Composition API, `<script setup>`, TypeScript, Tailwind CSS).
- **Core Modules Involved**:
  - `Scheduling`: `CourseOffering` (modelo de clases programadas), sesiones, asignación docente.
  - `Courses`: `Course`, `Unit`, `Lesson`, `CoursePolicy`.
  - `Progress`: `StudyController`, `CourseViewer.vue`.
  - `Navigation & Identity`: `AppLayout.vue`, `RolesAndPermissionsSeeder`, permisos Spatie.
- **New Additions**:
  - Nuevo módulo/vista docente "Clases" (`/my/classes` o `/classes`) con tarjetas `CourseCard`.
  - Endpoint y controlador para visualización de curso docente en modo diapositivas (`/classes/{offering}/preview` o soporte en `StudyController`/`TeacherClassController`).
  - Rediseño de `CourseViewer.vue` a formato slide runner: estado `currentLessonIndex`, botones Anterior / Siguiente, navegación libre y drawer/sidebar de temas colapsable.
  - Restricción del menú y rutas para docentes: `courses.view` condicional y validación estricta de asignación para edición (`CoursePolicy::manageContent` / `update`).

## Constitution Check

- **I. Monolito Modular con Arquitectura Hexagonal**: Se respetan las fronteras entre módulos (`Courses`, `Scheduling`, `Progress`). Los controladores son delgados y las políticas de autorización encapsulan las reglas de acceso.
- **III. DRY, KISS, YAGNI**: Se reutiliza el mismo visor interactivo `CourseViewer.vue` para alumnos y docentes; se reutiliza `CourseCard.vue` para mostrar las clases asignadas al profesor.
- **IV. Clean Code**: Código TypeScript fuertemente tipado en Vue; PHP 8.4 con tipos explícitos y PHPDocs.
- **V. Frontend con Vue y Sistema de Diseño**: Uso exclusivo de componentes del Design System (`Button`, `Badge`, `Card`, `IconButton`), tokens y estilos Tailwind consistentes.

## Gates Evaluation

- **Gate 1 (Arquitectura)**: APROBADO. No se rompen dependencias de dominio ni se crean capas innecesarias.
- **Gate 2 (Seguridad & Autorización)**: APROBADO. El docente solo puede editar cursos que tenga en `course_teacher` y solo accede a Cursos si tiene el permiso respectivo.

---

## Phase 0: Research & Decision Log

Ver archivo complementario [`specs/006-slide-lesson-viewer/research.md`](research.md).

## Phase 1: Data Model, Contracts & Quickstart

- [`specs/006-slide-lesson-viewer/data-model.md`](data-model.md)
- [`specs/006-slide-lesson-viewer/contracts/classes-and-viewer.md`](contracts/classes-and-viewer.md)
- [`specs/006-slide-lesson-viewer/quickstart.md`](quickstart.md)

---

## Phase 2: Implementation Breakdown

1. **Ajuste de Permisos y Navegación**:
   - Ajustar `AppLayout.vue`: Enlace "Clases" (`/my/classes`) visible para docentes con permiso `scheduling.view-own` o rol `docente`.
   - Condicionar enlace "Cursos" en `AppLayout.vue` a que el usuario tenga explícitamente `courses.view` o `courses.manage`.
   - Reforzar `CoursePolicy`: denegar `manageContent` y `update` si el usuario es docente y no está asignado en `course_teacher`.

2. **Backend Módulo Clases Docente**:
   - Crear controlador `TeacherClassController` con index (`/my/classes`) listando sus `CourseOffering` asignadas con la relación a `Course`.
   - Crear ruta para que el docente acceda al visor de diapositivas de la clase: `/my/classes/{offering}/viewer` que renderiza `Study/CourseViewer` con el curso correspondiente (autorizado mediante `SchedulePolicy::viewOwn`).

3. **Frontend Módulo Clases Docente (`resources/js/pages/Teacher/Classes.vue`)**:
   - Grid de clases asignadas renderizadas con `CourseCard.vue` con badge de clase/horario y botón "Entrar a clase / Ver lecciones".
   - Empty state si el docente no tiene clases asignadas.

4. **Rediseño del Visor de Lecciones (`resources/js/pages/Study/CourseViewer.vue`)**:
   - Transformar la vista para exhibir una única diapositiva activa:
     - `currentLessonId` o `currentLessonIndex` reactivo.
     - Barra de navegación inferior/superior con botones "Anterior" y "Siguiente" con soporte para teclado (flechas).
     - Drawer o panel lateral colapsable con el listado de unidades y lecciones para salto directo e indicación visual de lección activa y completada.
     - Mantenimiento del botón "Marcar como completada" (solo si es estudiante).
     - Si es docente: indicador visual de "Modo Presentación / Clase" y botón para volver a sus Clases.
