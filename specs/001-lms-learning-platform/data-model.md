# Data Model: LMS

Modelo conceptual derivado de [spec.md](./spec.md). Los tipos exactos se fijan en las migraciones.

## Identity
- **User**: id, name, email (único), password, must_change_password (bool), is_active, timestamps. Un usuario tiene un rol.
- **Role**: administrador, gestor_de_cursos, docente, estudiante. **Permission**: asignable a roles.
- **AuditLog**: id, user_id, event, subject, metadata, created_at.

## Students
- **Student**: id, user_id (único), documento/identificador opcional, teléfono opcional, is_active. Desactivar conserva el historial.

## Courses
- **Course**: id, name, description, objectives, status (`draft` | `published` | `deactivated`), deleted_at (eliminación lógica que conserva el historial), timestamps.
- **CourseTeacher**: course_id, user_id (docente asignado). Único por par.
- **Unit**: id, course_id, title, position.
- **Lesson**: id, unit_id, title, position, content_type (`text` | `document` | `image` | `link` | `video`), content (texto/URL/ruta de archivo), is_archived.
  - Regla: con progreso registrado solo puede archivarse, no eliminarse (FR-009).

## Enrollments
- **Enrollment**: id, student_id, course_id, status (`active` | `suspended` | `cancelled`), enrolled_at. Único (student_id, course_id).
  - Transiciones: active ↔ suspended; active/suspended → cancelled.
  - Acceso a contenido/evaluaciones: solo `active` + curso `published`. `cancelled` conserva consulta de historial (FR-018).

## Assessments
- **Assessment**: id, course_id, title, max_attempts (defecto 1), passing_score (entre 0 y total_score), total_score (suma de los puntos de sus preguntas), is_published.
- **Question**: id, assessment_id, statement, points, position.
- **Choice**: id, question_id, text, is_correct. Cada pregunta tiene al menos 2 alternativas y exactamente 1 correcta.
- **Attempt**: id, assessment_id, student_id, attempt_number, score, passed (bool), submitted_at.
- **AttemptAnswer**: attempt_id, question_id, choice_id.
  - Reglas: attempt_number ≤ max_attempts; resultado oficial = último intento; el historial se conserva.

## Progress
- **LessonProgress**: student_id, lesson_id, completed_at. Único (student_id, lesson_id).
  - Progreso del curso = lecciones completadas no archivadas / lecciones no archivadas del curso.

## Relaciones clave
Estudiante N—N Curso (vía Enrollment); Curso 1—N Unit 1—N Lesson; Curso 1—N Assessment 1—N Question 1—N Choice; Estudiante 1—N Attempt; Curso N—N Docente (CourseTeacher).
