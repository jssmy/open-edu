<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogDemoSeeder extends Seeder
{
    private const TEACHER_COUNT = 20;

    private const STUDENT_COUNT = 50;

    private const FIRST_NAMES = [
        'Lucía', 'Mateo', 'Valentina', 'Santiago', 'Camila', 'Sebastián', 'Sofía', 'Diego', 'Isabella', 'Nicolás',
        'Mariana', 'Andrés', 'Daniela', 'Javier', 'Paula', 'Gabriel', 'Natalia', 'Rodrigo', 'Fernanda', 'Emilio',
        'Carolina', 'Alejandro', 'Renata', 'Tomás', 'Julieta',
    ];

    private const LAST_NAMES = [
        'García', 'Rodríguez', 'Martínez', 'López', 'Hernández', 'Pérez', 'Gómez', 'Sánchez', 'Ramírez', 'Torres',
        'Flores', 'Rivera', 'Castillo', 'Vargas', 'Mendoza', 'Rojas', 'Salazar', 'Herrera', 'Medina', 'Aguilar',
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('CatalogDemoSeeder no puede ejecutarse en producción.');

            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);

        $teachers = $this->seedTeachers();
        $students = $this->seedStudents();
        $courses = $this->seedCourses($teachers);
        $this->seedEnrollments($students, $courses);
    }

    /** @return list<User> */
    private function seedTeachers(): array
    {
        $teachers = [];
        for ($i = 1; $i <= self::TEACHER_COUNT; $i++) {
            $teachers[] = $this->findOrCreateUser(
                'Prof. '.$this->personName($i - 1, 3),
                sprintf('docente%02d@openedu.test', $i),
                Role::Docente,
            );
        }

        return $teachers;
    }

    /** @return list<Student> */
    private function seedStudents(): array
    {
        $students = [];
        for ($i = 1; $i <= self::STUDENT_COUNT; $i++) {
            $user = $this->findOrCreateUser(
                $this->personName($i - 1, 7),
                sprintf('estudiante%02d@openedu.test', $i),
                Role::Estudiante,
            );
            $students[] = Student::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'identifier' => sprintf('EST-%03d', $i),
                    'phone' => sprintf('+51 9%08d', 10000000 + $i * 137),
                    'is_active' => true,
                ],
            );
        }

        return $students;
    }

    /**
     * @param  list<User>  $teachers
     * @return list<Course>
     */
    private function seedCourses(array $teachers): array
    {
        $courses = [];

        foreach ($this->courseDefinitions() as $index => $definition) {
            $course = Course::query()->where('name', $definition['name'])->first()
                ?? Course::factory()->published()->create([
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'objectives' => $definition['objectives'],
                    'intro_video_provider' => 'youtube',
                    'intro_video_id' => $definition['video'],
                ]);

            $course->teachers()->syncWithoutDetaching([
                $teachers[($index * 2) % count($teachers)]->id,
                $teachers[($index * 2 + 1) % count($teachers)]->id,
            ]);

            foreach ($definition['units'] as $unitIndex => $unitDefinition) {
                $unit = $course->units()->firstOrCreate(
                    ['title' => $unitDefinition['title']],
                    ['position' => $unitIndex + 1],
                );

                foreach ($this->lessonDefinitions($definition['slug'], $unitIndex, $unitDefinition) as $lessonIndex => $lesson) {
                    $unit->lessons()->firstOrCreate(
                        ['title' => $lesson['title']],
                        [
                            'position' => $lessonIndex + 1,
                            'content_type' => $lesson['type'],
                            'content' => $lesson['content'],
                            'is_archived' => false,
                        ],
                    );
                }
            }

            $courses[] = $course;
        }

        return $courses;
    }

    /**
     * @param  list<Student>  $students
     * @param  list<Course>  $courses
     */
    private function seedEnrollments(array $students, array $courses): void
    {
        foreach ($students as $studentIndex => $student) {
            $enrolledCount = 3 + ($studentIndex % 3);

            for ($offset = 0; $offset < $enrolledCount; $offset++) {
                $course = $courses[($studentIndex + $offset * 3) % count($courses)];

                if (! Enrollment::query()->where('student_id', $student->id)->where('course_id', $course->id)->exists()) {
                    Enrollment::factory()->for($student)->for($course)->create([
                        'status' => EnrollmentStatus::Active,
                        'enrolled_at' => now()->subDays(($studentIndex + $offset) % 30),
                    ]);
                }

                $lessonIds = DB::table('lessons')
                    ->join('units', 'units.id', '=', 'lessons.unit_id')
                    ->where('units.course_id', $course->id)
                    ->orderBy('units.position')
                    ->orderBy('lessons.position')
                    ->pluck('lessons.id');

                foreach ($lessonIds->take(($studentIndex * 3 + $offset * 5) % ($lessonIds->count() + 1)) as $lessonId) {
                    DB::table('lesson_progress')->insertOrIgnore([
                        'student_id' => $student->id,
                        'lesson_id' => $lessonId,
                        'completed_at' => now()->subDays($offset),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    private function personName(int $index, int $step): string
    {
        $first = self::FIRST_NAMES[$index % count(self::FIRST_NAMES)];
        $last = self::LAST_NAMES[($index * $step + intdiv($index, count(self::FIRST_NAMES))) % count(self::LAST_NAMES)];
        $second = self::LAST_NAMES[($index * $step + 5) % count(self::LAST_NAMES)];

        return "{$first} {$last} {$second}";
    }

    private function findOrCreateUser(string $name, string $email, Role $role): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => DemoDataSeeder::DEMO_PASSWORD,
                'must_change_password' => false,
                'is_active' => true,
            ],
        );
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * @param  array{title: string, summary: string, points: list<string>, video: string, link: array{title: string, url: string}}  $unit
     * @return list<array{title: string, type: string, content: array<string, mixed>|string}>
     */
    private function lessonDefinitions(string $slug, int $unitIndex, array $unit): array
    {
        $title = $unit['title'];
        $document = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $title]]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $unit['summary']]]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'En esta unidad aprenderás a:']]],
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
                        'content' => [['type' => 'text', 'text' => 'Practica cada concepto con un ejemplo propio antes de avanzar.', 'marks' => [['type' => 'italic']]]],
                    ]],
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Lectura complementaria: '],
                        [
                            'type' => 'text',
                            'text' => $unit['link']['title'],
                            'marks' => [['type' => 'link', 'attrs' => ['href' => $unit['link']['url'], 'target' => '_blank', 'rel' => 'noopener noreferrer nofollow']]],
                        ],
                    ],
                ],
            ],
        ];

        return [
            ['title' => "Lectura: {$title}", 'type' => 'text', 'content' => RichTextDocumentValidator::isValid($document) ? $document : RichTextDocumentValidator::fromPlainText($unit['summary'])],
            ['title' => "Video: {$title}", 'type' => 'video', 'content' => 'https://www.youtube.com/watch?v='.$unit['video']],
            ['title' => "Infografía: {$title}", 'type' => 'image', 'content' => sprintf('https://picsum.photos/seed/%s-%d/1200/675', $slug, $unitIndex + 1)],
            ['title' => "Recurso externo: {$unit['link']['title']}", 'type' => 'link', 'content' => $unit['link']['url']],
        ];
    }

    /**
     * @return list<array{slug: string, name: string, description: string, objectives: string, video: string, units: list<array{title: string, summary: string, points: list<string>, video: string, link: array{title: string, url: string}}>}>
     */
    private function courseDefinitions(): array
    {
        return [
            $this->course('python', 'Python desde Cero', 'Aprende a programar con Python: sintaxis, estructuras de datos, funciones y manejo de archivos.', 'Escribir programas en Python para resolver problemas cotidianos.', 'rfscVS0vtbw', [
                ['Fundamentos de Python', 'Variables, tipos de datos y operadores.', ['Declarar variables', 'Usar condicionales', 'Escribir bucles'], 'kqtD5dpn9C8', 'Tutorial oficial de Python', 'https://docs.python.org/es/3/tutorial/'],
                ['Estructuras de datos', 'Listas, tuplas, diccionarios y conjuntos.', ['Manipular listas', 'Usar diccionarios', 'Elegir la estructura adecuada'], '_uQrJ0TkZlc', 'Estructuras de datos en Python', 'https://docs.python.org/es/3/tutorial/datastructures.html'],
                ['Funciones y módulos', 'Reutiliza código con funciones y módulos.', ['Definir funciones', 'Importar módulos', 'Manejar excepciones'], 'rfscVS0vtbw', 'Módulos en Python', 'https://docs.python.org/es/3/tutorial/modules.html'],
            ]),
            $this->course('javascript', 'JavaScript Moderno', 'Domina JavaScript ES6+, el DOM y la programación asíncrona.', 'Crear interacciones dinámicas en la web con JavaScript.', 'PkZNo7MFNFg', [
                ['Sintaxis y tipos', 'Conoce el lenguaje de la web.', ['Usar let y const', 'Trabajar con funciones flecha', 'Manipular arreglos'], 'hdI2bqOjy3c', 'Guía de JavaScript (MDN)', 'https://developer.mozilla.org/es/docs/Web/JavaScript/Guide'],
                ['El DOM y los eventos', 'Modifica páginas web desde código.', ['Seleccionar elementos', 'Escuchar eventos', 'Actualizar el DOM'], 'PkZNo7MFNFg', 'Introducción al DOM', 'https://developer.mozilla.org/es/docs/Web/API/Document_Object_Model/Introduction'],
                ['Asincronía', 'Promesas, async/await y consumo de APIs.', ['Entender el event loop', 'Usar fetch', 'Manejar errores asíncronos'], 'hdI2bqOjy3c', 'Programación asíncrona', 'https://developer.mozilla.org/es/docs/Learn/JavaScript/Asynchronous'],
            ]),
            $this->course('html-css', 'Diseño Web con HTML y CSS', 'Construye sitios web responsivos con HTML5 y CSS3.', 'Maquetar páginas accesibles y adaptables a cualquier dispositivo.', 'qz0aGYrrlhU', [
                ['HTML semántico', 'Estructura contenido con etiquetas significativas.', ['Usar encabezados', 'Crear formularios', 'Mejorar la accesibilidad'], 'UB1O30fR-EE', 'Referencia HTML', 'https://developer.mozilla.org/es/docs/Web/HTML'],
                ['Estilos con CSS', 'Colores, tipografía y modelo de caja.', ['Aplicar selectores', 'Entender el modelo de caja', 'Definir tipografías'], 'qz0aGYrrlhU', 'Referencia CSS', 'https://developer.mozilla.org/es/docs/Web/CSS'],
                ['Diseño responsivo', 'Flexbox, Grid y media queries.', ['Crear layouts con Flexbox', 'Usar CSS Grid', 'Adaptar a móviles'], 'UB1O30fR-EE', 'Diseño responsivo', 'https://developer.mozilla.org/es/docs/Learn/CSS/CSS_layout/Responsive_Design'],
            ]),
            $this->course('git', 'Control de Versiones con Git y GitHub', 'Trabaja en equipo con Git, ramas y pull requests.', 'Gestionar proyectos colaborativos con un flujo de trabajo profesional.', 'SWYqp7iY_Tc', [
                ['Primeros pasos con Git', 'Repositorios, commits e historial.', ['Inicializar un repositorio', 'Crear commits', 'Revisar el historial'], 'SWYqp7iY_Tc', 'Libro Pro Git', 'https://git-scm.com/book/es/v2'],
                ['Ramas y fusiones', 'Trabaja en paralelo sin conflictos.', ['Crear ramas', 'Fusionar cambios', 'Resolver conflictos'], 'SWYqp7iY_Tc', 'Ramas en Git', 'https://git-scm.com/book/es/v2/Ramificaciones-en-Git-Procedimientos-Básicos-de-Ramificación-y-Fusión'],
                ['Colaboración en GitHub', 'Forks, pull requests y revisiones.', ['Clonar repositorios', 'Abrir pull requests', 'Revisar código'], 'SWYqp7iY_Tc', 'Documentación de GitHub', 'https://docs.github.com/es'],
            ]),
            $this->course('sql', 'Bases de Datos con SQL', 'Diseña consultas y modela bases de datos relacionales.', 'Consultar y modelar información con SQL.', 'HXV3zeQKqGY', [
                ['Consultas básicas', 'SELECT, filtros y ordenamiento.', ['Usar SELECT y WHERE', 'Ordenar resultados', 'Agrupar datos'], 'HXV3zeQKqGY', 'Tutorial de SQL', 'https://www.postgresql.org/docs/current/tutorial-sql.html'],
                ['Relaciones y JOIN', 'Combina tablas de forma eficiente.', ['Entender claves foráneas', 'Usar INNER y LEFT JOIN', 'Evitar duplicados'], 'HXV3zeQKqGY', 'Consultas con JOIN', 'https://www.postgresql.org/docs/current/tutorial-join.html'],
                ['Diseño de esquemas', 'Normalización e índices.', ['Normalizar tablas', 'Crear índices', 'Definir restricciones'], 'HXV3zeQKqGY', 'Índices en PostgreSQL', 'https://www.postgresql.org/docs/current/indexes.html'],
            ]),
            $this->course('data-science', 'Análisis de Datos con Python', 'Explora, limpia y visualiza datos con Pandas y NumPy.', 'Obtener conclusiones a partir de conjuntos de datos reales.', 'vmEHCJofslg', [
                ['NumPy esencial', 'Arreglos y operaciones vectorizadas.', ['Crear arreglos', 'Aplicar operaciones vectorizadas', 'Indexar y segmentar'], 'GPVsHOlRBBI', 'Guía de NumPy', 'https://numpy.org/doc/stable/user/absolute_beginners.html'],
                ['Pandas para análisis', 'DataFrames, limpieza y agregaciones.', ['Cargar datos', 'Limpiar valores faltantes', 'Agrupar y resumir'], 'vmEHCJofslg', '10 minutos de Pandas', 'https://pandas.pydata.org/docs/user_guide/10min.html'],
                ['Visualización', 'Gráficos que comunican resultados.', ['Crear gráficos de líneas y barras', 'Elegir el gráfico adecuado', 'Contar una historia con datos'], 'vmEHCJofslg', 'Galería de Matplotlib', 'https://matplotlib.org/stable/gallery/index.html'],
            ]),
            $this->course('machine-learning', 'Introducción al Aprendizaje Automático', 'Conceptos de machine learning y primeros modelos con scikit-learn.', 'Entrenar y evaluar modelos predictivos sencillos.', 'i_LwzRVP7bg', [
                ['Qué es el aprendizaje automático', 'Tipos de aprendizaje y casos de uso.', ['Distinguir aprendizaje supervisado y no supervisado', 'Identificar casos de uso', 'Preparar datos'], 'i_LwzRVP7bg', 'Introducción a scikit-learn', 'https://scikit-learn.org/stable/getting_started.html'],
                ['Modelos de regresión y clasificación', 'Entrena tu primer modelo.', ['Dividir datos de entrenamiento y prueba', 'Entrenar un modelo', 'Interpretar resultados'], 'i_LwzRVP7bg', 'Aprendizaje supervisado', 'https://scikit-learn.org/stable/supervised_learning.html'],
                ['Evaluación de modelos', 'Métricas y sobreajuste.', ['Calcular precisión y recall', 'Detectar sobreajuste', 'Usar validación cruzada'], 'i_LwzRVP7bg', 'Evaluación de modelos', 'https://scikit-learn.org/stable/modules/model_evaluation.html'],
            ]),
            $this->course('react', 'Desarrollo Web con React', 'Crea interfaces de usuario con componentes, estado y hooks.', 'Construir aplicaciones web interactivas con React.', 'bMknfKXIFA8', [
                ['Componentes y JSX', 'Piensa tu interfaz como componentes.', ['Crear componentes', 'Pasar props', 'Renderizar listas'], 'w7ejDZ8SWv8', 'Documentación de React', 'https://es.react.dev/learn'],
                ['Estado y hooks', 'useState, useEffect y hooks personalizados.', ['Manejar estado local', 'Sincronizar efectos', 'Crear hooks propios'], 'bMknfKXIFA8', 'Estado en React', 'https://es.react.dev/learn/state-a-components-memory'],
                ['Aplicaciones completas', 'Rutas, formularios y consumo de APIs.', ['Navegar entre páginas', 'Validar formularios', 'Consumir una API'], 'w7ejDZ8SWv8', 'Pensar en React', 'https://es.react.dev/learn/thinking-in-react'],
            ]),
            $this->course('docker', 'Contenedores con Docker', 'Empaqueta y despliega aplicaciones con Docker y Compose.', 'Ejecutar aplicaciones de forma reproducible en contenedores.', 'pTFZFxd4hOI', [
                ['Conceptos de contenedores', 'Imágenes, contenedores y registros.', ['Entender imágenes', 'Ejecutar contenedores', 'Usar volúmenes'], 'pTFZFxd4hOI', 'Primeros pasos con Docker', 'https://docs.docker.com/get-started/'],
                ['Dockerfile', 'Construye tus propias imágenes.', ['Escribir un Dockerfile', 'Optimizar capas', 'Publicar imágenes'], 'pTFZFxd4hOI', 'Referencia de Dockerfile', 'https://docs.docker.com/reference/dockerfile/'],
                ['Docker Compose', 'Orquesta varios servicios.', ['Definir servicios', 'Conectar redes', 'Gestionar variables de entorno'], 'pTFZFxd4hOI', 'Documentación de Compose', 'https://docs.docker.com/compose/'],
            ]),
            $this->course('node', 'APIs con Node.js', 'Desarrolla servicios web con Node.js y Express.', 'Diseñar e implementar APIs REST seguras.', 'Oe421EPjeBE', [
                ['Fundamentos de Node.js', 'Módulos, npm y el sistema de archivos.', ['Usar módulos', 'Administrar paquetes con npm', 'Leer y escribir archivos'], 'Oe421EPjeBE', 'Documentación de Node.js', 'https://nodejs.org/es/learn'],
                ['Servidores con Express', 'Rutas, middleware y controladores.', ['Definir rutas', 'Crear middleware', 'Responder en JSON'], 'Oe421EPjeBE', 'Guía de Express', 'https://expressjs.com/es/guide/routing.html'],
                ['Seguridad y despliegue', 'Autenticación, validación y publicación.', ['Validar entradas', 'Proteger rutas', 'Desplegar la API'], 'Oe421EPjeBE', 'Buenas prácticas de seguridad', 'https://expressjs.com/es/advanced/best-practice-security.html'],
            ]),
        ];
    }

    /**
     * @param  list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string, 5: string}>  $units
     * @return array{slug: string, name: string, description: string, objectives: string, video: string, units: list<array{title: string, summary: string, points: list<string>, video: string, link: array{title: string, url: string}}>}
     */
    private function course(string $slug, string $name, string $description, string $objectives, string $video, array $units): array
    {
        return [
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'objectives' => $objectives,
            'video' => $video,
            'units' => array_map(fn (array $unit): array => [
                'title' => $unit[0],
                'summary' => $unit[1],
                'points' => $unit[2],
                'video' => $unit[3],
                'link' => ['title' => $unit[4], 'url' => $unit[5]],
            ], $units),
        ];
    }
}
