# Tasks: Sistema de Gestión de Aprendizaje (LMS)

**Input**: Design documents from `/specs/001-lms-learning-platform/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/web-routes.md

**Tests**: Incluidos porque el principio VII de la constitución (Calidad y Pruebas) los exige. Las pruebas de cada historia se escriben antes de la implementación y deben fallar primero.

**Organization**: Tareas agrupadas por historia de usuario para implementación y prueba independientes.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Se puede ejecutar en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario (US1–US6)

## Path Conventions

Monolito Laravel: `app/Modules/<Módulo>/{Domain,Application,Infrastructure}`, frontend en `resources/js/`, pruebas en `tests/` y `resources/js/__tests__/` (ver plan.md).

---

## Phase 1: Setup (Shared Infrastructure)

- [X] T001 Crear el proyecto Laravel 13 (PHP 8.3) en la raíz del repositorio y configurar `.env.example` con PostgreSQL
- [X] T002 Instalar Inertia.js (servidor y cliente Vue 3), Vite y TypeScript; configurar `resources/js/app.ts` y `tsconfig.json`
- [X] T003 [P] Instalar y configurar Tailwind CSS en `tailwind.config.ts` y `resources/css/app.css`
- [X] T004 [P] Configurar Laravel Pint (PSR-12), ESLint y Prettier en `pint.json`, `eslint.config.js`, `.prettierrc`
- [X] T005 [P] Configurar Pest en `tests/Pest.php`, Vitest en `vitest.config.ts` y Playwright en `playwright.config.ts`
- [X] T006 Crear la estructura de módulos vacía `app/Modules/{Identity,Courses,Students,Enrollments,Assessments,Progress,Reports}/{Domain,Application,Infrastructure}` y `app/Shared/`
- [X] T007 Registrar el autoload de `App\Modules` y un `ModuleServiceProvider` por módulo en `app/Providers/` para enlazar puertos con adaptadores
- [X] T008 [P] Configurar el workflow de CI (linters, análisis estático, Pest, Vitest) en `.github/workflows/ci.yml`

---

## Phase 2: Foundational (Blocking Prerequisites)

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase.

- [X] T009 Instalar spatie/laravel-permission y publicar su migración en `database/migrations/`
- [X] T010 Crear la migración de `users` en `database/migrations/` con name, email (único), password, must_change_password (bool), is_active (bool), timestamps
- [X] T011 [P] Crear la migración de `audit_logs` en `database/migrations/` con user_id, event, subject, metadata, created_at
- [X] T012 [P] Crear la excepción base de dominio y el mapeo a respuestas (422/403/404) en `app/Shared/Exceptions/DomainException.php` y `bootstrap/app.php`
- [X] T013 [P] Implementar el servicio de auditoría en `app/Shared/Audit/AuditLogger.php` (sin datos sensibles en el registro)
- [X] T014 Crear el seeder de roles y permisos (administrador, gestor_de_cursos, docente, estudiante) y un Administrador inicial en `database/seeders/RolesAndPermissionsSeeder.php`
- [X] T015 [P] Crear el layout base Inertia con navegación por rol en `resources/js/layouts/AppLayout.vue` y el middleware `HandleInertiaRequests` compartiendo usuario y permisos
- [X] T016 [P] Definir los tokens del sistema de diseño (color, tipografía, espaciado, radios, sombras, breakpoints) en `resources/js/design-system/tokens.ts` y conectarlos a `tailwind.config.ts`
- [X] T017 [P] Crear los componentes base del sistema de diseño con props tipadas en `resources/js/design-system/`: `Button.vue`, `Input.vue`, `Select.vue`, `Textarea.vue`, `Checkbox.vue`
- [X] T018 [P] Crear los componentes base de feedback en `resources/js/design-system/`: `Alert.vue`, `Spinner.vue`, `EmptyState.vue`, `Modal.vue`, `Toast.vue`
- [X] T019 [P] Crear los componentes base de datos en `resources/js/design-system/`: `Table.vue`, `Pagination.vue`, `Badge.vue`, `Card.vue`, `FormField.vue`
- [X] T020 [P] Documentar variantes, estados y accesibilidad de cada componente base en `resources/js/design-system/README.md`
- [X] T021 [P] Pruebas Vitest de los componentes base del sistema de diseño en `resources/js/__tests__/design-system/`
- [X] T022 [P] Crear los tipos TypeScript compartidos y el cliente de servicios en `resources/js/services/http.ts`
- [X] T114 [P] Pruebas de arquitectura de Pest que verifiquen que `app/Modules/*/Domain` y `app/Modules/*/Application` no importan `Illuminate\*` ni Eloquent, en `tests/Arch/HexagonalTest.php`
- [X] T115 [P] Regla de ESLint que prohíba colores y valores de estilo fijos fuera de `resources/js/design-system/`, en `eslint.config.js`
- [X] T116 [P] Prueba de matriz rol × ruta basada en `contracts/web-routes.md` (toda operación no permitida responde 403, SC-003) en `tests/Feature/Security/RoleRouteMatrixTest.php`

**Checkpoint**: Base lista; pueden comenzar las historias.

---

## Phase 3: User Story 1 - Acceso seguro y gestión de usuarios, roles y permisos (Priority: P1) 🎯 MVP

**Goal**: Iniciar/cerrar sesión, recuperar contraseña, gestionar usuarios, roles y permisos.

**Independent Test**: El Administrador crea un usuario con rol; este inicia sesión y solo ve lo permitido; recupera su contraseña.

### Tests for User Story 1

- [X] T023 [P] [US1] Prueba Feature de login, logout, credenciales inválidas, usuario inactivo y límite de intentos (429) en `tests/Feature/Identity/AuthenticationTest.php`
- [X] T024 [P] [US1] Prueba Feature de recuperación de contraseña (enlace vencido o ya usado) en `tests/Feature/Identity/PasswordResetTest.php`
- [X] T025 [P] [US1] Prueba Feature de cambio obligatorio de contraseña en primer acceso en `tests/Feature/Identity/ForcePasswordChangeTest.php`
- [X] T026 [P] [US1] Prueba Feature de gestión de usuarios y de rechazo (403) para roles sin permiso en `tests/Feature/Identity/UserManagementTest.php`
- [X] T027 [P] [US1] Prueba Feature de edición de permisos por rol en `tests/Feature/Identity/RolePermissionsTest.php`
- [X] T028 [P] [US1] Pruebas unitarias de casos de uso de usuarios sin framework ni BD en `tests/Unit/Identity/`
- [X] T117 [P] [US1] Prueba Feature de que un cambio de rol surte efecto en la siguiente petición con sesión abierta en `tests/Feature/Identity/RoleChangeSessionTest.php`

### Implementation for User Story 1

- [X] T029 [P] [US1] Crear entidad y value objects de usuario/rol en `app/Modules/Identity/Domain/`
- [X] T030 [P] [US1] Definir el puerto `UserRepository` en `app/Modules/Identity/Domain/UserRepository.php`
- [X] T031 [US1] Implementar los casos de uso CreateUser, UpdateUser, SetUserStatus y ChangeUserRole (auditando cambios de rol) en `app/Modules/Identity/Application/`
- [X] T032 [US1] Implementar el caso de uso UpdateRolePermissions en `app/Modules/Identity/Application/UpdateRolePermissions.php`
- [X] T033 [US1] Implementar `EloquentUserRepository` y el modelo `User` en `app/Modules/Identity/Infrastructure/`
- [X] T034 [US1] Implementar login/logout con rate limiting (máximo 5 intentos por minuto por cuenta e IP, respuesta 429) y auditoría de inicios de sesión en `app/Modules/Identity/Infrastructure/Http/AuthController.php`
- [X] T035 [US1] Implementar recuperación de contraseña con vigencia limitada y el cambio obligatorio en primer acceso (middleware) en `app/Modules/Identity/Infrastructure/Http/`
- [X] T036 [US1] Crear Form Requests, Policies y controladores de usuarios y roles en `app/Modules/Identity/Infrastructure/Http/`
- [X] T037 [US1] Registrar las rutas de Identity según contracts/web-routes.md en `routes/web.php`
- [X] T038 [P] [US1] Crear las páginas Login, ForgotPassword, ResetPassword y ChangePassword, con estados de carga, vacío, error y mensajes de éxito, en `resources/js/pages/Auth/`
- [X] T039 [P] [US1] Crear las páginas de usuarios (listado, crear/editar) y de roles/permisos, con estados de carga, vacío, error y mensajes de éxito, en `resources/js/pages/Users/` y `resources/js/pages/Roles/`
- [X] T040 [US1] Prueba E2E de login, cambio de contraseña inicial y restricción por rol en `tests/e2e/auth.spec.ts`

**Checkpoint**: US1 funcional y probable por sí sola.

---

## Phase 4: User Story 2 - Gestión de cursos y estructura de contenidos (Priority: P1)

**Goal**: CRUD de cursos, publicar/desactivar, asignar docentes, unidades y lecciones con contenidos.

**Independent Test**: Crear y publicar un curso con unidades y lecciones; guardar y reabrir formato enriquecido, una imagen con texto alternativo y un video permitido; comprobar que editar cursos y lecciones ocurre en páginas independientes, que una URL HTTPS de video externo se conserva, y que un guardado con versión obsoleta se rechaza sin reemplazar cambios recientes.

### Tests for User Story 2

- [X] T041 [P] [US2] Prueba Feature de CRUD de cursos, publicar/desactivar y confirmación al eliminar con inscripciones en `tests/Feature/Courses/CourseManagementTest.php`
- [X] T042 [P] [US2] Prueba Feature de unidades/lecciones, reordenamiento y archivado (no eliminar con progreso) en `tests/Feature/Courses/ContentManagementTest.php`
- [X] T043 [P] [US2] Prueba Feature de alcance del docente (solo cursos asignados) y subida de archivos en `tests/Feature/Courses/TeacherScopeTest.php`
- [X] T044 [P] [US2] Pruebas unitarias de reglas de curso y lección sin framework ni BD en `tests/Unit/Courses/`
- [X] T118 [P] [US2] Prueba Feature de que al desasignar a un docente se conservan sus evaluaciones en `tests/Feature/Courses/TeacherUnassignTest.php`
- [X] T119 [P] [US2] Prueba Feature de rechazo de contenido en formato o tamaño no admitido en `tests/Feature/Courses/ContentUploadTest.php`
- [X] T120 [P] [US2] Prueba Feature de eliminación lógica de un curso con inscripciones: conserva historial y lo oculta de los listados en `tests/Feature/Courses/CourseSoftDeleteTest.php`

### Implementation for User Story 2

- [X] T045 [US2] Crear la migración de `courses` en `database/migrations/` con status (`draft` | `published` | `deactivated`) y deleted_at (eliminación lógica que conserva el historial)
- [X] T046 [US2] Crear las migraciones de `course_teacher` (único por par), `units` (position) y `lessons` en `database/migrations/` con content_type (`text` | `document` | `image` | `link` | `video`) e is_archived
- [X] T047 [P] [US2] Crear entidades Course, Unit, Lesson y la regla "con progreso registrado solo puede archivarse, no eliminarse" en `app/Modules/Courses/Domain/`
- [X] T048 [P] [US2] Definir los puertos `CourseRepository` y `FileStorage` en `app/Modules/Courses/Domain/`
- [X] T049 [US2] Implementar los casos de uso CreateCourse, UpdateCourse, DeleteCourse, SetCourseStatus y AssignTeachers en `app/Modules/Courses/Application/`
- [X] T050 [US2] Implementar los casos de uso de unidades y lecciones (crear, editar, reordenar, archivar, eliminar) en `app/Modules/Courses/Application/`
- [X] T051 [US2] Implementar modelos Eloquent, `EloquentCourseRepository` y `LocalFileStorage` (Storage de Laravel, límite de tamaño configurable) en `app/Modules/Courses/Infrastructure/`
- [X] T052 [US2] Crear Form Requests, `CoursePolicy` (alcance por docente asignado) y controladores en `app/Modules/Courses/Infrastructure/Http/`
- [X] T053 [US2] Registrar las rutas de Cursos y Contenido en `routes/web.php`
- [X] T054 [P] [US2] Crear composable `useCourses` y servicio en `resources/js/composables/useCourses.ts` y `resources/js/services/courses.ts`
- [X] T055 [P] [US2] Crear las páginas de cursos (listado, crear/editar, detalle), con estados de carga, vacío, error y mensajes de éxito, en `resources/js/pages/Courses/`
- [X] T056 [P] [US2] Crear el componente de unidades/lecciones con reordenamiento y el editor de contenido por tipo en `resources/js/components/courses/`
- [X] T057 [US2] Prueba E2E de creación y publicación de un curso con contenido en `tests/e2e/courses.spec.ts`

### Editor enriquecido, imágenes y video de presentación (FR-030–FR-034)

- [X] T122 [US2] Instalar Tiptap v3 para Vue con `@tiptap/vue-3`, `@tiptap/pm`, `@tiptap/starter-kit` y `@tiptap/extension-image` en `package.json` y `package-lock.json`; mantener `allowBase64` desactivado.
- [X] T123 [P] [US2] Añadir pruebas Feature para guardar y recuperar documentos JSON Tiptap válidos, rechazar nodos/marcas no permitidos, HTML ejecutable y enlaces que no sean HTTP/HTTPS en `tests/Feature/Courses/RichContentTest.php`.
- [X] T124 [P] [US2] Añadir pruebas Feature para carga y lectura de imágenes JPEG, PNG y WebP de máximo 5120 KB, rechazo de MIME/tamaño inválido, texto alternativo requerido o `alt=""` decorativo y autorización por curso en `tests/Feature/Courses/ContentImageTest.php`.
- [X] T125 [P] [US2] Añadir pruebas Vitest para la barra de herramientas accesible por teclado, etiquetas accesibles, foco visible, inserción de imagen con alternativa descriptiva o decorativa y rechazo de protocolos inseguros en `resources/js/__tests__/RichTextEditor.test.ts`.
- [X] T126 [P] [US2] Crear migraciones para `courses.description` y `courses.objectives` JSONB, `intro_video_provider` nullable (`youtube` | `vimeo`), `intro_video_id` nullable, `lessons.content` JSONB y `course_media_images` con course_id, storage_path privado, mime_type, size_bytes y uploaded_by en `database/migrations/`; convertir el texto existente a documentos Tiptap sin perder su contenido.
- [X] T127 [P] [US2] Implementar un validador backend reutilizable del esquema de documentos Tiptap que permita solo párrafos, negrita, cursiva, encabezados, listas, citas, enlaces e imágenes; validar atributos `alt` y protocolos HTTP/HTTPS para enlaces y solo HTTPS para imágenes en `app/Shared/RichText/RichTextDocumentValidator.php`.
- [X] T128 [US2] Actualizar persistencia, modelos y Form Requests de cursos y lecciones para validar y guardar descripción, objetivos y contenido textual como documentos Tiptap JSON; normalizar `intro_video_url` a proveedor e ID tras validar HTTPS y hosts exactos de YouTube/Vimeo en `app/Modules/Courses/Infrastructure/Models/Course.php`, `app/Modules/Courses/Infrastructure/Models/Lesson.php`, `app/Modules/Courses/Infrastructure/Http/Requests/SaveCourseRequest.php`, `app/Modules/Courses/Infrastructure/Http/Requests/SaveLessonRequest.php` y `app/Modules/Courses/Infrastructure/Http/CourseController.php`.
- [X] T129 [US2] Implementar carga y lectura autenticada de imágenes de contenido usando almacenamiento privado, nombre aleatorio, MIME real permitido y máximo 5120 KB; autorizar Admin/Gestor, Docente asignado y Estudiante con inscripción activa en curso publicado, y responder con MIME validado y `X-Content-Type-Options: nosniff` en `app/Modules/Courses/Domain/FileStorage.php`, `app/Modules/Courses/Infrastructure/LocalFileStorage.php`, `app/Modules/Courses/Infrastructure/Http/ContentImageController.php`, `app/Modules/Courses/Infrastructure/Http/Requests/SaveContentImageRequest.php` y `routes/web.php`.
- [X] T130 [US2] Crear el componente reutilizable Tiptap con formato básico, inserción de imagen subida o URL HTTPS, captura explícita de texto alternativo/decorativa, mensajes de error, controles accesibles y distribución adaptable a móvil en `resources/js/components/editor/RichTextEditor.vue`.
- [X] T131 [US2] Reemplazar los campos de texto extenso de descripción, objetivos y contenido textual de lección por `RichTextEditor.vue`, conservar controles simples para nombres/títulos y conectar la carga de imágenes al curso correspondiente en `resources/js/pages/Courses/Index.vue`, `resources/js/pages/Courses/Show.vue` y `resources/js/components/courses/CourseContentEditor.vue`.
- [X] T132 [US2] Mostrar la descripción, los objetivos y el video de presentación en la ficha del curso usando un visor Tiptap de solo lectura y un iframe construido solo desde el proveedor/ID validados; usar `youtube-nocookie.com`, `player.vimeo.com` y `referrerpolicy="no-referrer"` para imágenes externas en `resources/js/components/editor/RichTextContent.vue` y `resources/js/pages/Courses/Show.vue`.
- [X] T133 [US2] Ampliar la prueba E2E de cursos para crear/editar formato e imágenes, comprobar persistencia al reabrir, probar el video permitido y verificar editor por teclado y viewport de 375 px en `tests/e2e/courses.spec.ts`.

### Páginas de edición, cambios sin guardar y concurrencia (FR-035–FR-036)

- [X] T144 [P] [US2] Añadir pruebas Feature que verifiquen rechazo HTTP 409 de actualizaciones obsoletas de cursos y lecciones, sin sobrescribir los datos actuales, en `tests/Feature/Courses/ConcurrentContentUpdateTest.php`.
- [X] T145 [P] [US2] Añadir pruebas Feature para aceptar enlaces de video de lección HTTPS y rechazar enlaces HTTP o archivos de video en `tests/Feature/Courses/LessonVideoLinkTest.php`.
- [X] T146 [P] [US2] Añadir pruebas Vitest para confirmar que el guard de cambios sin guardar bloquea navegación Inertia y activa `beforeunload` solo cuando el formulario está modificado en `resources/js/__tests__/UnsavedChangesGuard.test.ts`.
- [X] T147 [US2] Añadir escenarios Playwright que verifiquen que crear/editar cursos y lecciones se abre en páginas independientes y que las opciones de quedarse o descartar funcionan en `tests/e2e/courses.spec.ts`.
- [X] T148 [P] [US2] Crear una migración que agregue `lock_version` entero no nulo, inicializado para registros existentes, a `courses` y `lessons` en `database/migrations/`.
- [X] T149 [US2] Exigir la versión enviada por el cliente e implementar comparación e incremento atómicos de `lock_version` para actualizaciones de curso y lección; responder 409 sin guardar ante versión obsoleta en `app/Modules/Courses/Infrastructure/Http/CourseController.php`, `app/Modules/Courses/Infrastructure/Http/ContentController.php`, `app/Modules/Courses/Infrastructure/Http/Requests/SaveCourseRequest.php` y `app/Modules/Courses/Infrastructure/Http/Requests/SaveLessonRequest.php`.
- [X] T150 [US2] Validar que el video de lección sea una URL HTTPS y tratarlo como enlace externo, sin aceptar subida de archivos de video, en `app/Modules/Courses/Infrastructure/Http/Requests/SaveLessonRequest.php`, `app/Modules/Courses/Infrastructure/Http/ContentController.php` y `tests/Feature/Courses/LessonVideoLinkTest.php`.
- [X] T151 [US2] Presentar creación y edición de curso y lección en páginas Inertia independientes, retirar el editor de cualquier modal o diálogo y mostrar el conflicto HTTP 409 sin perder los datos locales en `resources/js/pages/Courses/Index.vue`, `resources/js/pages/Courses/Show.vue`, `resources/js/components/courses/CourseContentEditor.vue` y `routes/web.php`.
- [X] T152 [US2] Crear `useUnsavedChangesGuard` con interceptores de navegación Inertia y `beforeunload`, y conectarlo a los formularios de curso y lección para permitir quedarse o confirmar descarte en `resources/js/composables/useUnsavedChangesGuard.ts`, `resources/js/pages/Courses/Index.vue`, `resources/js/pages/Courses/Show.vue` y `resources/js/components/courses/CourseContentEditor.vue`.

**Checkpoint**: US1 y US2 funcionan de forma independiente.

---

## Phase 5: User Story 3 - Gestión de estudiantes e inscripción a cursos (Priority: P1)

**Goal**: Registrar/administrar estudiantes e inscribirlos en cursos con estados gestionables.

**Independent Test**: Registrar un estudiante, inscribirlo en dos cursos y verificar ambas vistas.

### Tests for User Story 3

- [X] T058 [P] [US3] Prueba Feature de registro, edición y activación/desactivación de estudiantes en `tests/Feature/Students/StudentManagementTest.php`
- [X] T059 [P] [US3] Prueba Feature de inscripción, duplicado rechazado, curso no publicado y consultas cruzadas en `tests/Feature/Enrollments/EnrollmentTest.php`
- [X] T060 [P] [US3] Pruebas unitarias de transiciones de estado de inscripción (active ↔ suspended; active/suspended → cancelled) en `tests/Unit/Enrollments/`

### Implementation for User Story 3

- [X] T061 [US3] Crear la migración de `students` en `database/migrations/` con user_id (único), documento/identificador opcional, teléfono opcional, is_active
- [X] T062 [US3] Crear la migración de `enrollments` en `database/migrations/` con status (`active` | `suspended` | `cancelled`), enrolled_at y restricción única (student_id, course_id)
- [X] T063 [P] [US3] Crear entidad Student y casos de uso RegisterStudent (usuario con contraseña temporal y must_change_password), UpdateStudent y SetStudentStatus en `app/Modules/Students/`
- [X] T064 [P] [US3] Crear entidad Enrollment con la máquina de estados y el puerto `EnrollmentRepository` en `app/Modules/Enrollments/Domain/`
- [X] T065 [US3] Implementar los casos de uso EnrollStudent (rechaza duplicados y cursos no publicados), ChangeEnrollmentStatus y las consultas por curso/estudiante, con auditoría de inscripciones, en `app/Modules/Enrollments/Application/`
- [X] T066 [US3] Implementar modelos, repositorios Eloquent, Form Requests, Policies y controladores de Students y Enrollments en sus `Infrastructure/`
- [X] T067 [US3] Registrar las rutas de Estudiantes e Inscripciones en `routes/web.php`
- [X] T077 [US3] Implementar la política de acceso del estudiante (inscripción `active` + curso `published`; `cancelled` solo historial) en `app/Modules/Enrollments/Infrastructure/Policies/CourseAccessPolicy.php`
- [X] T068 [P] [US3] Crear las páginas de estudiantes (listado, formulario, detalle con cursos), con estados de carga, vacío, error y mensajes de éxito, en `resources/js/pages/Students/`
- [X] T069 [P] [US3] Crear la pestaña de inscritos y el diálogo de inscripción en `resources/js/pages/Courses/Enrollments.vue` y `resources/js/components/enrollments/EnrollDialog.vue`
- [X] T070 [US3] Prueba E2E de registro e inscripción de un estudiante en `tests/e2e/enrollments.spec.ts`

**Checkpoint**: US1–US3 operativas; MVP administrativo completo.

---

## Phase 6: User Story 4 - El estudiante estudia el contenido de sus cursos (Priority: P2)

**Goal**: El estudiante navega sus cursos y completa lecciones.

**Independent Test**: Estudiante inscrito en un curso publicado ve el formato enriquecido y las imágenes autorizadas de una lección, la completa y su progreso se actualiza; usuarios sin acceso no pueden obtener los medios.

### Tests for User Story 4

- [X] T071 [P] [US4] Prueba Feature de acceso por inscripción activa y curso publicado; denegación para suspendida, cancelada, no inscrito y curso desactivado en `tests/Feature/Progress/StudentAccessTest.php`
- [X] T072 [P] [US4] Prueba Feature de completar lección (idempotente) y cálculo de progreso sin lecciones archivadas en `tests/Feature/Progress/LessonCompletionTest.php`
- [X] T073 [P] [US4] Pruebas unitarias del cálculo de progreso en `tests/Unit/Progress/`

### Implementation for User Story 4

- [X] T074 [US4] Crear la migración de `lesson_progress` en `database/migrations/` con student_id, lesson_id, completed_at y restricción única (student_id, lesson_id)
- [X] T075 [P] [US4] Crear dominio y puerto `ProgressRepository` y el servicio de cálculo de progreso en `app/Modules/Progress/Domain/`
- [X] T076 [US4] Implementar los casos de uso CompleteLesson y GetCourseProgress en `app/Modules/Progress/Application/`
- [X] T078 [US4] Implementar repositorio Eloquent y controladores de estudio (`/my/courses`, `/lessons/{id}/complete`) en `app/Modules/Progress/Infrastructure/`
- [X] T079 [US4] Registrar las rutas de Estudio en `routes/web.php`
- [X] T080 [P] [US4] Crear las páginas `MyCourses.vue`, `CourseViewer.vue` y el visor de lección por tipo de contenido, con estados de carga, vacío, error y mensajes de éxito, en `resources/js/pages/Study/`
- [X] T081 [P] [US4] Crear el componente de barra de progreso en `resources/js/components/progress/ProgressBar.vue`
- [X] T082 [US4] Prueba E2E del recorrido del estudiante en `tests/e2e/study.spec.ts`
- [X] T134 [P] [US4] Añadir pruebas Feature y E2E que confirmen que un estudiante con inscripción activa en curso publicado ve el formato enriquecido y las imágenes subidas, mientras que estudiantes no inscritos, suspendidos o con curso no publicado no pueden leer esas imágenes en `tests/Feature/Progress/StudentAccessTest.php` y `tests/e2e/study.spec.ts`.
- [X] T135 [US4] Integrar el visor Tiptap en modo solo lectura al contenido textual de las lecciones, sin `v-html`, y asegurar que la vista de estudio carga el contenido y las imágenes privadas solo mediante rutas autorizadas en `resources/js/pages/Study/CourseViewer.vue` y `resources/js/components/editor/RichTextContent.vue`.

---

## Phase 7: User Story 5 - Evaluaciones (Priority: P2)

**Goal**: Crear evaluaciones con preguntas y alternativas; el estudiante las rinde y obtiene resultado.

**Independent Test**: Crear una evaluación de 3 preguntas con instrucciones y enunciados enriquecidos en una página independiente, reabrirla, resolverla como estudiante y verificar formato, puntaje y resultado; comprobar que un guardado concurrente con versión obsoleta se rechaza sin reemplazar la evaluación ni sus preguntas y alternativas guardadas.

### Tests for User Story 5

- [X] T083 [P] [US5] Prueba Feature de creación de evaluaciones y de rechazo de publicación sin pregunta válida en `tests/Feature/Assessments/AssessmentManagementTest.php`
- [X] T084 [P] [US5] Prueba Feature de rendir evaluación: cálculo de puntaje, aprobado/reprobado, intentos agotados y acceso denegado en `tests/Feature/Assessments/TakeAssessmentTest.php`
- [X] T085 [P] [US5] Pruebas unitarias de calificación y de `attempt_number ≤ max_attempts` en `tests/Unit/Assessments/`
- [X] T121 [P] [US5] Prueba Feature de curso desactivado durante un intento: se puede enviar el intento en curso y no se permiten nuevos, en `tests/Feature/Assessments/CourseDeactivatedDuringAttemptTest.php`
- [X] T136 [P] [US5] Añadir pruebas Feature para guardar y recuperar instrucciones y enunciados como JSON Tiptap, permitir `instructions` nullable y rechazar estructuras o protocolos no permitidos en `tests/Feature/Assessments/AssessmentRichTextTest.php`.

### Implementation for User Story 5

- [X] T086 [US5] Crear las migraciones de `assessments` en `database/migrations/` con max_attempts (defecto 1), passing_score (entre 0 y total_score), total_score (suma de los puntos de sus preguntas), is_published
- [X] T087 [US5] Crear las migraciones de `questions`, `choices` (is_correct), `attempts` y `attempt_answers` en `database/migrations/`
- [X] T088 [P] [US5] Crear entidades Assessment, Question, Choice y Attempt con las reglas "cada pregunta tiene al menos 2 alternativas y exactamente 1 correcta", "el resultado oficial = último intento" y "passing_score entre 0 y total_score" en `app/Modules/Assessments/Domain/`
- [X] T089 [US5] Implementar los casos de uso CreateAssessment, UpdateAssessment, PublishAssessment y gestión de preguntas en `app/Modules/Assessments/Application/`
- [X] T090 [US5] Implementar el caso de uso SubmitAttempt (calificación automática síncrona, validación de intentos y acceso) y GetResults en `app/Modules/Assessments/Application/`
- [X] T091 [US5] Implementar modelos, repositorio Eloquent, Form Requests, Policies y controladores en `app/Modules/Assessments/Infrastructure/`
- [X] T092 [US5] Registrar las rutas de Evaluaciones y Resultados en `routes/web.php`
- [X] T093 [P] [US5] Crear el editor de evaluaciones (preguntas, alternativas, puntajes, nota mínima, intentos), con estados de carga, vacío, error y mensajes de éxito, en `resources/js/pages/Assessments/Edit.vue`
- [X] T094 [P] [US5] Crear las páginas para rendir la evaluación y ver resultados, con estados de carga, vacío, error y mensajes de éxito, en `resources/js/pages/Assessments/Take.vue` y `Results.vue`
- [X] T095 [US5] Prueba E2E de crear y rendir una evaluación en `tests/e2e/assessments.spec.ts`
- [X] T137 [US5] Crear migraciones para `assessments.instructions` JSONB nullable y `questions.statement` JSONB, preservando los enunciados existentes como documentos de párrafo Tiptap en `database/migrations/`.
- [X] T138 [US5] Actualizar modelos, repositorio, controlador y `SaveAssessmentRequest` para validar con `RichTextDocumentValidator`, persistir documentos Tiptap para `instructions` nullable y `questions.*.statement`, y conservar preguntas y alternativas existentes en `app/Modules/Assessments/Infrastructure/Models/Assessment.php`, `app/Modules/Assessments/Infrastructure/Models/Question.php`, `app/Modules/Assessments/Infrastructure/EloquentAssessmentRepository.php`, `app/Modules/Assessments/Infrastructure/Http/Requests/SaveAssessmentRequest.php` y `app/Modules/Assessments/Infrastructure/Http/AssessmentController.php`.
- [X] T139 [P] [US5] Reemplazar el enunciado extenso de pregunta e instrucciones de evaluación por el editor reutilizable, conectando la subida de imágenes a la ruta del curso de la evaluación, y mantener campos breves y alternativas como controles simples en `resources/js/pages/Assessments/Edit.vue`.
- [X] T140 [US5] Mostrar instrucciones y enunciados con el visor Tiptap de solo lectura en las vistas de rendición y resultados sin `v-html` en `resources/js/pages/Assessments/Take.vue` y `resources/js/pages/Assessments/Results.vue`.
- [X] T141 [US5] Ampliar la prueba E2E para redactar, guardar, reabrir y consultar formato enriquecido en instrucciones y enunciados, y validar su operación por teclado en `tests/e2e/assessments.spec.ts`.
- [X] T153 [P] [US5] Añadir pruebas Feature para rechazar con 409 la actualización obsoleta de una evaluación, incluyendo modificaciones concurrentes de preguntas y alternativas, y preservar los datos guardados en `tests/Feature/Assessments/ConcurrentAssessmentUpdateTest.php`.
- [X] T154 [US5] Añadir escenarios Playwright que confirmen que el editor de evaluaciones está en una página independiente y que la navegación con cambios sin guardar permite quedarse o confirmar descarte en `tests/e2e/assessments.spec.ts`.
- [X] T155 [P] [US5] Crear una migración que agregue `lock_version` entero no nulo, inicializado para registros existentes, a `assessments` en `database/migrations/`.
- [X] T156 [US5] Exigir la versión enviada por el cliente e implementar comparación e incremento atómicos de `lock_version` para actualizaciones de evaluaciones, preguntas y alternativas; garantizar que la edición sea una página independiente, integrar el guard de cambios sin guardar, responder 409 en versiones obsoletas y mostrar el conflicto en `app/Modules/Assessments/Infrastructure/EloquentAssessmentRepository.php`, `app/Modules/Assessments/Infrastructure/Http/AssessmentController.php`, `app/Modules/Assessments/Infrastructure/Http/Requests/SaveAssessmentRequest.php`, `resources/js/pages/Assessments/Edit.vue` y `routes/web.php`.

---

## Phase 8: User Story 6 - Seguimiento del aprendizaje y reportes (Priority: P3)

**Goal**: Consultar progreso y resultados por curso/estudiante y generar reportes exportables.

**Independent Test**: Con datos de inscripción, progreso y resultados, cada reporte coincide con lo registrado y respeta permisos.

### Tests for User Story 6

- [X] T096 [P] [US6] Prueba Feature de progreso por curso y por estudiante con alcance por rol en `tests/Feature/Reports/ProgressViewTest.php`
- [X] T097 [P] [US6] Prueba Feature de reportes (inscritos, avance, resultados), filtros, exportación CSV y alcance del docente en `tests/Feature/Reports/ReportsTest.php`

### Implementation for User Story 6

- [X] T098 [US6] Implementar los casos de uso de consulta de reportes con filtros por curso, estudiante y estado, y consultas optimizadas sin N+1, en `app/Modules/Reports/Application/`
- [X] T099 [US6] Implementar la exportación CSV en streaming en `app/Modules/Reports/Infrastructure/CsvExporter.php`
- [X] T100 [US6] Implementar controladores, Policies de alcance (docente: cursos asignados; gestor y admin: todos) y rutas de Reportes y Progreso en `app/Modules/Reports/Infrastructure/Http/` y `routes/web.php`
- [X] T101 [P] [US6] Crear las páginas de progreso por curso y "Mi progreso" (incluido el historial de inscripciones canceladas), con estados de carga, vacío, error y mensajes de éxito, en `resources/js/pages/Progress/`
- [X] T102 [P] [US6] Crear las páginas de reportes con filtros, botón de exportar y estados de carga, vacío, error y mensajes de éxito, en `resources/js/pages/Reports/`
- [X] T103 [US6] Prueba E2E de generación y exportación de un reporte en `tests/e2e/reports.spec.ts`

---

## Phase 9: Polish & Cross-Cutting Concerns

- [X] T104 [P] Crear seeders y factories de datos de demostración en `database/seeders/DemoDataSeeder.php` y `database/factories/`
- [X] T105 [P] Revisar accesibilidad WCAG 2.1 AA y responsive de todas las pantallas en `resources/js/pages/` y `tests/e2e/`
- [X] T106 [P] Verificar que no queden estilos ni componentes fuera del sistema de diseño (revisión contra el principio VI) en `resources/js/design-system/tokens.ts` y `resources/js/components/`
- [X] T107 [P] Prueba de rendimiento: reporte de 500 estudiantes < 30 s, resultado de evaluación < 5 s y progreso reflejado en < 1 minuto tras completar una lección (SC-005, SC-006, SC-007) en `tests/Feature/Reports/ReportsTest.php`, `tests/Feature/Assessments/TakeAssessmentTest.php` y `tests/Feature/Progress/LessonCompletionTest.php`
- [ ] T112 [P] Prueba de carga con k6 de 500 usuarios concurrentes: el 95% de las páginas responde en menos de 2 s (SC-008) en `tests/load/concurrency.js` (tres ejecuciones locales con 500 VUs en servidores PHP compartiendo máquina con k6 no cumplen: los servidores de desarrollo dieron p95 2,84–5,74 s y 62,56–73,60% de checks; PHP-FPM dio p95 3,89 s y 9,44% de checks. Repetir con el generador separado y configuración de staging antes de atribuir el resultado a la aplicación)
- [X] T113 [P] Plan y registro de la prueba de usabilidad con 5 estudiantes (SC-004) en `docs/usability-test.md`
- [X] T108 [P] Revisión de seguridad: autorización en cada ruta, CSRF, rate limiting y ausencia de datos sensibles en logs en `routes/web.php`, `app/Modules/` y `storage/logs/`
- [X] T109 [P] Documentar decisiones de arquitectura y variables de entorno en `docs/architecture.md` y `.env.example`
- [X] T110 Verificar que la constitución v1.2.0 (TypeScript en el frontend y páginas independientes para editores) coincide con plan y tareas en `.specify/memory/constitution.md`
- [X] T111 Ejecutar los escenarios de `quickstart.md` y confirmar que pasan todas las pruebas y linters
- [X] T142 [P] Añadir una política CSP que permita `frame-src` solo desde `https://www.youtube-nocookie.com` y `https://player.vimeo.com`, preservando las directivas necesarias de Inertia/Vite en `app/Http/Middleware/ContentSecurityPolicy.php` y `bootstrap/app.php`.
- [ ] T157 Configurar y documentar respaldos recuperables de PostgreSQL y medios privados con cobertura de 24 horas, escribir el procedimiento de recuperación y ejecutar una restauración en staging que demuestre RTO ≤24 h y RPO ≤24 h en `docs/backup-recovery.md`.
- [X] T158 [P] Estandarizar los listados de usuarios y estudiantes (tablas) y cursos (tarjetas con vista previa) (FR-037): paginación y filtros en el servidor, `IconButton`, `Pagination` con rango, `useListFilters` y pruebas Feature en `tests/Feature/Identity/UserManagementTest.php`, `tests/Feature/Students/StudentManagementTest.php` y `tests/Feature/Courses/CourseManagementTest.php`.
- [X] T159 [P] [US2] Portada opcional de curso (FR-038): migración `cover_path`/`cover_mime`, `SetCourseCover`, `DefaultCourseCover` (SVG por defecto), `CourseCoverController` (`GET /courses/{id}/cover`), campo de imagen con vista previa en `Courses/Edit.vue`, tarjetas de `Courses/Index.vue` y pruebas en `tests/Feature/Courses/CourseManagementTest.php`.
- [X] T160 [P] Crear `FileInput.vue` en `resources/js/design-system/` (FR-039) y reemplazar los `input type="file"` de `Courses/Edit.vue`, `Courses/LessonEdit.vue` y `RichTextEditor.vue`, con prueba en `resources/js/__tests__/design-system/components.test.ts`.
- [X] T161 [P] [US2] Añadir en `tests/e2e/courses.spec.ts` escenarios Playwright (FR-037, FR-038, FR-039): listado de cursos en tarjetas con búsqueda, filtro y paginación de 10; crear y editar un curso con portada vía `FileInput` (formulario multipart con `rich_text_encoded`, confirmando que descripción y objetivos se conservan); curso sin portada muestra el SVG por defecto; quitar portada restaura el SVG
- [X] T162 [P] Documentar en `specs/001-lms-learning-platform/quickstart.md` que el despliegue requiere `php artisan migrate` para la migración `add_cover_to_courses_table` (FR-038)
- [X] T163 Ejecutar con Node 24 `npm run build` y `npx playwright test tests/e2e/courses.spec.ts` y confirmar los escenarios 14 y 15 de `quickstart.md`, después de T161
- [ ] T143 Ejecutar, después de completar T157, los escenarios 8–13 de editor, medios, accesibilidad, seguridad, móvil, navegación con cambios sin guardar, ediciones simultáneas y recuperación de `specs/001-lms-learning-platform/quickstart.md`; registrar y corregir cualquier fallo en las pruebas afectadas.

---

## Dependencies & Execution Order

- **Setup (Fase 1)** → **Foundational (Fase 2)** bloquea todas las historias.
- **US1** (P1): inicia tras la Fase 2; no depende de otras historias.
- **US2** (P1): inicia tras la Fase 2; usa la autorización de US1 (T036) para sus Policies.
- **US3** (P1): depende de US1 (usuarios) y US2 (cursos).
- **US4** (P2): depende de US2 y US3.
- **US5** (P2): depende de US2 y US3; reutiliza T077 (US3).
- **US6** (P3): depende de US3, US4 y US5.
- Las tareas nuevas de editor/medios de US2 (T122–T133) habilitan la visualización enriquecida de US4 (T134–T135) y los campos largos de evaluaciones de US5 (T136–T141); T142 y T143 cierran CSP y validación integral.
- Las tareas de páginas y concurrencia de US2 (T144–T152) dependen de la infraestructura existente; el guard común se integra a US5 mediante T154 y T156. T153, T154 y T155 preceden al control concurrente de evaluaciones T156, que también depende del guard T152.
- T157 y el escenario 13 de quickstart requieren un entorno de staging configurado; completar T157 y las implementaciones de US2 y US5 antes de ejecutar T143 como validación manual final de los escenarios 8–13.
- **Polish**: tras las historias deseadas.

Dentro de cada historia: pruebas (fallando) → migraciones → dominio → casos de uso → infraestructura → rutas → frontend → E2E.

## Parallel Opportunities

- Fase 2: T011–T013 y T015–T022 en paralelo (archivos distintos).
- Cada historia: todas las pruebas `[P]` juntas; dominio y puertos `[P]`; páginas y componentes Vue `[P]`.
- Ejemplo US1: T023–T028 en paralelo, luego T029 y T030, luego T038 y T039.
- Con varios desarrolladores, US2 y el frontend de US1 pueden avanzar a la vez tras la Fase 2.
- Editor enriquecido: T123–T125 son pruebas en archivos distintos; T126 y T127 pueden prepararse en paralelo; T130 puede avanzar en paralelo con la implementación backend T128–T129. En US5, T139 puede integrarse después de disponer de T130, mientras T137–T138 adaptan persistencia y validación.
- Nuevos flujos de edición: T144–T146 y T153 pueden implementarse en paralelo porque usan archivos de prueba distintos; T148 y T155 pueden prepararse en paralelo después de sus pruebas. T149/T150 son backend de cursos/lecciones; T151/T152 son frontend y pueden avanzar en paralelo con el backend. T156 depende de T152–T155; T157 es una actividad operativa independiente una vez habilitado staging.

## Implementation Strategy

- **MVP**: Fases 1–2 + US1 (acceso y usuarios); validar y demostrar.
- **Administrativo completo**: añadir US2 y US3 (cursos, estudiantes e inscripciones).
- **Editor y medios**: completar T122–T133 en US2 antes de T134–T135 y T136–T141 para consumo estudiantil y evaluaciones.
- **Edición segura**: completar T144–T152 en US2 y T153–T156 en US5 para páginas independientes, avisos de cambios sin guardar y rechazo de escrituras obsoletas.
- **Valor pedagógico**: US4 (estudio) y US5 (evaluaciones).
- **Gestión y operación**: US6 (reportes), T157 para recuperación y Polish, incluyendo la carga k6 pendiente T112.
- Verificar cada checkpoint con sus pruebas antes de continuar.
