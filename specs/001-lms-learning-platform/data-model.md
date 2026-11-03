# Data Model: LMS

Modelo conceptual derivado de [spec.md](./spec.md). Los tipos exactos se fijan en las migraciones.

## Identity
- **User**: id, name, email (único), password, must_change_password (bool), is_active, timestamps. Un usuario tiene un rol.
- **Role**: administrador, gestor_de_cursos, docente, estudiante. **Permission**: asignable a roles.
- **AuditLog**: id, user_id, event, subject, metadata, created_at.

## Students
- **Student**: id, user_id (único), documento/identificador opcional, teléfono opcional, is_active. Desactivar conserva el historial.

## Courses
- **Course**: id, name, description (documento JSONB Tiptap), objectives (documento JSONB Tiptap), intro_video_provider (`youtube` | `vimeo`, nullable), intro_video_id (ID validado del proveedor, nullable), status (`draft` | `published` | `deactivated`), `lock_version` (entero que aumenta en cada actualización), deleted_at (eliminación lógica que conserva el historial), timestamps.
- **CourseTeacher**: course_id, user_id (docente asignado). Único por par.
- **CourseMediaImage**: id aleatorio, course_id, storage_path privado, mime_type, size_bytes, uploaded_by, timestamps. Los nodos de imagen de los documentos guardan la URL de lectura interna, `alt` y el indicador de imagen decorativa mediante `alt=""`; los archivos solo se leen tras autorizar el acceso al curso.
- **Unit**: id, course_id, title, position.
- **Lesson**: id, unit_id, title, position, content_type (`text` | `document` | `image` | `link` | `video`), content (JSONB Tiptap para contenido textual; enlace HTTPS para video y URL/ruta validada para los otros tipos), `lock_version` (entero que aumenta en cada actualización), is_archived.
  - Regla: con progreso registrado solo puede archivarse, no eliminarse (FR-009).

## Enrollments
- **Enrollment**: id, student_id, course_id, status (`active` | `suspended` | `cancelled`), enrolled_at. Único (student_id, course_id).
  - Transiciones: active ↔ suspended; active/suspended → cancelled.
  - Acceso a contenido/evaluaciones: solo `active` + curso `published`. `cancelled` conserva consulta de historial (FR-018).

## Assessments
- **Assessment**: id, course_id, title, instructions (documento JSONB Tiptap, nullable), max_attempts (defecto 1), passing_score (entre 0 y total_score), total_score (suma de los puntos de sus preguntas), is_published, `lock_version` (entero que aumenta en cada actualización, también al modificar preguntas o alternativas).
- **Question**: id, assessment_id, statement (documento JSONB Tiptap), points, position.
- **Choice**: id, question_id, text, is_correct. Cada pregunta tiene al menos 2 alternativas y exactamente 1 correcta.
- **Attempt**: id, assessment_id, student_id, attempt_number, score, passed (bool), submitted_at.
- **AttemptAnswer**: attempt_id, question_id, choice_id.
  - Reglas: attempt_number ≤ max_attempts; resultado oficial = último intento; el historial se conserva.

## Progress
- **LessonProgress**: student_id, lesson_id, completed_at. Único (student_id, lesson_id).
  - Progreso del curso = lecciones completadas no archivadas / lecciones no archivadas del curso.
- **Report**: consulta filtrada de inscripciones, progreso o resultados; salida exportable en CSV.

## Relaciones clave
Estudiante N—N Curso (vía Enrollment); Curso 1—N Unit 1—N Lesson; Curso 1—N Assessment 1—N Question 1—N Choice; Estudiante 1—N Attempt; Curso N—N Docente (CourseTeacher).

## Reglas de contenido enriquecido y medios
- Los documentos JSONB usan el esquema Tiptap permitido: párrafos, negrita, cursiva, encabezados, listas, citas, enlaces e imágenes. El backend valida estructura, atributos y protocolos antes de persistir; no se guardan HTML arbitrario, `data:` URLs ni iframes proporcionados por usuarios.
- Los enlaces permiten únicamente HTTP/HTTPS; las imágenes externas, únicamente HTTPS. El servidor no descarga URLs externas.
- Las imágenes subidas admiten JPEG, PNG y WebP, hasta 5120 KB; se validan en backend y se nombran con identificadores aleatorios. Los enlaces a imágenes privadas aplican la autorización del curso.
- `intro_video_provider` e `intro_video_id` se derivan de una URL HTTPS validada de YouTube o Vimeo. La URL canónica se reconstruye al editar; el reproductor genera su `iframe` únicamente desde estos valores validados.
- Para conservar registros de texto previos, la migración convierte el texto existente a un documento JSONB con un nodo de párrafo.

## Concurrencia y recuperación
- Las actualizaciones de curso, lección y evaluación incluyen el `lock_version` leído al abrir el formulario. La escritura solo se aplica si coincide con la versión persistida; en el mismo guardado se incrementa la versión. Una versión obsoleta no reemplaza datos y se reporta como conflicto para que el usuario recargue.
- Las copias de seguridad incluyen PostgreSQL y el almacenamiento privado de medios. El respaldo más reciente puede tener hasta 24 horas; después de una interrupción, el servicio y los datos deben volver a estar disponibles en un máximo de 24 horas. La recuperación no puede perder más que los cambios posteriores al último respaldo. La política de retención queda sujeta al entorno de despliegue.
