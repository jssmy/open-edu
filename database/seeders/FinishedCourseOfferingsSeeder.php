<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Question;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Students\Infrastructure\Models\Student;
use App\Shared\RichText\RichTextDocumentValidator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinishedCourseOfferingsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('FinishedCourseOfferingsSeeder no puede ejecutarse en producción.');

            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);

        $teachers = User::role(Role::Docente->value)->where('is_active', true)->orderBy('id')->get();
        if ($teachers->isEmpty()) {
            $this->command?->error('No hay docentes para asignar a las clases finalizadas.');

            return;
        }

        $classrooms = Classroom::query()->where('is_active', true)->get();

        // Estudiantes que matricularemos en las clases finalizadas
        $students = Student::query()->where('is_active', true)->orderBy('id')->take(10)->get();

        $today = CarbonImmutable::now()->startOfDay();

        /** @var list<array{
         *     course_name: string,
         *     description: string,
         *     objectives: string,
         *     group_code: string,
         *     modality: string,
         *     ended_days_ago: int,
         *     duration_weeks: int,
         *     max_students: int,
         *     lessons: list<string>,
         *     assessment?: array{
         *         title: string,
         *         instructions: string,
         *         passing_score: int,
         *         total_score: int,
         *         questions: list<array{statement: string, points: int, choices: list<array{text: string, is_correct: bool}>}>
         *     }
         * }> $finishedBlueprints */
        $finishedBlueprints = [
            [
                'course_name' => 'Fundamentos de Algoritmos y Lógica de Programación',
                'description' => 'Curso completado sobre estructuras de datos básicas, pseudocódigo y resolución lógica de problemas.',
                'objectives' => 'Desarrollar el pensamiento algorítmico y estructurar soluciones a problemas computacionales.',
                'group_code' => 'GRP-ALGO-FIN',
                'modality' => 'in_person',
                'ended_days_ago' => 14, // Finalizó hace 2 semanas
                'duration_weeks' => 8,
                'max_students' => 30,
                'lessons' => [
                    'Introducción a los algoritmos y diagramas de flujo',
                    'Variables, operadores y estructuras condicionales',
                    'Bucles e iteración controlada',
                    'Arreglos unidimensionales y bidimensionales',
                    'Funciones y modularización básica',
                ],
                'assessment' => [
                    'title' => 'Examen Final de Algoritmos y Lógica',
                    'instructions' => 'Evaluación sumativa final rendida al concluir el ciclo académico.',
                    'passing_score' => 12,
                    'total_score' => 20,
                    'questions' => [
                        [
                            'statement' => '¿Cuál es la complejidad temporal de una búsqueda binaria en el peor de los casos?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'O(log n)', 'is_correct' => true],
                                ['text' => 'O(n)', 'is_correct' => false],
                                ['text' => 'O(n^2)', 'is_correct' => false],
                                ['text' => 'O(1)', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué estructura de datos opera bajo el principio LIFO (Last In, First Out)?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Pila (Stack)', 'is_correct' => true],
                                ['text' => 'Cola (Queue)', 'is_correct' => false],
                                ['text' => 'Árbol binario', 'is_correct' => false],
                                ['text' => 'Grafo', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué condición debe cumplir una lista para aplicar búsqueda binaria?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Estar previamente ordenada', 'is_correct' => true],
                                ['text' => 'Contener únicamente números primos', 'is_correct' => false],
                                ['text' => 'Tener una cantidad par de elementos', 'is_correct' => false],
                                ['text' => 'No tener índices negativos', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué caracteriza a un algoritmo voraz (greedy algorithm)?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Toma la opción óptima local en cada paso esperando alcanzar el óptimo global', 'is_correct' => true],
                                ['text' => 'Explora todas las combinaciones posibles recursivamente', 'is_correct' => false],
                                ['text' => 'Ordena aleatoriamente el conjunto de entrada', 'is_correct' => false],
                                ['text' => 'Siempre utiliza memoria secundaria', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'course_name' => 'Diseño de Experiencia de Usuario (UX/UI)',
                'description' => 'Curso introductorio a wireframing, prototipado en Figma y principios de usabilidad digital.',
                'objectives' => 'Aprender las fases de diseño centrado en el usuario e implementar prototipos navegables.',
                'group_code' => 'GRP-UXUI-FIN',
                'modality' => 'virtual',
                'ended_days_ago' => 30, // Finalizó hace 1 mes
                'duration_weeks' => 6,
                'max_students' => 35,
                'lessons' => [
                    'Fundamentos de UX: Investigación y Personas',
                    'Arquitectura de información y User Journeys',
                    'Diseño UI: Tipografía, paletas de color y espaciado',
                    'Componentes y sistemas de diseño en Figma',
                    'Pruebas de usabilidad y entrega a desarrollo',
                ],
                'assessment' => [
                    'title' => 'Evaluación de Cierre: Usabilidad y Prototipado UX/UI',
                    'instructions' => 'Evaluación sobre heurísticas de Nielsen y principios de diseño visual.',
                    'passing_score' => 12,
                    'total_score' => 20,
                    'questions' => [
                        [
                            'statement' => '¿Cuál de las siguientes corresponde a una de las 10 heurísticas de Jakob Nielsen?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Visibilidad del estado del sistema', 'is_correct' => true],
                                ['text' => 'Uso obligatorio del modo oscuro', 'is_correct' => false],
                                ['text' => 'Animaciones tridimensionales en cada botón', 'is_correct' => false],
                                ['text' => 'Tipografías serif en toda la aplicación', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué propósito principal tiene un wireframe de baja fidelidad?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Definir la estructura y jerarquía de contenidos sin distraer con colores finales', 'is_correct' => true],
                                ['text' => 'Servir como código fuente de producción', 'is_correct' => false],
                                ['text' => 'Elegir las imágenes fotográficas del producto', 'is_correct' => false],
                                ['text' => 'Determinar el servidor de alojamiento web', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => 'En accesibilidad web (WCAG), ¿cuál es el ratio de contraste mínimo recomendado para texto normal?',
                            'points' => 5,
                            'choices' => [
                                ['text' => '4.5:1 (Nivel AA)', 'is_correct' => true],
                                ['text' => '1.5:1', 'is_correct' => false],
                                ['text' => '10:1 obligatoriamente', 'is_correct' => false],
                                ['text' => '2:1', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué técnica permite ordenar la estructura de menús con participación directa de los usuarios?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Card Sorting', 'is_correct' => true],
                                ['text' => 'A/B Testing en producción', 'is_correct' => false],
                                ['text' => 'Heatmaps térmicos', 'is_correct' => false],
                                ['text' => 'Eye Tracking de laboratorio', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'course_name' => 'Seguridad Informática y Buenas Prácticas Web',
                'description' => 'Capacitación sobre vulnerabilidades OWASP Top 10, autenticación segura y cifrado de datos.',
                'objectives' => 'Identificar y mitigar vulnerabilidades comunes en aplicaciones web modernas.',
                'group_code' => 'GRP-SEC-FIN',
                'modality' => 'hybrid',
                'ended_days_ago' => 45, // Finalizó hace 1 mes y medio
                'duration_weeks' => 8,
                'max_students' => 25,
                'lessons' => [
                    'Conceptos fundamentales de ciberseguridad',
                    'Inyecciones SQL y mitigación con sentencias preparadas',
                    'Cross-Site Scripting (XSS) y CSRF tokens',
                    'Autenticación robusta, hashing y JWT',
                    'Auditoría y escaneo básico de vulnerabilidades',
                ],
            ],
        ];

        foreach ($finishedBlueprints as $idx => $blueprint) {
            $course = Course::query()->firstOrCreate(
                ['name' => $blueprint['course_name']],
                [
                    'description' => $blueprint['description'],
                    'objectives' => $blueprint['objectives'],
                    'status' => 'published',
                ]
            );

            if ($course->status !== 'published') {
                $course->update(['status' => 'published']);
            }

            // Crear unidad y lecciones si no existen
            $unit = $course->units()->firstOrCreate(
                ['title' => 'Unidad 1: Contenido Completo del Curso'],
                ['position' => 1]
            );

            $createdLessons = [];
            foreach ($blueprint['lessons'] as $lPos => $lTitle) {
                $createdLessons[] = $unit->lessons()->firstOrCreate(
                    ['title' => $lTitle],
                    [
                        'position' => $lPos + 1,
                        'content_type' => 'text',
                        'content' => RichTextDocumentValidator::fromPlainText("Contenido de la lección: {$lTitle}."),
                        'is_archived' => false,
                    ]
                );
            }

            $teacher = $teachers[$idx % $teachers->count()];
            $course->teachers()->syncWithoutDetaching([$teacher->id]);

            // Fechas en el pasado:
            $endsOn = $today->subDays($blueprint['ended_days_ago'])->toDateString();
            $startsOn = $today->subDays($blueprint['ended_days_ago'])->subWeeks($blueprint['duration_weeks'])->toDateString();

            /** @var CourseOffering $offering */
            $offering = CourseOffering::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'group_code' => $blueprint['group_code'],
                ],
                [
                    'starts_on' => $startsOn,
                    'ends_on' => $endsOn,
                    'max_students' => $blueprint['max_students'],
                    'modality' => $blueprint['modality'],
                    'status' => 'published',
                ]
            );

            $offering->teachers()->sync([$teacher->id]);

            // Sesiones en el pasado concluidas
            $offering->sessions()->delete();
            $classroom = $classrooms->isNotEmpty() ? $classrooms[$idx % $classrooms->count()] : null;

            $firstSessionDate = $startsOn;
            $finalSessionDate = $endsOn;

            $offering->sessions()->create([
                'title' => 'Sesión inaugural',
                'session_date' => $firstSessionDate,
                'starts_at' => '10:00',
                'ends_at' => '12:00',
                'teacher_id' => $teacher->id,
                'classroom_id' => $blueprint['modality'] !== 'virtual' ? $classroom?->id : null,
                'meeting_url' => $blueprint['modality'] !== 'in_person' ? 'https://meet.openedu.test/clase-'.strtolower($blueprint['group_code']) : null,
                'status' => 'completed',
            ]);

            $offering->sessions()->create([
                'title' => 'Sesión de clausura y conclusiones',
                'session_date' => $finalSessionDate,
                'starts_at' => '10:00',
                'ends_at' => '12:00',
                'teacher_id' => $teacher->id,
                'classroom_id' => $blueprint['modality'] !== 'virtual' ? $classroom?->id : null,
                'meeting_url' => $blueprint['modality'] !== 'in_person' ? 'https://meet.openedu.test/clase-'.strtolower($blueprint['group_code']) : null,
                'status' => 'completed',
            ]);

            // Crear evaluación si está definida en el blueprint
            $assessment = null;
            if (isset($blueprint['assessment'])) {
                $assessmentData = $blueprint['assessment'];
                /** @var Assessment $assessment */
                $assessment = Assessment::query()->firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'title' => $assessmentData['title'],
                    ],
                    [
                        'instructions' => RichTextDocumentValidator::fromPlainText($assessmentData['instructions']),
                        'max_attempts' => 2,
                        'passing_score' => $assessmentData['passing_score'],
                        'total_score' => $assessmentData['total_score'],
                        'is_published' => true,
                    ]
                );

                if (! $assessment->questions()->exists()) {
                    foreach ($assessmentData['questions'] as $qPos => $qDef) {
                        /** @var Question $question */
                        $question = $assessment->questions()->create([
                            'statement' => RichTextDocumentValidator::fromPlainText($qDef['statement']),
                            'points' => $qDef['points'],
                            'position' => $qPos + 1,
                        ]);

                        foreach ($qDef['choices'] as $cDef) {
                            $question->choices()->create([
                                'text' => $cDef['text'],
                                'is_correct' => $cDef['is_correct'],
                            ]);
                        }
                    }
                }
            }

            // Matricular a los estudiantes
            foreach ($students as $sIdx => $student) {
                Enrollment::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'course_offering_id' => $offering->id,
                        'status' => EnrollmentStatus::Active,
                        'enrolled_at' => CarbonImmutable::parse($startsOn)->subDays(3),
                    ]
                );

                // Progreso histórico de lecciones
                $completedCount = ($sIdx % 2 === 0) ? count($createdLessons) : (int) ceil(count($createdLessons) / 2);
                for ($k = 0; $k < $completedCount; $k++) {
                    if (isset($createdLessons[$k])) {
                        DB::table('lesson_progress')->insertOrIgnore([
                            'student_id' => $student->id,
                            'lesson_id' => $createdLessons[$k]->id,
                            'completed_at' => CarbonImmutable::parse($startsOn)->addDays(5 + ($k * 3)),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Si la clase tiene evaluación, sembrar intento histórico finalizado antes de la fecha de cierre
                if ($assessment !== null) {
                    $this->seedHistoricalAttemptForStudent(
                        $assessment,
                        $student,
                        $sIdx,
                        CarbonImmutable::parse($endsOn)->subDays(2)
                    );
                }
            }
        }

        $this->command?->info('Semilla de clases finalizadas (con evaluaciones) ejecutada con éxito.');
    }

    private function seedHistoricalAttemptForStudent(
        Assessment $assessment,
        Student $student,
        int $studentIndex,
        CarbonImmutable $submissionDate
    ): void {
        if ($assessment->attempts()->where('student_id', $student->id)->exists()) {
            return;
        }

        $questions = $assessment->questions()->with('choices')->get();
        if ($questions->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($assessment, $student, $questions, $studentIndex, $submissionDate): void {
            $attempt = $assessment->attempts()->create([
                'student_id' => $student->id,
                'attempt_number' => 1,
                'status' => 'in_progress',
                'started_at' => $submissionDate->subMinutes(30),
            ]);

            $totalCalculatedScore = 0;
            // Alternar: algunos estudiantes aprueban con 20 o 15, otros tienen notas menores (10 o 5)
            $isHighPerformer = ($studentIndex % 2 === 0);

            foreach ($questions as $qIndex => $question) {
                $choices = $question->choices;
                $correctChoice = $choices->firstWhere('is_correct', true);
                $wrongChoice = $choices->firstWhere('is_correct', false) ?? $correctChoice;

                $chosenChoice = $isHighPerformer
                    ? $correctChoice
                    : (($qIndex === 0 || $qIndex === 1) ? $correctChoice : $wrongChoice);

                if ($chosenChoice->is_correct) {
                    $totalCalculatedScore += $question->points;
                }

                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'choice_id' => $chosenChoice->id,
                ]);
            }

            $passed = $totalCalculatedScore >= $assessment->passing_score;

            $attempt->update([
                'score' => $totalCalculatedScore,
                'passed' => $passed,
                'status' => 'submitted',
                'submitted_at' => $submissionDate,
            ]);
        });
    }
}
