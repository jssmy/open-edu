<?php

namespace App\Modules\Identity\Domain;

final class PermissionDictionary
{
    /** @var array<string, array{label: string, module: string, description: string}> */
    private const CATALOG = [
        'users.manage' => [
            'label' => 'Gestión de usuarios',
            'module' => 'Usuarios',
            'description' => 'Crear, editar y suspender cuentas de usuario en el sistema.',
        ],
        'roles.manage' => [
            'label' => 'Gestión de roles y permisos',
            'module' => 'Seguridad',
            'description' => 'Crear roles y configurar permisos de acceso en la plataforma.',
        ],
        'courses.manage' => [
            'label' => 'Gestión de cursos',
            'module' => 'Cursos',
            'description' => 'Crear, editar estructura y administrar cursos institucionales.',
        ],
        'courses.view' => [
            'label' => 'Consulta de cursos',
            'module' => 'Cursos',
            'description' => 'Visualizar el catálogo de cursos y contenidos publicados.',
        ],
        'content.manage' => [
            'label' => 'Gestión de contenidos',
            'module' => 'Contenidos',
            'description' => 'Crear y editar lecciones, temas y diapositivas de cursos.',
        ],
        'students.manage' => [
            'label' => 'Gestión de estudiantes',
            'module' => 'Estudiantes',
            'description' => 'Administrar expedientes y datos de estudiantes.',
        ],
        'students.view' => [
            'label' => 'Consulta de estudiantes',
            'module' => 'Estudiantes',
            'description' => 'Ver información y avance de estudiantes.',
        ],
        'enrollments.manage' => [
            'label' => 'Gestión de matrículas',
            'module' => 'Matrículas',
            'description' => 'Matricular y desmatricular estudiantes en clases y grupos.',
        ],
        'assessments.manage' => [
            'label' => 'Gestión de evaluaciones',
            'module' => 'Evaluaciones',
            'description' => 'Crear, editar y calificar pruebas y evaluaciones académicas.',
        ],
        'reports.view' => [
            'label' => 'Consulta de reportes',
            'module' => 'Reportes',
            'description' => 'Acceder a reportes y analíticas institucionales.',
        ],
        'scheduling.manage' => [
            'label' => 'Gestión de programación académica',
            'module' => 'Programación',
            'description' => 'Programar periodos, clases, grupos, aulas y horarios.',
        ],
        'scheduling.view-own' => [
            'label' => 'Visualización de clases asignadas',
            'module' => 'Programación',
            'description' => 'Ver las clases y horarios propios asignados a un docente.',
        ],
        'study' => [
            'label' => 'Aula de estudio interactiva',
            'module' => 'Aprendizaje',
            'description' => 'Acceso al reproductor de lecciones y estudio para estudiantes.',
        ],
    ];

    public static function get(string $name): PermissionRecord
    {
        if (isset(self::CATALOG[$name])) {
            $info = self::CATALOG[$name];

            return new PermissionRecord($name, $info['label'], $info['module'], $info['description']);
        }

        $clean = str_replace(['.', '-', '_'], ' ', $name);
        $label = ucfirst($clean);

        return new PermissionRecord($name, $label, 'General', null);
    }
}
