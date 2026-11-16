# Feature Specification: Role-Based Course Categories, Teacher Self-Enrollment, and Certificate Issuance

**Feature Branch**: `018-course-categories-certificates-enrollment`

**Created**: 2026-11-15

**Status**: Ready for Planning

**Input**: User description: "atualizamos, cuando se crea una categoria se debe seleccionar el rol al cual debe estar disponible, por defecto estara marcado el rol de estudiante. Entonces cuando creas un curso solo seleccionas la categoria. En programacion academica y matricula solo seran visibles las categorias de los estudiantes. Los otros roles no tendrn programacionn academica ni matricula, mentonces por ejemplo cuando el rol Docentre inicie seion en /cursos veran todos los curos disponibles de su categoria, el docente mismo puede inscribirse y tomar estos cursos."

## Clarifications

### Session 2026-11-15

- Q: ¿En qué momento y de qué forma se debe generar el archivo del certificado cuando el participante (estudiante o docente) cumple con los requisitos del curso? → A: Bajo demanda del participante: al llegar al 100% de avance (y aprobar exámenes si existen), se habilita el botón "Obtener certificado" y el archivo se genera y guarda en el volumen la primera vez que el participante lo solicita.
- Q: ¿Desde dónde debe el administrador poder crear y gestionar las categorías de los cursos en la interfaz? → A: Integrado en el módulo de Cursos: mediante un botón/modal de "Nueva categoría" directamente en `/courses` y junto al selector de categoría al crear/editar un curso, seleccionando el rol al cual estará disponible (por defecto `estudiante`).
- Q: ¿Cómo se determina a quién va dirigido un curso y cómo acceden los docentes a los cursos de su categoría? → A: El rol se define en la **Categoría** (por defecto `estudiante`). Al crear un curso solo se selecciona la categoría. En Programación Académica (`/scheduling`) y Matrícula (`/enrollments`) solo son visibles los cursos de categorías de `estudiante`. Los demás roles (como `docente`) no tienen programación académica ni matrícula administrativa: cuando un docente inicia sesión en `/courses`, ve todos los cursos publicados disponibles de las categorías de su rol y puede inscribirse por sí mismo y tomar el curso.
- Q: ¿Qué categorías predeterminadas debe incluir la semilla inicial y a qué rol se asignan? → A: Sembrar 8 categorías predeterminadas: `"Onboarding"` y `"Capacitación"` asignadas al rol `docente`, y `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"` y `"Digital"` asignadas al rol `estudiante`.
- Q: ¿En qué formato de archivo debe generarse y almacenarse el certificado dentro del volumen del sistema? → A: Documento PDF (`.pdf`) en orientación horizontal con diseño institucional, nombre del participante, curso, categoría, fecha de emisión y código único, guardando únicamente `file_url` en la base de datos.
- Q: ¿Cómo debe mostrarse la información y el acceso al detalle en la tarjeta de curso (`CourseCard`)? → A: En el card de cursos no se debe mostrar el rol, sino únicamente la **categoría**. Además, no debe mostrarse el botón con icono de ojo para entrar al detalle; el acceso al detalle del curso debe realizarse haciendo clic directamente sobre la **vista previa de la imagen** o sobre el **nombre del curso**.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Role-Targeted Course Categories and Default Seeders (Priority: P1)

As an administrator,  
I want to select the target role when creating a course category (with the `estudiante` role selected by default) and have predefined categories seeded for both students and teachers,  
So that each category automatically governs which role can access its courses and whether those courses participate in academic scheduling and student enrollment.

**Why this priority**: Foundational classification that drives course visibility, scheduling eligibility, enrollment rules, and role-based catalog access.

**Independent Test**: Run the category seeder and verify that `"Onboarding"` and `"Capacitación"` exist for the `docente` role and `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"`, and `"Digital"` exist for the `estudiante` role. Open the "Nueva categoría" modal in `/courses` or `/courses/create`, verify the role selector defaults to `estudiante`, create a category for `docente`, and verify it is persisted with that role.

**Acceptance Scenarios**:

1. **Given** a freshly seeded system, **When** inspecting course categories, **Then** `"Onboarding"` and `"Capacitación"` are configured for the `docente` role, and `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"`, and `"Digital"` are configured for the `estudiante` role.
2. **Given** an administrator opens the "Nueva categoría" modal in `/courses` or the course creation/editing form, **When** the modal loads, **Then** the role selector is pre-selected to **Estudiante** (`estudiante`) by default, and the administrator can keep it or choose another role (such as **Docente**) before saving.
3. **Given** an administrator creates a category with a valid unique name and a selected role, **When** it is saved, **Then** the category stores its assigned role and is immediately available in the course category selector.

---

### User Story 2 - Simplified Course Creation (Category Selection Only + Default Certificate Toggle) (Priority: P1)

As an administrator creating or editing a course,  
I want to select only the course category (without a separate target audience field) and have the "Emitir certificado al finalizar" option checked (`true`) by default,  
So that course creation is streamlined and the course automatically inherits its target role from the selected category.

**Why this priority**: Directly simplifies the course creation workflow and ensures every course has a deterministic role audience via its category.

**Independent Test**: Open `/courses/create`, verify there is no separate target audience dropdown, verify that selecting a category assigns the category (and its underlying role) to the course, and verify that "Emitir certificado al finalizar" is checked (`true`) by default.

**Acceptance Scenarios**:

1. **Given** an administrator opens `/courses/create` or `/courses/{course}/edit`, **When** viewing the form fields, **Then** the administrator selects the **Categoría** without having to fill out a separate target audience field, and the **"Emitir certificado al finalizar"** checkbox is checked (`true`) by default on creation.
2. **Given** any user views a course card (`CourseCard`) in the courses catalog, **When** the card renders, **Then** it displays the **category** badge (and never displays a role badge on the card), does not render an eye icon button, and navigates to the course detail when clicking either the **cover image preview** or the **course name**.
3. **Given** a course assigned to a category of the `docente` role (e.g., `"Capacitación"` or `"Onboarding"`), **When** units, lessons, and optional assessments/exams are configured on the course, **Then** it supports the full lesson and assessment workflow identically to student courses.

---

### User Story 3 - Student-Only Scheduling & Enrollment vs. Teacher Catalog & Self-Enrollment in `/courses` (Priority: P1)

As an administrator or teacher,  
I want Academic Scheduling (`/scheduling`) and Enrollments (`/enrollments`) to display only courses from Student categories, while teachers logging into `/courses` see all published courses from Teacher categories and can self-enroll and study them directly,  
So that student academic groups and schedules remain cleanly separated from self-paced faculty training courses.

**Why this priority**: Implements the core operational distinction between scheduled student courses and self-enrolled courses for teachers and other non-student roles.

**Independent Test**: Create a published course in a `docente` category (`"Capacitación"`) and a published course in an `estudiante` category (`"Lógica"`). Verify that `/scheduling` and `/enrollments` list only the `"Lógica"` course and reject scheduling/enrolling the `"Capacitación"` course. Then log in as a `docente` user, visit `/courses`, verify all published `"Capacitación"` / `"Onboarding"` courses are visible, click to self-enroll in the `"Capacitación"` course, and study its lessons and assessments in `/my/courses/{course}`.

**Acceptance Scenarios**:

1. **Given** an authorized user in Academic Scheduling (`/scheduling`, `/scheduling/pending`) or Enrollments (`/enrollments`), **When** viewing or selecting courses to schedule or enroll students, **Then** only courses belonging to categories with role **Estudiante** (`estudiante`) are visible and eligible; courses from other roles' categories do not appear and cannot be scheduled or enrolled there.
2. **Given** a user with the **Docente** role (without global `courses.manage` permission) logs in and visits `/courses`, **When** the page loads, **Then** `/courses` displays all published courses belonging to categories assigned to the `docente` role, indicating which courses the teacher is already enrolled in and which are available to join.
3. **Given** a teacher viewing an available course of their category in `/courses`, **When** they click **"Inscribirme"** (or **"Tomar curso"**), **Then** the system registers their active self-enrollment without requiring a schedule or group (`CourseOffering`) and redirects or gives immediate access to the study viewer (`/my/courses/{course}`) to complete lessons and take any published exams.
4. **Given** a teacher already enrolled in a course of their category, **When** they click to enter the course from `/courses`, **Then** they access `/my/courses/{course}` directly to continue their progress and assessments while the academic classes they teach remain in `/my/classes`.

---

### User Story 4 - Certificate Template, Volume Storage, and URL-Only Database Persistence (Priority: P2)

As a learner (student or self-enrolled teacher),  
I want the system to generate a landscape `.pdf` completion certificate from an institutional template when I complete 100% of a certificate-enabled course, storing the `.pdf` file in the system storage volume and saving only the file URL in the database,  
So that I can obtain and download official proof of completion without storing binary blobs in the database.

**Why this priority**: Completes the learning lifecycle for both student courses and self-enrolled teacher courses.

**Independent Test**: Complete all published lessons (and pass any published assessments) in a course with `issues_certificate = true`, click **"Obtener certificado"**, verify the `.pdf` file is written to the storage volume, verify only `file_url` is saved in the `certificates` table, and download the `.pdf` file.

**Acceptance Scenarios**:

1. **Given** a learner (student or self-enrolled teacher) in a course with `issues_certificate = true`, **When** they complete 100% of published lessons and pass all published assessments (if any) and request their certificate, **Then** the system generates a landscape `.pdf` certificate from the institutional template and writes it to the storage volume.
2. **Given** a certificate is generated, **When** it is saved in the database, **Then** the `certificates` table stores **only** `file_url` and metadata (`enrollment_id`, `course_id`, `user_id`, `student_id`, `code`, `issued_at`), with zero binary content in the database.
3. **Given** a course with `issues_certificate = false`, **When** the learner completes 100% of the course, **Then** no certificate is generated or offered.

---

### Edge Cases

- **Duplicate category names**: Creating a category with a name that already exists (case-insensitive) is rejected with a validation error.
- **Category required on courses**: Creating or updating a course requires selecting a valid active category so every course has a defined role audience.
- **Attempting to create a `CourseOffering` for a non-student category course**: If a request attempts to create an academic schedule/offering (`POST /scheduling/offerings`) for a course whose category role is not `estudiante`, the system rejects it with a clear domain validation error.
- **Idempotent self-enrollment**: If a teacher clicks to self-enroll in a course where they already have an active enrollment, the system does not create a duplicate record and takes them directly to the course viewer (`/my/courses/{course}`). If they had a previously cancelled enrollment, it reactivates it preserving prior lesson progress.
- **Courses with no exams vs. courses with exams**: If a course has no published assessments, completing 100% of non-archived lessons unlocks certificate generation. If the course has published assessments, passing all published assessments is also required.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST associate every course category (`CourseCategory`) with a target role (`role_name`, defaulting to `'estudiante'`), and allow the administrator to select the target role when creating a category.
- **FR-002**: System MUST provide a database seeder (`CourseCategorySeeder`) that creates `"Onboarding"` and `"Capacitación"` for the `docente` role, and `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"`, and `"Digital"` for the `estudiante` role.
- **FR-003**: System MUST allow administrators (`courses.manage`) to create course categories inline from `/courses` and from `/courses/create` (`/courses/{course}/edit`) via a modal where the role selector defaults to **Estudiante** (`estudiante`).
- **FR-004**: System MUST require selecting a category (`category_id`) when creating or editing a course, without displaying a separate target audience field on the course form (the course's target role is determined by its category).
- **FR-005**: System MUST provide the **"Emitir certificado al finalizar el curso"** (`issues_certificate`) option checked (`true`) by default when creating a course.
- **FR-006**: System MUST restrict **Academic Scheduling** (`/scheduling`, `/scheduling/pending`, `POST /scheduling/offerings`) and **Enrollments** (`/enrollments`, `POST /enrollments`, `POST /courses/{courseId}/enrollments`) so that **only courses belonging to categories of the `estudiante` role** are visible and eligible for scheduling and administrative student enrollment.
- **FR-007**: System MUST NOT require or display academic scheduling (`CourseOffering`) or administrative enrollment (`/enrollments`) for categories belonging to non-student roles (such as `docente`).
- **FR-008**: When a user with a non-student role (such as `docente`, without `courses.manage`) visits `/courses`, the system MUST display **all published courses belonging to categories assigned to their role**, allowing them to filter by their role's categories, self-enroll (`POST /courses/{course}/enroll`), and enter `/my/courses/{course}` to study lessons and take assessments.
- **FR-009**: System MUST allow a user of a non-student role (e.g. `docente`) to self-enroll directly in any published course whose category matches their role, creating an active `Enrollment` (`user_id = $user->id`, `course_offering_id = null`) without schedule restrictions.
- **FR-010**: System MUST generate a landscape `.pdf` certificate using the institutional template on demand (`POST /courses/{course}/certificate`) when an enrolled learner (student or self-enrolled teacher) completes 100% of non-archived lessons and passes all published assessments on a course with `issues_certificate = true`.
- **FR-011**: System MUST store the generated `.pdf` certificate file in the persistent storage volume and store **only `file_url`** and metadata in the `certificates` database table.
- **FR-012**: In the course card (`CourseCard`), the system MUST display the course's **category** badge without displaying the role name, MUST NOT render the eye (`view`) icon button in the card footer, and MUST allow navigating to the course detail by clicking either the **course cover image preview** or the **course name**.

### Key Entities *(include if feature involves data)*

- **Course Category (`CourseCategory`)**: Classification for courses. Attributes: `id`, `name`, `slug`, `role_name` (target role name, default `'estudiante'`), `is_active`.
- **Course (`Course`)**: Belongs to a `CourseCategory` (`category_id`, which determines the target role) and includes `issues_certificate` (`boolean`, default `true`).
- **Enrollment (`Enrollment`)**: Links either a student (`student_id` + `course_offering_id`) for student-category courses in `/enrollments`, or a self-enrolled user (`user_id`, `course_offering_id = null`) for non-student role courses in `/courses`.
- **Certificate (`Certificate`)**: Issued completion credential storing `code`, `file_url` (path/URL to the `.pdf` in the storage volume), and `issued_at`.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of seeded environments contain the 8 predefined categories with their respective roles (`"Onboarding"` and `"Capacitación"` for `docente`; `"Disciplina"`, `"Expresión"`, `"Creatividad"`, `"Lógica"`, `"Idiomas"`, and `"Digital"` for `estudiante`), and new categories default to the `estudiante` role in the creation modal.
- **SC-002**: 100% of course creation forms require only category selection (no separate target audience field) and default "Emitir certificado al finalizar" to `true`.
- **SC-003**: 100% of courses shown in Academic Scheduling (`/scheduling`) and Enrollments (`/enrollments`) belong exclusively to `estudiante` categories; 0 courses from non-student categories appear in `/scheduling` or `/enrollments`.
- **SC-004**: 100% of teachers logging into `/courses` can view all published courses in `docente` categories, self-enroll in one click without a schedule, complete lessons and optional assessments, and download their generated `.pdf` certificate stored in the system volume with only `file_url` saved in the database.

## Assumptions

- Available roles selectable when creating a category come from the active roles in the system (`estudiante`, `docente`, etc.), with `estudiante` pre-selected by default.
- Administrators with `courses.manage` continue to see and manage all courses across all categories in `/courses`, while teachers (`docente` without `courses.manage`) see all published courses belonging to `docente` categories in `/courses` and can self-enroll and study them there.
- Classes that a teacher is assigned to teach to students (`CourseOffering` / `CourseSession`) continue to be accessed in `/my/classes` and `/my/schedule`.
