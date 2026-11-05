<?php

namespace Database\Seeders;

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Choice;
use App\Modules\Assessments\Infrastructure\Models\Question;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Students\Infrastructure\Models\Student;
use App\Shared\RichText\RichTextDocumentValidator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('AssessmentSeeder no puede ejecutarse en producción.');

            return;
        }

        $courses = Course::query()->where('status', 'published')->get();

        if ($courses->isEmpty()) {
            $this->command?->warn('No hay cursos publicados para crear evaluaciones.');

            return;
        }

        $now = CarbonImmutable::now();

        foreach ($courses as $course) {
            $assessmentsData = $this->getAssessmentsForCourse($course);

            foreach ($assessmentsData as $data) {
                /** @var Assessment $assessment */
                $assessment = Assessment::query()->firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'title' => $data['title'],
                    ],
                    [
                        'instructions' => RichTextDocumentValidator::fromPlainText($data['instructions']),
                        'max_attempts' => $data['max_attempts'],
                        'passing_score' => $data['passing_score'],
                        'total_score' => $data['total_score'],
                        'is_published' => true,
                    ]
                );

                // Si ya tiene preguntas creadas, continuar
                if ($assessment->questions()->exists()) {
                    $this->seedAttemptsForAssessment($assessment, $now);

                    continue;
                }

                // Crear preguntas y alternativas
                foreach ($data['questions'] as $position => $qData) {
                    /** @var Question $question */
                    $question = $assessment->questions()->create([
                        'statement' => RichTextDocumentValidator::fromPlainText($qData['statement']),
                        'points' => $qData['points'],
                        'position' => $position + 1,
                    ]);

                    foreach ($qData['choices'] as $cData) {
                        $question->choices()->create([
                            'text' => $cData['text'],
                            'is_correct' => $cData['is_correct'],
                        ]);
                    }
                }

                $this->seedAttemptsForAssessment($assessment, $now);
            }
        }

        $this->command?->info('Evaluaciones e intentos de estudiantes sembrados exitosamente.');
    }

    /**
     * @return list<array{title: string, instructions: string, max_attempts: int, passing_score: int, total_score: int, questions: list<array{statement: string, points: int, choices: list<array{text: string, is_correct: bool}>}>}>
     */
    private function getAssessmentsForCourse(Course $course): array
    {
        $courseSlug = strtolower(str_replace(' ', '-', $course->name));

        if (str_contains($courseSlug, 'python')) {
            return [
                [
                    'title' => 'Evaluación Parcial: Fundamentos de Python',
                    'instructions' => 'Responde a las siguientes preguntas sobre variables, tipos de datos y estructuras de control.',
                    'max_attempts' => 2,
                    'passing_score' => 12,
                    'total_score' => 20,
                    'questions' => [
                        [
                            'statement' => '¿Cuál de las siguientes es una estructura de datos mutable en Python?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'List (lista)', 'is_correct' => true],
                                ['text' => 'Tuple (tupla)', 'is_correct' => false],
                                ['text' => 'String (cadena)', 'is_correct' => false],
                                ['text' => 'Frozenset', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué operador se utiliza para realizar la división entera en Python?',
                            'points' => 5,
                            'choices' => [
                                ['text' => '//', 'is_correct' => true],
                                ['text' => '/', 'is_correct' => false],
                                ['text' => '%', 'is_correct' => false],
                                ['text' => '**', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Cuál es la salida de print(type([]))?',
                            'points' => 5,
                            'choices' => [
                                ['text' => "<class 'list'>", 'is_correct' => true],
                                ['text' => "<class 'array'>", 'is_correct' => false],
                                ['text' => "<class 'tuple'>", 'is_correct' => false],
                                ['text' => "<class 'dict'>", 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué palabra clave se usa para definir una función en Python?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'def', 'is_correct' => true],
                                ['text' => 'function', 'is_correct' => false],
                                ['text' => 'fun', 'is_correct' => false],
                                ['text' => 'define', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Examen Final: Programación Orientada a Objetos en Python',
                    'instructions' => 'Demuestra tus conocimientos sobre clases, herencia, encapsulamiento y métodos mágicos.',
                    'max_attempts' => 2,
                    'passing_score' => 14,
                    'total_score' => 20,
                    'questions' => [
                        [
                            'statement' => '¿Cuál es el método constructor por defecto de una clase en Python?',
                            'points' => 5,
                            'choices' => [
                                ['text' => '__init__', 'is_correct' => true],
                                ['text' => '__construct__', 'is_correct' => false],
                                ['text' => '__new__', 'is_correct' => false],
                                ['text' => '__start__', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué parámetro representa la instancia actual dentro de los métodos de clase?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'self', 'is_correct' => true],
                                ['text' => 'this', 'is_correct' => false],
                                ['text' => 'cls', 'is_correct' => false],
                                ['text' => 'instance', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué función se utiliza para invocar un método de la superclase en Python?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'super()', 'is_correct' => true],
                                ['text' => 'parent()', 'is_correct' => false],
                                ['text' => 'base()', 'is_correct' => false],
                                ['text' => 'ancestor()', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Cómo se define una convención de atributo privado en Python?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Prefijando el nombre con doble guión bajo (__)', 'is_correct' => true],
                                ['text' => 'Con la palabra clave private', 'is_correct' => false],
                                ['text' => 'Terminando el nombre con signo de dólar ($)', 'is_correct' => false],
                                ['text' => 'Usando corchetes cuadrados', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (str_contains($courseSlug, 'sql') || str_contains($courseSlug, 'datos')) {
            return [
                [
                    'title' => 'Evaluación de SQL: Consultas y Modelado',
                    'instructions' => 'Pon a prueba tus habilidades formulando consultas relacionales y filtros.',
                    'max_attempts' => 3,
                    'passing_score' => 11,
                    'total_score' => 20,
                    'questions' => [
                        [
                            'statement' => '¿Qué cláusula se utiliza para filtrar registros después de una agregación con GROUP BY?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'HAVING', 'is_correct' => true],
                                ['text' => 'WHERE', 'is_correct' => false],
                                ['text' => 'FILTER', 'is_correct' => false],
                                ['text' => 'ORDER BY', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué tipo de JOIN devuelve todas las filas de la tabla izquierda aunque no haya coincidencias en la derecha?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'LEFT JOIN', 'is_correct' => true],
                                ['text' => 'INNER JOIN', 'is_correct' => false],
                                ['text' => 'RIGHT JOIN', 'is_correct' => false],
                                ['text' => 'CROSS JOIN', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué comando SQL se utiliza para eliminar la estructura de una tabla por completo?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'DROP TABLE', 'is_correct' => true],
                                ['text' => 'DELETE TABLE', 'is_correct' => false],
                                ['text' => 'TRUNCATE TABLE', 'is_correct' => false],
                                ['text' => 'REMOVE TABLE', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Cuál es la función del índice en una base de datos relacional?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Acelerar la recuperación de datos en consultas', 'is_correct' => true],
                                ['text' => 'Encriptar automáticamente la información', 'is_correct' => false],
                                ['text' => 'Evitar caídas del servidor', 'is_correct' => false],
                                ['text' => 'Comprimir el tamaño en disco de las tablas', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (str_contains($courseSlug, 'react') || str_contains($courseSlug, 'javascript')) {
            return [
                [
                    'title' => 'Evaluación de Fundamentos y Componentes',
                    'instructions' => 'Preguntas sobre renderizado, reactividad y ciclo de vida de componentes.',
                    'max_attempts' => 2,
                    'passing_score' => 12,
                    'total_score' => 20,
                    'questions' => [
                        [
                            'statement' => '¿Cuál de los siguientes hooks permite preservar estado local en un componente funcional?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'useState', 'is_correct' => true],
                                ['text' => 'useEffect', 'is_correct' => false],
                                ['text' => 'useContext', 'is_correct' => false],
                                ['text' => 'useCallback', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Por qué es fundamental proporcionar una propiedad "key" única en listas?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Para que el reconciliador de React identifique qué elementos cambiaron o se movieron', 'is_correct' => true],
                                ['text' => 'Para aplicar estilos CSS automáticos', 'is_correct' => false],
                                ['text' => 'Para cifrar los datos del cliente', 'is_correct' => false],
                                ['text' => 'Es opcional y solo sirve como comentario', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué método o hook se utiliza para manejar efectos secundarios como llamadas a API?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'useEffect', 'is_correct' => true],
                                ['text' => 'useSideEffect', 'is_correct' => false],
                                ['text' => 'useLifecycle', 'is_correct' => false],
                                ['text' => 'useAction', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Qué es JSX en el ecosistema de JavaScript/React?',
                            'points' => 5,
                            'choices' => [
                                ['text' => 'Una extensión de sintaxis que permite escribir etiquetas similares a HTML en JavaScript', 'is_correct' => true],
                                ['text' => 'Un nuevo lenguaje compilado que reemplaza a TypeScript', 'is_correct' => false],
                                ['text' => 'Un motor de base de datos en memoria para el navegador', 'is_correct' => false],
                                ['text' => 'Un empaquetador alternativo a Webpack', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ];
        }

        // Evaluación genérica para otros cursos
        return [
            [
                'title' => 'Evaluación Diagnóstica: '.$course->name,
                'instructions' => 'Demuestra los conceptos y habilidades aprendidas a lo largo de este curso.',
                'max_attempts' => 2,
                'passing_score' => 12,
                'total_score' => 20,
                'questions' => [
                    [
                        'statement' => '¿Cuál es el objetivo principal del curso "'.$course->name.'"?',
                        'points' => 5,
                        'choices' => [
                            ['text' => $course->objectives ?: 'Desarrollar competencias prácticas y teóricas fundamentales.', 'is_correct' => true],
                            ['text' => 'Aprender teoría sin aplicación práctica ni proyectos.', 'is_correct' => false],
                            ['text' => 'Memorizar definiciones técnicas sin contexto.', 'is_correct' => false],
                        ],
                    ],
                    [
                        'statement' => '¿Cuál de las siguientes es una buena práctica en el estudio y aplicación de esta disciplina?',
                        'points' => 5,
                        'choices' => [
                            ['text' => 'Realizar prácticas continuas y revisar el material complementario.', 'is_correct' => true],
                            ['text' => 'Dejar todo el contenido para el último día sin repasar.', 'is_correct' => false],
                            ['text' => 'Ignorar las dudas y no consultar con el docente.', 'is_correct' => false],
                        ],
                    ],
                    [
                        'statement' => 'En el contexto de la materia, ¿qué factor asegura un aprendizaje efectivo?',
                        'points' => 5,
                        'choices' => [
                            ['text' => 'Comprender los fundamentos y resolver ejercicios paso a paso.', 'is_correct' => true],
                            ['text' => 'Copiar soluciones sin entender su funcionamiento interno.', 'is_correct' => false],
                            ['text' => 'Saltarse las unidades introductorias.', 'is_correct' => false],
                        ],
                    ],
                    [
                        'statement' => '¿Cómo se valida la asimilación correcta de los temas estudiados?',
                        'points' => 5,
                        'choices' => [
                            ['text' => 'A través de evaluaciones continuas y resolución de casos de uso.', 'is_correct' => true],
                            ['text' => 'Únicamente mirando las diapositivas de forma pasiva.', 'is_correct' => false],
                            ['text' => 'No es necesario validar el aprendizaje.', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function seedAttemptsForAssessment(Assessment $assessment, CarbonImmutable $now): void
    {
        // Buscar estudiantes con matrícula activa en este curso
        $enrolledStudents = Student::query()
            ->whereHas('enrollments', function ($query) use ($assessment): void {
                $query->where('course_id', $assessment->course_id)
                    ->where('status', EnrollmentStatus::Active);
            })
            ->get();

        if ($enrolledStudents->isEmpty()) {
            return;
        }

        $questions = $assessment->questions()->with('choices')->get();
        if ($questions->isEmpty()) {
            return;
        }

        // Vamos a simular que un subconjunto de estudiantes realiza la evaluación
        // Variando:
        // - Estudiantes con 100% de aciertos (aprobados sobresalientes)
        // - Estudiantes con mezcla de respuestas correctas e incorrectas (aprobados con nota media)
        // - Estudiantes con mayoría de respuestas incorrectas (desaprobados)
        // - Estudiantes con 2 intentos (el primero desaprobado y el segundo aprobado)

        $studentsToAttempt = $enrolledStudents->take(6);

        foreach ($studentsToAttempt as $index => $student) {
            // Si el estudiante ya tiene un intento en esta evaluación, omitir
            if ($assessment->attempts()->where('student_id', $student->id)->exists()) {
                continue;
            }

            // Distribuir perfiles de desempeño según el índice
            $performanceType = $index % 4;

            if ($performanceType === 0) {
                // Perfil 0: Excelente (todas correctas) -> Aprobado
                $this->createCompletedAttempt(
                    $assessment,
                    $student,
                    $questions,
                    attemptNumber: 1,
                    pattern: 'all_correct',
                    startedAt: $now->subDays(3)->addHours($index),
                    submittedAt: $now->subDays(3)->addHours($index)->addMinutes(25)
                );
            } elseif ($performanceType === 1) {
                // Perfil 1: Regular (mitad correctas, mitad incorrectas)
                $this->createCompletedAttempt(
                    $assessment,
                    $student,
                    $questions,
                    attemptNumber: 1,
                    pattern: 'mixed',
                    startedAt: $now->subDays(2)->addHours($index),
                    submittedAt: $now->subDays(2)->addHours($index)->addMinutes(35)
                );
            } elseif ($performanceType === 2) {
                // Perfil 2: Desaprobado (mayoría incorrectas)
                $this->createCompletedAttempt(
                    $assessment,
                    $student,
                    $questions,
                    attemptNumber: 1,
                    pattern: 'mostly_wrong',
                    startedAt: $now->subDays(1)->addHours($index),
                    submittedAt: $now->subDays(1)->addHours($index)->addMinutes(15)
                );
            } elseif ($performanceType === 3 && $assessment->max_attempts >= 2) {
                // Perfil 3: Dos intentos (primer intento desaprobado, segundo intento aprobado)
                $this->createCompletedAttempt(
                    $assessment,
                    $student,
                    $questions,
                    attemptNumber: 1,
                    pattern: 'mostly_wrong',
                    startedAt: $now->subDays(4)->addHours($index),
                    submittedAt: $now->subDays(4)->addHours($index)->addMinutes(20)
                );

                $this->createCompletedAttempt(
                    $assessment,
                    $student,
                    $questions,
                    attemptNumber: 2,
                    pattern: 'all_correct',
                    startedAt: $now->subDays(2)->addHours($index),
                    submittedAt: $now->subDays(2)->addHours($index)->addMinutes(30)
                );
            }
        }
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    private function createCompletedAttempt(
        Assessment $assessment,
        Student $student,
        $questions,
        int $attemptNumber,
        string $pattern,
        CarbonImmutable $startedAt,
        CarbonImmutable $submittedAt
    ): void {
        DB::transaction(function () use ($assessment, $student, $questions, $attemptNumber, $pattern, $startedAt, $submittedAt): void {
            $attempt = $assessment->attempts()->create([
                'student_id' => $student->id,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'started_at' => $startedAt,
            ]);

            $totalCalculatedScore = 0;

            foreach ($questions as $qIndex => $question) {
                /** @var Collection<int, Choice> $choices */
                $choices = $question->choices;
                $correctChoice = $choices->firstWhere('is_correct', true);
                $wrongChoice = $choices->firstWhere('is_correct', false) ?? $correctChoice;

                $chosenChoice = match ($pattern) {
                    'all_correct' => $correctChoice,
                    'mostly_wrong' => ($qIndex === 0) ? $correctChoice : $wrongChoice,
                    'mixed' => ($qIndex % 2 === 0) ? $correctChoice : $wrongChoice,
                    default => $correctChoice,
                };

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
                'submitted_at' => $submittedAt,
            ]);
        });
    }
}
