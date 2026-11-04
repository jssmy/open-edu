# Quickstart: Validar Programación académica (`CourseOffering`, `Sessions`) y Matrícula de estudiantes

**Prerrequisitos**: `composer install`, `npm install`, `php artisan migrate --seed`.

1. **Crear programación académica (`CourseOffering`) y sesiones individuales (`Sessions`)**:
   - Iniciar sesión como administrador y abrir `/scheduling`.
   - Crear un `CourseOffering` seleccionando el curso en el desplegable con buscador (`SearchableSelect`), verificando el código de grupo autogenerado en modo solo lectura (`GRP-01`, `GRP-02`, ...), e indicando fechas de inicio y fin, modalidad (`presencial`, `virtual` o `híbrida`), capacidad máxima (ej. `2` estudiantes), estado `publicada` y asignando un único docente mediante `SearchableSelect`.
   - Agregar manualmente sesiones individuales (`Sessions`) indicando únicamente fecha exacta, hora de inicio y fin, estado y aula o enlace de videoconferencia (sin campo de título/tema ni referencia a lecciones).
   - En el calendario (`ScheduleCalendar`), verificar que cada docente se muestra con un color diferente, que dentro de cada ítem del calendario se visualizan en líneas separadas el horario, el curso/grupo, el docente (`Docente: ...`) y el aula (`Aula: ...`), y que al hacer clic sobre un bloque del calendario se abre el modal `Editar programación académica` de ese grupo.
   - Verificar que se rechazan sesiones con traslape de docente o aula en la misma fecha y hora (`teacher_conflict` / `classroom_conflict`) y que las sesiones consecutivas son aceptadas.

2. **Matricular estudiante viendo su horario en tiempo real (`/enrollments`)**:
   - Abrir `/enrollments` como administrador (y verificar que un rol sin permiso `enrollments.manage` recibe 403).
   - Seleccionar un estudiante activo en el buscador (`SearchableSelect`).
   - Explorar la sección "Cursos publicados disponibles", verificando que los cursos con vacantes disponibles se listan primero en una cuadrícula de tarjetas reutilizables (`CourseCard`) con paginación (`Pagination`).
   - Hacer clic en "Ver programación y cupos" de un curso disponible → se abre el modal de programación académica detallando los grupos (`CourseOffering`), docentes, fechas/días, horarios de sesiones y cupos disponibles, con su botón `Cancelar`.
   - Confirmar la matrícula en el grupo → el estudiante queda matriculado, los cupos disponibles disminuyen en 1 y las sesiones aparecen inmediatamente en el horario a ancho completo del estudiante (`ScheduleCalendar`).
   - Observar que la tarjeta superior muestra los cursos matriculados en una lista compacta con scroll vertical interno (`max-h-44 overflow-y-auto`) sin hacer crecer la tarjeta indefinidamente.

3. **Validar cruces de horario y límite de cupos**:
   - Intentar matricular al mismo estudiante en otro curso cuyo `CourseOffering` tenga una sesión en la misma fecha y horario traslapado → el sistema rechaza con error 422 indicando el cruce de horario (`student_schedule_conflict`).
   - Matricular a otros estudiantes hasta agotar `max_students` e intentar matricular a uno más → el sistema rechaza por falta de vacantes (`offering_full`).

4. **Desmatricular desde el calendario o listado y verificar liberación de cupo en tiempo real**:
   - En `/enrollments`, hacer clic sobre cualquier ítem o bloque de programación académica dentro del horario del estudiante (`ScheduleCalendar`), o en el botón "Desmatricular" del listado superior de cursos matriculados.
   - En el modal de confirmación (`AlertDialog`), confirmar que desea desmatricular al estudiante del curso.
   - Verificar que las sesiones de ese curso desaparecen al instante del horario del estudiante, que el cupo disponible en el `CourseOffering` aumenta en 1 y que el estudiante puede volver a matricularse posteriormente conservando su progreso de lecciones.

**Pruebas automatizadas**:
- Backend: `php artisan test --compact --filter=Scheduling` y `php artisan test --compact --filter=Enrollment`
- Frontend: `npm test`

