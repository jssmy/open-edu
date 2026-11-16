<?php

namespace Database\Seeders;

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Question;
use App\Modules\Courses\Domain\CourseTargetAudience;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\CourseCategory;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Database\Seeder;

class TeacherCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CourseCategorySeeder::class);

        $onboardingCategory = CourseCategory::query()
            ->where('slug', 'onboarding')
            ->firstOrFail();

        $capacitacionCategory = CourseCategory::query()
            ->where('slug', 'capacitacion')
            ->firstOrFail();

        $this->assignStudentCategoriesToUncategorizedStudentCourses();

        // Renombrar cursos previos si fueron sembrados con el nombre antiguo
        Course::query()
            ->where('name', 'Onboarding Docente: Cultura y Metodología Open-Edu')
            ->update(['name' => 'Onboarding Docente: Cultura y Metodología Aula Virtual']);
        Course::query()
            ->where('name', 'Dominio de la Plataforma Open-Edu y Gestión de Clases')
            ->update(['name' => 'Dominio del Aula Virtual y Gestión de Clases']);

        foreach ($this->teacherCourseDefinitions() as $definition) {
            $category = $definition['category_slug'] === 'onboarding'
                ? $onboardingCategory
                : $capacitacionCategory;

            $course = Course::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'category_id' => $category->id,
                    'target_audience' => CourseTargetAudience::Teacher->value,
                    'issues_certificate' => $definition['issues_certificate'],
                    'description' => RichTextDocumentValidator::fromPlainText($definition['description']),
                    'objectives' => RichTextDocumentValidator::fromPlainText($definition['objectives']),
                    'intro_video_provider' => 'youtube',
                    'intro_video_id' => $definition['video'],
                    'status' => 'published',
                ],
            );

            foreach ($definition['units'] as $unitIndex => $unitDef) {
                $unit = $course->units()->firstOrCreate(
                    ['title' => $unitDef['title']],
                    ['position' => $unitIndex + 1],
                );

                foreach ($this->buildLessonsForUnit($definition['slug'], $unitIndex, $unitDef) as $lessonIndex => $lessonDef) {
                    $unit->lessons()->updateOrCreate(
                        ['title' => $lessonDef['title']],
                        [
                            'position' => $lessonIndex + 1,
                            'content_type' => $lessonDef['type'],
                            'content' => $lessonDef['content'],
                            'is_archived' => false,
                        ],
                    );
                }
            }

            if ($definition['assessment'] !== null) {
                $this->seedAssessment($course, $definition['assessment']);
            }
        }
    }

    private function assignStudentCategoriesToUncategorizedStudentCourses(): void
    {
        $studentCategories = CourseCategory::query()
            ->where('role_name', 'estudiante')
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->keyBy('slug');

        if ($studentCategories->isEmpty()) {
            return;
        }

        $digitalId = $studentCategories->get('digital')?->id;
        $logicaId = $studentCategories->get('logica')?->id;
        $creatividadId = $studentCategories->get('creatividad')?->id;
        $expresionId = $studentCategories->get('expresion')?->id;
        $fallbackId = $studentCategories->first()->id;

        $uncategorizedCourses = Course::query()
            ->whereNull('category_id')
            ->where('target_audience', CourseTargetAudience::Student->value)
            ->get();

        foreach ($uncategorizedCourses as $course) {
            $lowerName = mb_strtolower($course->name);
            $categoryId = match (true) {
                str_contains($lowerName, 'datos') || str_contains($lowerName, 'sql') || str_contains($lowerName, 'automático') => $logicaId ?? $fallbackId,
                str_contains($lowerName, 'diseño') || str_contains($lowerName, 'html') || str_contains($lowerName, 'react') => $creatividadId ?? $fallbackId,
                str_contains($lowerName, 'introducción') || str_contains($lowerName, 'herramientas') => $expresionId ?? $fallbackId,
                default => $digitalId ?? $fallbackId,
            };

            $course->update(['category_id' => $categoryId]);
        }
    }

    /**
     * @param  array{title: string, instructions: string, max_attempts: int, passing_score: int, total_score: int, questions: list<array{statement: string, points: int, choices: list<array{text: string, is_correct: bool}>}>}  $assessmentDef
     */
    private function seedAssessment(Course $course, array $assessmentDef): void
    {
        /** @var Assessment $assessment */
        $assessment = Assessment::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => $assessmentDef['title'],
            ],
            [
                'instructions' => RichTextDocumentValidator::fromPlainText($assessmentDef['instructions']),
                'max_attempts' => $assessmentDef['max_attempts'],
                'passing_score' => $assessmentDef['passing_score'],
                'total_score' => $assessmentDef['total_score'],
                'is_published' => true,
            ],
        );

        if ($assessment->questions()->exists()) {
            return;
        }

        foreach ($assessmentDef['questions'] as $position => $questionDef) {
            /** @var Question $question */
            $question = $assessment->questions()->create([
                'statement' => RichTextDocumentValidator::fromPlainText($questionDef['statement']),
                'points' => $questionDef['points'],
                'position' => $position + 1,
            ]);

            foreach ($questionDef['choices'] as $choiceDef) {
                $question->choices()->create([
                    'text' => $choiceDef['text'],
                    'is_correct' => $choiceDef['is_correct'],
                ]);
            }
        }
    }

    /**
     * @param  array{title: string, summary: string, points: list<string>, video: string, link: array{title: string, url: string}}  $unit
     * @return list<array{title: string, type: string, content: array<string, mixed>|string}>
     */
    private function buildLessonsForUnit(string $slug, int $unitIndex, array $unit): array
    {
        $title = $unit['title'];
        $document = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $title]]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $unit['summary']]]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Objetivos clave de esta lección:']]],
                [
                    'type' => 'bulletList',
                    'content' => array_map(
                        fn (string $point): array => [
                            'type' => 'listItem',
                            'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $point]]]],
                        ],
                        $unit['points'],
                    ),
                ],
                [
                    'type' => 'blockquote',
                    'content' => [[
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Aplica estas pautas en tus sesiones académicas para enriquecer la experiencia de tus estudiantes.', 'marks' => [['type' => 'italic']]]],
                    ]],
                ],
            ],
        ];

        return [
            [
                'title' => "Guía: {$title}",
                'type' => 'text',
                'content' => RichTextDocumentValidator::isValid($document)
                    ? $document
                    : RichTextDocumentValidator::fromPlainText($unit['summary']),
            ],
            [
                'title' => "Cápsula en video: {$title}",
                'type' => 'video',
                'content' => 'https://www.youtube.com/watch?v='.$unit['video'],
            ],
            [
                'title' => "Recurso complementario: {$unit['link']['title']}",
                'type' => 'link',
                'content' => $unit['link']['url'],
            ],
        ];
    }

    /**
     * @return list<array{
     *     slug: string,
     *     category_slug: 'onboarding'|'capacitacion',
     *     name: string,
     *     description: string,
     *     objectives: string,
     *     video: string,
     *     issues_certificate: bool,
     *     units: list<array{title: string, summary: string, points: list<string>, video: string, link: array{title: string, url: string}}>,
     *     assessment: array{title: string, instructions: string, max_attempts: int, passing_score: int, total_score: int, questions: list<array{statement: string, points: int, choices: list<array{text: string, is_correct: bool}>}>}|null
     * }>
     */
    private function teacherCourseDefinitions(): array
    {
        return [
            [
                'slug' => 'onboarding-cultura-openedu',
                'category_slug' => 'onboarding',
                'name' => 'Onboarding Docente: Cultura y Metodología Aula Virtual',
                'description' => 'Conoce la misión pedagógica de la Asociación Un Día de Esperanza, el modelo de acompañamiento estudiantil y los estándares de calidad académica en el Aula Virtual.',
                'objectives' => 'Integrarse al equipo docente comprendiendo el modelo educativo, los canales de comunicación y los indicadores de éxito en el aula.',
                'video' => 'rfscVS0vtbw',
                'issues_certificate' => true,
                'units' => [
                    [
                        'title' => 'Identidad y Propósito Institucional',
                        'summary' => 'Visión educativa centrada en el estudiante, valores de la Asociación Un Día de Esperanza y rol del docente facilitador.',
                        'points' => [
                            'Comprender la misión y valores de la Asociación Un Día de Esperanza',
                            'Fomentar un entorno de aprendizaje inclusivo y participativo',
                            'Aplicar el enfoque práctico en cada sesión sincrónica y asincrónica',
                        ],
                        'video' => 'rfscVS0vtbw',
                        'link' => [
                            'title' => 'Marco de Competencias Docentes UNESCO',
                            'url' => 'https://www.unesco.org/es/digital-competencies-skills/ict-cft',
                        ],
                    ],
                    [
                        'title' => 'Flujo Académico y Acompañamiento',
                        'summary' => 'Cómo preparar tus sesiones, dar seguimiento al progreso de tus grupos y coordinar con el equipo académico.',
                        'points' => [
                            'Revisar la programación semanal y los objetivos de cada unidad',
                            'Monitorear el avance de lecciones y resultados de evaluaciones',
                            'Brindar retroalimentación oportuna a los estudiantes',
                        ],
                        'video' => 'PkZNo7MFNFg',
                        'link' => [
                            'title' => 'Guía de Buenas Prácticas en Educación Digital',
                            'url' => 'https://unesdoc.unesco.org/',
                        ],
                    ],
                ],
                'assessment' => [
                    'title' => 'Evaluación de Inducción Institucional',
                    'instructions' => 'Responde las siguientes preguntas sobre la cultura y el flujo académico del Aula Virtual para acreditar tu onboarding.',
                    'max_attempts' => 3,
                    'passing_score' => 14,
                    'total_score' => 20,
                    'questions' => [
                        [
                            'statement' => '¿Cuál es el rol principal del docente dentro del modelo pedagógico del Aula Virtual?',
                            'points' => 10,
                            'choices' => [
                                ['text' => 'Facilitar el aprendizaje activo y acompañar el progreso de cada estudiante', 'is_correct' => true],
                                ['text' => 'Limitarse a dictar teoría sin interacción ni seguimiento práctico', 'is_correct' => false],
                                ['text' => 'Evaluar únicamente con un examen anual sin retroalimentación', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Dónde visualiza y gestiona el docente las sesiones programadas de sus grupos de estudiantes?',
                            'points' => 10,
                            'choices' => [
                                ['text' => 'En los módulos Mis Clases (/my/classes) y Mi Horario (/my/schedule)', 'is_correct' => true],
                                ['text' => 'En el módulo de configuración de servidores', 'is_correct' => false],
                                ['text' => 'No existe registro de sesiones para los grupos', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'onboarding-plataforma-virtual',
                'category_slug' => 'onboarding',
                'name' => 'Dominio del Aula Virtual y Gestión de Clases',
                'description' => 'Recorrido paso a paso por las herramientas del Aula Virtual: visor de clases, unidades, lecciones multimedia y evaluaciones.',
                'objectives' => 'Utilizar con fluidez todas las funcionalidades docentes del Aula Virtual desde el primer día de clase.',
                'video' => 'qz0aGYrrlhU',
                'issues_certificate' => true,
                'units' => [
                    [
                        'title' => 'Navegación en Mis Clases y Mi Horario',
                        'summary' => 'Gestión del calendario docente, modalidades presencial/virtual/híbrida y acceso al visor de clase.',
                        'points' => [
                            'Consultar horarios semanales y enlaces de videollamada',
                            'Identificar aulas asignadas y cupos de estudiantes por grupo',
                            'Utilizar el visor del curso durante el desarrollo de la sesión',
                        ],
                        'video' => 'qz0aGYrrlhU',
                        'link' => [
                            'title' => 'Recursos de Aprendizaje en Línea',
                            'url' => 'https://developer.mozilla.org/es/',
                        ],
                    ],
                ],
                'assessment' => null,
            ],
            [
                'slug' => 'onboarding-convivencia-etica',
                'category_slug' => 'onboarding',
                'name' => 'Protocolos de Convivencia, Ética y Bienestar Estudiantil',
                'description' => 'Lineamientos institucionales para promover un clima de respeto, seguridad digital y motivación en todas las modalidades.',
                'objectives' => 'Aplicar protocolos de convivencia positiva y canalizar oportunamente situaciones de apoyo estudiantil.',
                'video' => 'SWYqp7iY_Tc',
                'issues_certificate' => true,
                'units' => [
                    [
                        'title' => 'Clima de Aula y Comunicación Asertiva',
                        'summary' => 'Pautas para el trato respetuoso, resolución constructiva de dudas y motivación grupal.',
                        'points' => [
                            'Establecer acuerdos claros de participación al inicio del curso',
                            'Practicar la escucha activa y la comunicación empática',
                            'Promover la colaboración segura en entornos virtuales y presenciales',
                        ],
                        'video' => 'SWYqp7iY_Tc',
                        'link' => [
                            'title' => 'Pautas de Convivencia Escolar UNESCO',
                            'url' => 'https://www.unesco.org/es/education',
                        ],
                    ],
                ],
                'assessment' => null,
            ],
            [
                'slug' => 'capacitacion-evaluacion-formativa',
                'category_slug' => 'capacitacion',
                'name' => 'Estrategias de Evaluación Formativa y Retroalimentación Efectiva',
                'description' => 'Diseña instrumentos de evaluación centrados en el crecimiento del estudiante y domina técnicas de feedback accionable.',
                'objectives' => 'Implementar evaluaciones formativas y sumativas alineadas a los objetivos de aprendizaje de cada curso.',
                'video' => 'HXV3zeQKqGY',
                'issues_certificate' => true,
                'units' => [
                    [
                        'title' => 'Principios de la Evaluación para el Aprendizaje',
                        'summary' => 'Diferencias y complementariedad entre evaluación diagnóstica, formativa y sumativa.',
                        'points' => [
                            'Alinear preguntas de evaluación con los objetivos de la unidad',
                            'Combinar verificación conceptual con casos prácticos',
                            'Utilizar los resultados para reforzar temas clave en clase',
                        ],
                        'video' => 'HXV3zeQKqGY',
                        'link' => [
                            'title' => 'Evaluación Formativa en la Práctica',
                            'url' => 'https://www.unesco.org/es',
                        ],
                    ],
                    [
                        'title' => 'Retroalimentación Oportuna y Constructiva',
                        'summary' => 'Metodologías para brindar comentarios claros que impulsen la autonomía del estudiante.',
                        'points' => [
                            'Reconocer logros específicos antes de señalar oportunidades de mejora',
                            'Ofrecer ejemplos concretos y pasos siguientes accionables',
                            'Fomentar la autoevaluación y la reflexión metacognitiva',
                        ],
                        'video' => 'vmEHCJofslg',
                        'link' => [
                            'title' => 'Guía de Retroalimentación Pedagógica',
                            'url' => 'https://unesdoc.unesco.org/',
                        ],
                    ],
                ],
                'assessment' => [
                    'title' => 'Evaluación Final: Evaluación Formativa',
                    'instructions' => 'Selecciona la alternativa correcta en cada caso práctico sobre diseño de evaluaciones y retroalimentación.',
                    'max_attempts' => 3,
                    'passing_score' => 10,
                    'total_score' => 20,
                    'questions' => [
                        [
                            'statement' => '¿Cuál es una característica esencial de una retroalimentación formativa efectiva?',
                            'points' => 10,
                            'choices' => [
                                ['text' => 'Es específica, oportuna y orienta al estudiante sobre cómo mejorar su desempeño', 'is_correct' => true],
                                ['text' => 'Se entrega únicamente meses después de finalizado el curso sin explicaciones', 'is_correct' => false],
                                ['text' => 'Consiste solo en un número sin indicar qué criterios se evaluaron', 'is_correct' => false],
                            ],
                        ],
                        [
                            'statement' => '¿Con qué elemento deben estar directamente alineadas las preguntas de una evaluación?',
                            'points' => 10,
                            'choices' => [
                                ['text' => 'Con los objetivos de aprendizaje trabajados en las unidades del curso', 'is_correct' => true],
                                ['text' => 'Con temas ajenos al programa del curso', 'is_correct' => false],
                                ['text' => 'Con acertijos al azar sin relación con las lecciones', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'capacitacion-ia-herramientas-digitales',
                'category_slug' => 'capacitacion',
                'name' => 'Herramientas Digitales e Inteligencia Artificial para el Aula',
                'description' => 'Integra recursos interactivos, pizarras colaborativas y asistentes de IA de forma ética y pedagógica en la planificación docente.',
                'objectives' => 'Potenciar la creación de materiales didácticos y actividades dinámicas mediante herramientas digitales modernas.',
                'video' => 'i_LwzRVP7bg',
                'issues_certificate' => true,
                'units' => [
                    [
                        'title' => 'Ecosistema Digital para la Enseñanza Activa',
                        'summary' => 'Selección estratégica de recursos multimedia, simuladores y actividades interactivas.',
                        'points' => [
                            'Integrar videos breves, infografías y lecturas estructuradas',
                            'Dinamizar sesiones virtuales e híbridas con participación en vivo',
                            'Garantizar la accesibilidad de los materiales digitales',
                        ],
                        'video' => 'i_LwzRVP7bg',
                        'link' => [
                            'title' => 'IA y Educación: Guía para Docentes (UNESCO)',
                            'url' => 'https://www.unesco.org/es/digital-education/artificial-intelligence',
                        ],
                    ],
                    [
                        'title' => 'Uso Pedagógico y Ético de la IA Generativa',
                        'summary' => 'Cómo diseñar casos de estudio, rúbricas y ejercicios personalizados manteniendo el pensamiento crítico.',
                        'points' => [
                            'Redactar prompts efectivos para generar ejemplos y bancos de práctica',
                            'Verificar siempre la precisión académica de los contenidos generados',
                            'Guiar a los estudiantes en el uso responsable y ético de la tecnología',
                        ],
                        'video' => 'bMknfKXIFA8',
                        'link' => [
                            'title' => 'Competencias Digitales para Educadores',
                            'url' => 'https://www.unesco.org/es/digital-competencies-skills',
                        ],
                    ],
                ],
                'assessment' => [
                    'title' => 'Evaluación: Integración Digital e IA en el Aula',
                    'instructions' => 'Comprueba tu dominio sobre el uso ético y didáctico de herramientas digitales en la enseñanza.',
                    'max_attempts' => 3,
                    'passing_score' => 10,
                    'total_score' => 10,
                    'questions' => [
                        [
                            'statement' => 'Al utilizar herramientas de Inteligencia Artificial para apoyar la creación de material didáctico, ¿qué práctica debe aplicar siempre el docente?',
                            'points' => 10,
                            'choices' => [
                                ['text' => 'Revisar, validar y adaptar pedagógicamente el contenido antes de compartirlo con los estudiantes', 'is_correct' => true],
                                ['text' => 'Publicar cualquier resultado automático sin leerlo ni contrastarlo con los objetivos del curso', 'is_correct' => false],
                                ['text' => 'Reemplazar por completo la orientación humana y el pensamiento crítico', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'capacitacion-metodologias-activas-abp',
                'category_slug' => 'capacitacion',
                'name' => 'Diseño de Sesiones Activas y Aprendizaje Basado en Proyectos (ABP)',
                'description' => 'Transforma tus clases en experiencias prácticas mediante retos reales, trabajo colaborativo y aprendizaje basado en proyectos.',
                'objectives' => 'Estructurar sesiones de clase con alta participación estudiantil y proyectos integradores.',
                'video' => 'pTFZFxd4hOI',
                'issues_certificate' => true,
                'units' => [
                    [
                        'title' => 'Estructura de una Sesión de Aprendizaje Activo',
                        'summary' => 'Activación de saberes previos, demostración guiada, práctica colaborativa y cierre reflexivo.',
                        'points' => [
                            'Iniciar cada sesión con un reto o pregunta disparadora relevante',
                            'Alternar bloques breves de explicación con práctica inmediata',
                            'Cerrar la sesión sintetizando los aprendizajes clave logrados',
                        ],
                        'video' => 'pTFZFxd4hOI',
                        'link' => [
                            'title' => 'Metodologías Activas de Aprendizaje',
                            'url' => 'https://www.unesco.org/es/education',
                        ],
                    ],
                ],
                'assessment' => null,
            ],
        ];
    }
}
