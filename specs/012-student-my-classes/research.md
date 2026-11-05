# Research & Decision Log: Módulo de Mis Clases y Restricciones Temporales de Estudio

## Decision 1: Modelo de Enrutamiento y Convivencia entre Rutas
- **Problema**: La ruta `/my/classes` ya está asignada a `TeacherClassController::class` para docentes bajo el permiso `scheduling.view-own` o rol docente. ¿Cómo permitir que el estudiante acceda a `/my/classes` sin colisionar?
- **Opciones consideradas**:
  1. *Ruta unificada con despacho condicional según rol*: En `/my/classes`, un middleware o controlador despacha a la vista docente o a la vista de estudiante.
  2. *Controlador conjunto o enrutamiento basado en rol*: En `routes/web.php`:
     - El docente con rol `docente` sigue accediendo a su vista docente de clases.
     - El estudiante con rol `estudiante` accede a `StudentClassController::index` o se evalúa en el controlador.
     - La ruta `/my/courses` se redirige vía 302 a `/my/classes` cuando el usuario es estudiante.
- **Decisión**: En `routes/web.php` se define la ruta `/my/classes` delegando a un método en `StudentClassController` (o un despachador unificado) donde si el usuario es estudiante, carga el listado de clases matriculadas del estudiante; si es docente/admin, mantiene la gestión de sus clases. Además, cualquier petición de estudiante a `/my/courses` responde con redirección 302 hacia `/my/classes`.

## Decision 2: Validación Temporal del Inicio del Curso (`starts_on`)
- **Problema**: Solo se debe poder acceder al visor de estudio si la clase ha iniciado (`starts_on <= hoy`).
- **Decisión**:
  - En la política `CourseAccessPolicy::view`, validar la oferta académica (`course_offering`) en la que está matriculado el estudiante. Si `starts_on !== null` y `starts_on > now()->toDateString()`, denegar acceso (`403 Forbidden`).
  - En el frontend de Mis Clases, la tarjeta del curso renderiza el botón de entrada deshabilitado y un badge con el texto: `"Inicia el DD/MM/AAAA"`.

## Decision 3: Modo Consulta en Cursos Finalizados (`ends_on < hoy`)
- **Problema**: Cuando el curso finaliza, el estudiante debe poder seguir visualizando el contenido, pero no marcar lecciones ni rendir evaluaciones.
- **Decisión**:
  - `StudyController::complete`: Agregar verificación para validar si la oferta de la clase del estudiante ya finalizó (`ends_on !== null && ends_on < now()->toDateString()`). Si finalizó, retornar `abort(403, 'El curso ha finalizado. Modo solo lectura.')`.
  - `AssessmentPolicy::take` y `submit`: Agregar la misma condición sobre la fecha de fin de la oferta del estudiante.
  - En el frontend (`CourseViewer.vue`):
    - Se añade un banner superior informativo: *"Este curso finalizó el DD/MM/AAAA. Modo de consulta"*.
    - Se oculta el botón "Marcar como completada".
    - En el listado de evaluaciones se oculta el botón "Rendir evaluación" y se muestra solo el historial de intentos con notas.

## Decision 4: Avisos Superiores en Mis Clases
- **Problema**: Mostrar hasta 5 clases comenzadas y hasta 5 clases próximas a empezar.
- **Decisión**:
  - El caso de uso o controlador agrupará y limitará:
    - `started_classes`: Clases con `starts_on <= hoy <= ends_on`, ordenadas por cercanía o progreso (hasta 5).
    - `upcoming_classes`: Clases con `starts_on > hoy`, ordenadas ascendentemente por `starts_on` (hasta 5).
  - En Vue, se renderizarán como dos secciones compactas de tarjetas antes de la grilla principal filtrable.

