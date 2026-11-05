# Quickstart & Validation Guide: Navegación de Calendario y Revisión de Evaluaciones

## Pre-requisitos
- Base de datos con al menos un curso, un estudiante con evaluaciones rendidas o en curso, y programaciones en diferentes semanas y meses.
- Servidor ejecutándose localmente (`http://localhost:8000`).

## Escenario de Validación 1: Navegación Temporal en Calendario
1. Ingresar a `http://localhost:8000/scheduling` o `http://localhost:8000/my/schedule`.
2. Verificar que en la barra superior de `ScheduleCalendar` se muestran los botones: "Anterior", "Hoy", "Siguiente" y el título del período actual (ej. mes o semana visible).
3. Estando en vista **Semanal**, presionar "Siguiente":
   - El calendario avanza exactamente 7 días.
4. Cambiar a vista **Mensual** y presionar "Siguiente":
   - El calendario avanza exactamente un mes calendario.
5. Cambiar a vista **Diaria** y presionar "Siguiente":
   - El calendario avanza un día.
6. Presionar "Hoy":
   - El calendario vuelve al período que contiene el día de hoy.

## Escenario de Validación 2: Acceso a Revisión desde la Ficha de Estudiante
1. Ingresar a `http://localhost:8000/students/2` (o cualquier estudiante con cursos y evaluaciones).
2. En la sección "Evaluaciones y Calificaciones", hacer clic en una evaluación rendida.
3. El sistema navega a `/assessments/{id}/review?student_id=2`.
4. Verificar que se renderiza la vista `Assessments/Review`:
   - Enunciados de preguntas e instrucciones.
   - Opciones de respuesta completas.
   - Opción seleccionada por el estudiante resaltada.
   - Estado de acierto/error y puntaje obtenido.
   - La página está en modo estricto de solo lectura (sin campos editables ni botón de guardar).

## Escenario de Validación 3: Estudiante con Evaluación en Curso
1. Iniciar sesión como estudiante que tenga un intento en estado `in_progress` en una evaluación.
2. Ingresar a la revisión de dicha evaluación (`/assessments/{id}/review`).
3. Verificar que aparece un botón o alerta destacada: "Continuar evaluación" / "Rendir examen".
4. Hacer clic en dicho botón y constatar que redirige a `/assessments/{id}/take`.

## Escenario de Validación 4: Optimización de Consultas SQL y Rendimiento
1. Ejecutar las pruebas del módulo de programación de docentes y revisión:
   ```bash
   php artisan test tests/Feature/Scheduling/TeacherClassOrderTest.php tests/Feature/Assessments/AssessmentReviewTest.php
   ```
2. Verificar que las consultas en `TeacherClassController` y `AssessmentController` no traen campos innecesarios (`objectives`, `intro_video_*`, `content`) y que la ordenación se resuelve limpiamente en memoria por colección.


