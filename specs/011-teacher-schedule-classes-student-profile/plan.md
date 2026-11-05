# Implementation Plan: Horario por Curso, Clases Próximas y Ficha Integral de Estudiante

**Branch**: `011-teacher-schedule-classes-student-profile` | **Date**: 2026-11-04 | **Spec**: [spec.md](file:///Users/jssmy/Documents/projects/open-edu/specs/011-teacher-schedule-classes-student-profile/spec.md)

**Input**: Feature specification from `specs/011-teacher-schedule-classes-student-profile/spec.md`

## Summary

Implementar la asignación cromática consistente por curso en el calendario de horarios (`/my/schedule` y vista administrativa), ordenar cronológicamente las clases en `/my/classes` por la más próxima a iniciar, y enriquecer la ficha del estudiante (`/students/{studentId}`) integrando el avance/progreso de lecciones y el desglose de evaluaciones y notas dentro de cada curso matriculado para docentes y administradores.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 11+), TypeScript (Vue 3, Inertia.js v3)  
**Primary Dependencies**: FullCalendar (`@fullcalendar/vue3`), Tailwind CSS, Headless UI / Heroicons  
**Storage**: PostgreSQL (modelos `CourseOffering`, `CourseSession`, `Course`, `Enrollment`, `Student`, `LessonCompletion`, `Assessment`, `Attempt`)  
**Testing**: Pest / PHPUnit (`php artisan test`)  
**Target Platform**: Web SPA (Inertia.js Monolith)  
**Project Type**: Monolito Modular Web (Hexagonal Architecture)  
**Performance Goals**: Carga de ficha de estudiante y calendario en < 1 segundo, sin consultas N+1  
**Constraints**: Respeto estricto a las políticas de autorización de docentes (`canTeacherView`), diseño accesible WCAG AA para contrastes de color en calendario  
**Scale/Scope**: 3 vistas principales modificadas (`MySchedule.vue`, `Teacher/Classes.vue`, `Students/Show.vue`) y 2 endpoints backend extendidos  

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **I. Monolito Modular con Arquitectura Hexagonal**: La lógica se mantiene en los módulos correspondientes (`Scheduling`, `Students`, `Progress`, `Assessments`) respetando dominios y puertos de repositorio.
- [x] **II & III. SOLID, DRY, KISS, YAGNI**: Reutiliza composables (`useScheduleCalendar`), componentes del sistema de diseño (`Badge`, `Card`, `ScheduleCalendar`) y repositorios existentes sin crear abstracciones innecesarias.
- [x] **V. Frontend con Vue y TypeScript**: TypeScript estricto con interfaces claras para los bloques de calendario y cursos de estudiante.
- [x] **VI. Sistema de Diseño**: Colores conformes a la paleta institucional y componentes base existentes.
- [x] **VII. Calidad y Pruebas**: Tests con Pest cubriendo el ordenamiento en `/my/classes`, el enriquecimiento de datos en `/students/{id}` y el mapeo de bloques de calendario.
- [x] **VIII. Seguridad**: Autorización estricta mediante `canTeacherView` en `StudentPolicy` asegurando que docentes no accedan a calificaciones de cursos o alumnos ajenos.
- [x] **XI. FullCalendar único**: Conserva el componente envoltorio `ScheduleCalendar` y composable `useScheduleCalendar` sin agregar librerías externas.

## Project Structure

### Documentation (this feature)

```text
specs/011-teacher-schedule-classes-student-profile/
├── spec.md              # Requisitos y escenarios aceptados
├── plan.md              # Este plan de implementación
├── research.md          # Decisiones técnicas y justificaciones
├── data-model.md        # Estructuras de datos y entidades
├── quickstart.md        # Guía de validación y escenarios de prueba
├── contracts/
│   └── endpoints.md     # Contratos de respuesta de endpoints
└── checklists/
    └── requirements.md  # Checklist de calidad de especificación
```

### Source Code

```text
app/Modules/
├── Scheduling/
│   └── Infrastructure/Http/
│       ├── SchedulingController.php         # Incluye course_id en calendarBlocks
│       └── TeacherClassController.php       # Ordena clases por starts_on cronológico
├── Students/
│   ├── Infrastructure/Http/
│   │   └── StudentController.php            # Enriquece cursos con progreso y evaluaciones
│   ├── Infrastructure/
│   │   └── EloquentStudentRepository.php    # Consulta de cursos con progreso/notas
│   └── Domain/
│       └── StudentRepository.php            # Contrato actualizado
├── Progress/                                # Consulta de avance de lecciones
└── Assessments/                             # Consulta de intentos y notas de evaluaciones

resources/js/
├── composables/
│   └── useScheduleCalendar.ts               # Paleta y mapeo por courseId/courseName
├── pages/
│   ├── Scheduling/
│   │   └── MySchedule.vue                   # Vista de horario docente
│   ├── Teacher/
│   │   └── Classes.vue                      # Tarjetas de clases ordenadas
│   └── Students/
│       └── Show.vue                         # Ficha de estudiante integrada por curso

tests/Feature/
├── Scheduling/
│   ├── MyScheduleColorTest.php
│   └── TeacherClassOrderTest.php
└── Students/
    └── StudentShowIntegratedProgressTest.php
```

## Phase 0: Outline & Research
*Completado en `research.md`*. Todas las decisiones técnicas han sido evaluadas y fundamentadas sin incógnitas abiertas.

## Phase 1: Design & Contracts
*Completado en `data-model.md`, `contracts/endpoints.md` y `quickstart.md`*. Estructuras, contratos y guías de verificación detalladas.
