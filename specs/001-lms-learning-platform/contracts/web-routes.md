# Contract: Rutas web (Inertia)

Interfaz expuesta a los usuarios. Todas las rutas requieren sesión salvo las de autenticación; la
autorización se valida siempre en el backend (Policies/permisos). Alcance: Admin = todo; Gestor =
todos los cursos y estudiantes; Docente = cursos asignados; Estudiante = solo lo propio.

| Módulo | Método y ruta | Descripción | Roles |
|--------|---------------|-------------|-------|
| Auth | `GET/POST /login`, `POST /logout` | Iniciar/cerrar sesión | Público / autenticado |
| Auth | `GET/POST /forgot-password`, `GET/POST /reset-password/{token}` | Recuperación de contraseña | Público |
| Auth | `GET/POST /password/change` | Cambio obligatorio en primer acceso | Autenticado |
| Usuarios | `GET/POST /users`, `GET/PUT /users/{id}`, `PATCH /users/{id}/status` | Gestión de usuarios | Administrador |
| Roles | `GET /roles`, `PUT /roles/{id}/permissions` | Roles y permisos | Administrador |
| Cursos | `GET/POST /courses`, `GET/PUT/DELETE /courses/{id}`, `PATCH /courses/{id}/status` | CRUD, publicar/desactivar | Admin, Gestor; Docente (lectura/asignados) |
| Cursos | `PUT /courses/{id}/teachers` | Asignar docentes | Admin, Gestor |
| Contenido | `POST /courses/{id}/units`, `PUT/DELETE /units/{id}`, `PATCH /units/reorder` | Unidades | Admin, Gestor, Docente asignado |
| Contenido | `POST /units/{id}/lessons`, `PUT/DELETE /lessons/{id}`, `PATCH /lessons/reorder`, `PATCH /lessons/{id}/archive` | Lecciones | Admin, Gestor, Docente asignado |
| Estudiantes | `GET/POST /students`, `GET/PUT /students/{id}`, `PATCH /students/{id}/status` | Gestión de estudiantes | Admin, Gestor; Docente (solo lectura de sus cursos) |
| Inscripciones | `POST /courses/{id}/enrollments`, `PATCH /enrollments/{id}/status` | Inscribir y cambiar estado | Admin, Gestor |
| Inscripciones | `GET /courses/{id}/students`, `GET /students/{id}/courses` | Consultas cruzadas | Según alcance |
| Estudio | `GET /my/courses`, `GET /my/courses/{id}`, `POST /lessons/{id}/complete` | Cursos, contenido y completar lección | Estudiante (inscripción activa) |
| Evaluaciones | `POST /courses/{id}/assessments`, `PUT/DELETE /assessments/{id}`, `PATCH /assessments/{id}/publish` | Gestión de evaluaciones y preguntas | Admin, Gestor, Docente asignado |
| Evaluaciones | `GET /assessments/{id}/take`, `POST /assessments/{id}/attempts` | Rendir y enviar | Estudiante |
| Resultados | `GET /assessments/{id}/results`, `GET /my/results` | Consultar resultados | Según alcance |
| Progreso | `GET /courses/{id}/progress`, `GET /my/progress` | Progreso por curso/estudiante | Según alcance |
| Reportes | `GET /reports/enrollments`, `/reports/progress`, `/reports/results` (`?format=csv`) | Reportes filtrables y exportables | Admin, Gestor, Docente (sus cursos) |

### Reglas de cursos y contenido
- `DELETE /courses/{id}` elimina lógicamente el curso. Si tiene inscripciones, requiere `confirm_impact=true`; las inscripciones y el historial se conservan.
- `POST /units/{id}/lessons` acepta archivos PDF, DOC/DOCX, PPT/PPTX para documentos; JPG/JPEG, PNG o WebP para imágenes; MP4, WebM o MOV para videos. El máximo se configura con `COURSE_MAX_UPLOAD_KB` (por defecto 10 240 KB).
- Las lecciones con progreso no se pueden eliminar; deben archivarse.

## Respuestas de error
- 422: validación (mensajes por campo). 403: sin permiso o sin inscripción activa. 404: recurso fuera de alcance.
- 429: límite de intentos de inicio de sesión. Los errores internos no exponen detalles.
