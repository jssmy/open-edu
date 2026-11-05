# Tasks: Módulo de Mis Clases del Estudiante con Restricciones de Fechas y Estado

**Input**: Design documents from `/specs/012-student-my-classes/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/student-classes-api.md`

## Organization: `[ID] [P?] [Story] Description`
- **[P]**: Ejecución en paralelo posible (sin dependencias directas de archivo).
- **[Story]**: Mapeo a historias de usuario (`US1`, `US2`, `US3`, `US4`).

---

## Phase 1: Setup & Prerrequisitos Compartidos

**Purpose**: Preparar la estructura base de clases de estudiante y rutas en el backend y frontend.

- [x] T001 [P] Crear el caso de uso `GetStudentClasses` en `app/Modules/Scheduling/Application/GetStudentClasses.php` para obtener ofertas matriculadas con docentes, grupo, fechas, progreso y estado.
- [x] T002 [P] Crear el controlador `StudentClassController` en `app/Modules/Scheduling/Infrastructure/Http/StudentClassController.php` con método `index` para retornar Inertia `Student/MyClasses`.
- [x] T003 Configurar enrutamiento en `routes/web.php` para `/my/classes` diferenciando rol estudiante y agregando redirección 302 de `/my/courses` hacia `/my/classes` para estudiantes.

---

## Phase 2: Foundational (Políticas y Restricciones Temporales Base)

**Purpose**: Reglas de negocio de acceso y calendario que bloquean o habilitan las historias de usuario.

- [x] T004 Actualizar `CourseAccessPolicy` en `app/Modules/Enrollments/Infrastructure/Policies/CourseAccessPolicy.php` para verificar si la matrícula del estudiante tiene `starts_on > now()` y denegar acceso a cursos no iniciados.
- [x] T005 Actualizar `StudyController::complete` en `app/Modules/Progress/Infrastructure/Http/StudyController.php` para rechazar con `403` si la oferta asociada al estudiante ya finalizó (`ends_on < now()`).
- [x] T006 [P] Actualizar `AssessmentPolicy` en `app/Modules/Assessments/Infrastructure/Policies/AssessmentPolicy.php` en los métodos `take` y `submit` para rechazar intentos en cursos finalizados.

---

## Phase 3: User Story 1 - Reemplazo de "Mis Cursos" por "Mis Clases" y Vista Priorizada (Priority: P1) 🎯 MVP

**Goal**: El estudiante ingresa a `/my/classes`, ve sus clases matriculadas con `CourseCard`, y por defecto se priorizan las clases ya iniciadas con filtros por estado.

### Tests para User Story 1
- [x] T007 [P] [US1] Feature test de redirección `/my/courses` -> `/my/classes` y listado de clases en `tests/Feature/Study/StudentClassesTest.php`.
- [x] T008 [P] [US1] Unit/Component test para `Student/MyClasses.vue` y filtrado por estado en `resources/js/__tests__/StudentClasses.test.ts`.

### Implementación para User Story 1
- [x] T009 [US1] Actualizar la navegación principal en `resources/js/layouts/AppLayout.vue` y accesos directos en `resources/js/pages/Dashboard.vue` sustituyendo "Mis cursos" por "Mis clases" (`/my/classes`).
- [x] T010 [US1] Crear la página Vue `resources/js/pages/Student/MyClasses.vue` reutilizando `CourseCard`, mostrando código de grupo, modalidad, fechas de inicio/fin, barra de progreso y selector de filtros por estado (`in_progress`, `scheduled`, `finished`, `all`).
- [x] T011 [US1] Conectar el ordenamiento en `GetStudentClasses.php` para priorizar clases en curso (`in_progress`), seguidas de programadas (`scheduled`) y finalizadas (`finished`).

**Checkpoint**: El estudiante puede acceder a `/my/classes`, ver sus clases activas con `CourseCard` y filtrar por estado.

---

## Phase 4: User Story 2 - Resumen de Avisos: Clases Próximas y Comenzadas (Priority: P2)

**Goal**: Mostrar dos secciones o carruseles de tarjetas compactas en la parte superior de `/my/classes` con hasta 5 clases comenzadas y hasta 5 próximas a empezar.

### Tests para User Story 2
- [x] T012 [P] [US2] Feature test en `tests/Feature/Study/StudentClassesTest.php` validando los arreglos `started_classes` (tope 5) y `upcoming_classes` (tope 5).
- [x] T013 [P] [US2] Test en `resources/js/__tests__/StudentClasses.test.ts` verificando el renderizado de los dos bloques de aviso superiores.

### Implementación para User Story 2
- [x] T014 [US2] En `GetStudentClasses.php`, extraer y calcular los dos conjuntos de avisos: `started_classes` (`starts_on <= hoy <= ends_on`, máx 5) y `upcoming_classes` (`starts_on > hoy` ordenadas por fecha más cercana, máx 5).
- [x] T015 [US2] En `resources/js/pages/Student/MyClasses.vue`, diseñar los dos bloques/carruseles compactos superiores con diseño responsivo antes de la grilla principal.

**Checkpoint**: El estudiante visualiza proactivamente hasta 5 clases comenzadas y 5 próximas a empezar al cargar la vista.

---

## Phase 5: User Story 3 - Bloqueo de Acceso Previo a la Fecha de Inicio (Priority: P1)

**Goal**: Clases con `starts_on > hoy` muestran botón deshabilitado con badge "Inicia el DD/MM/AAAA" en `/my/classes` y rechazan peticiones directas al visor con `403`.

### Tests para User Story 3
- [x] T016 [P] [US3] Feature test en `tests/Feature/Study/CourseTemporalRestrictionsTest.php` comprobando que `GET /my/courses/{course}` responde `403 Forbidden` cuando `starts_on > hoy`.

### Implementación para User Story 3
- [x] T017 [US3] Adaptar la tarjeta de clase en `resources/js/pages/Student/MyClasses.vue` para inhabilitar el botón de acción y renderizar el badge con la fecha de inicio cuando `can_access === false` (`starts_on > hoy`).
- [x] T018 [US3] Ajustar `StudyController::show` para devolver mensaje explícito de bloqueo si la clase aún no inicia.

**Checkpoint**: Es imposible acceder al contenido de un curso antes de su fecha programada de inicio.

---

## Phase 6: User Story 4 - Modo Lectura en Cursos Finalizados (Priority: P2)

**Goal**: Clases con `ends_on < hoy` permiten consultar contenidos en el visor, pero muestran un banner superior, ocultan botones de completar lecciones y bloquean rendición de evaluaciones.

### Tests para User Story 4
- [x] T019 [P] [US4] Feature test en `tests/Feature/Study/CourseTemporalRestrictionsTest.php` validando que cursos finalizados permiten `GET /my/courses/{course}` pero bloquean `POST /lessons/{lesson}/complete` y `POST /assessments/{assessment}/take` con 403.
- [x] T020 [P] [US4] Component test en `resources/js/__tests__/CourseViewerReadOnly.test.ts` validando la presencia del banner superior y la ausencia de botones de mutación en modo consulta.

### Implementación para User Story 4
- [x] T021 [US4] En `StudyController::show`, inyectar las flags `is_finished` y `ends_on` en los datos del curso pasados a `Study/CourseViewer.vue`.
- [x] T022 [US4] En `resources/js/pages/Study/CourseViewer.vue`, agregar banner informativo superior *"Este curso finalizó el DD/MM/AAAA. Modo de consulta"*, y ocultar condicionalmente los botones para marcar lección como completada y para rendir evaluaciones.

**Checkpoint**: Al terminar el periodo del curso, los contenidos permanecen accesibles pero inmutables.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [x] T023 [P] Ejecutar linter y formateador `vendor/bin/pint --format agent` en todos los archivos PHP modificados.
- [x] T024 [P] Ejecutar `npm run typecheck` y `npm test` para asegurar compatibilidad de TypeScript y tests de interfaz.
- [x] T025 Ejecutar suite completa de tests de integración (`vendor/bin/pest tests/Feature/Study/`).
- [x] T026 Actualizar la documentación y notas de requerimiento en Obsidian si surgen ajustes finales.

