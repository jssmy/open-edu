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
- `POST /units/{id}/lessons` acepta archivos PDF, DOC/DOCX, PPT/PPTX para documentos y JPG/JPEG, PNG o WebP para imágenes. Los videos de lección se guardan como enlaces HTTPS; no se aceptan archivos de video. El máximo para archivos se configura con `COURSE_MAX_UPLOAD_KB` (por defecto 10 240 KB).
- Las lecciones con progreso no se pueden eliminar; deben archivarse.
- Los flujos para crear o editar cursos, lecciones y evaluaciones con editor enriquecido se presentan en páginas Inertia independientes, nunca en modales o diálogos. Una navegación con cambios sin guardar pide confirmación para quedarse o descartarlos.
- Las actualizaciones de curso, lección y evaluación envían el `lock_version` recibido al cargar el formulario. El backend actualiza de forma atómica solo si esa versión sigue vigente e incrementa el contador al guardar. Las modificaciones de preguntas y alternativas incrementan la versión de su evaluación.

### Contratos de contenido enriquecido y medios
- `POST /courses/{id}/content-images` recibe `multipart/form-data` con una imagen JPEG, PNG o WebP de máximo 5120 KB. Solo Admin, Gestor o Docente asignado puede subir a ese curso. Laravel valida el archivo y genera su identificador/nombre; no confía en nombre, extensión o MIME del cliente. Responde JSON con `id` y `src` de lectura interna para insertar en el documento Tiptap.
- `GET /courses/{id}/content-images/{image}` entrega una imagen desde almacenamiento privado y verifica que el usuario pueda acceder al contenido del curso: Admin/Gestor, Docente asignado o Estudiante con inscripción activa en curso publicado. Un recurso fuera del alcance responde 404. La respuesta especifica el MIME validado y `X-Content-Type-Options: nosniff`.
- Las solicitudes de curso incluyen `description` y `objectives` como documentos JSON Tiptap y `intro_video_url` como URL de YouTube o Vimeo. Las solicitudes de lección incluyen `content` como JSON Tiptap cuando el tipo es texto. Las solicitudes de evaluación incluyen `instructions` opcional y las de pregunta incluyen `statement`, ambos documentos JSON Tiptap.
- El servidor rechaza nodos, marcas, atributos y esquemas URL fuera del allowlist. Los enlaces solo admiten HTTP/HTTPS; las imágenes externas solo HTTPS. Las imágenes subidas deben tener `alt` descriptivo o `alt=""` para decorativas. No se admiten HTML/iframes arbitrarios ni `data:` URLs.
- `intro_video_url` acepta solo URLs válidas de YouTube/Vimeo. El servidor persiste proveedor e ID validados y devuelve la URL canónica para edición; el reproductor construye un iframe seguro de `youtube-nocookie.com` o `player.vimeo.com`.
- Las imágenes externas se cargan directamente desde el navegador, sin proxy ni descarga del servidor, y se muestran con `referrerpolicy="no-referrer"`.

## Respuestas de error
- 422: validación (mensajes por campo). 403: sin permiso o sin inscripción activa. 404: recurso fuera de alcance.
- 409: conflicto de edición; no se guarda la versión obsoleta y se indica al usuario que recargue para revisar el contenido actual.
- 429: límite de intentos de inicio de sesión. Los errores internos no exponen detalles.
