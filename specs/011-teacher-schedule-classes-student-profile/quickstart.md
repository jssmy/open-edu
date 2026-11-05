# Quickstart: Validación de Horarios por Curso, Clases Próximas y Ficha Integral

## Escenarios de Validación

### 1. Validación de Color por Curso en Calendario (`/my/schedule`)
1. Iniciar sesión con un usuario docente (`docente@example.com`) o administrador (`admin@example.com`).
2. Navegar a `http://localhost:8000/my/schedule`.
3. Verificar que los bloques de calendario pertenecientes al mismo curso comparten el mismo color de fondo/borde.
4. Si hay dos asignaturas distintas (ej. "Matemática" y "Física"), comprobar que muestran dos colores claramente diferenciados.
5. Comprobar legibilidad de los textos dentro del bloque con el color aplicado.

### 2. Validación de Ordenamiento de Clases por Proximidad (`/my/classes`)
1. Iniciar sesión con un usuario docente.
2. Navegar a `http://localhost:8000/my/classes`.
3. Verificar que la lista de tarjetas de curso está ordenada cronológicamente de forma ascendente según `starts_on`, mostrando primero la oferta más inminente a dictar.

### 3. Validación de Ficha de Estudiante con Progreso y Evaluaciones (`/students/{studentId}`)
1. Iniciar sesión con el usuario docente.
2. Navegar a `http://localhost:8000/students/2` (o cualquier ID de estudiante en un curso asignado al docente).
3. Verificar que la información básica del estudiante se muestra en el encabezado.
4. Verificar que cada curso matriculado exhibe:
   - Nombre de curso y código de grupo.
   - Barra de progreso formativo con porcentaje y lecciones completadas.
   - Listado de evaluaciones rendidas con su respectiva calificación/nota, estado de aprobación (Aprobado/Desaprobado) y fecha.
5. Verificar que el docente no puede ver opciones administrativas restringidas si no tiene `students.manage`.

## Ejecución de Pruebas Automatizadas
```bash
/opt/homebrew/Cellar/php@8.4/8.4.24/bin/php artisan test --filter=TeacherClassTest
/opt/homebrew/Cellar/php@8.4/8.4.24/bin/php artisan test --filter=StudentProfileTest
/opt/homebrew/Cellar/php@8.4/8.4.24/bin/php artisan test --filter=ScheduleTest
```

