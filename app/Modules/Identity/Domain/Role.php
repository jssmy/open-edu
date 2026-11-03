<?php

namespace App\Modules\Identity\Domain;

enum Role: string
{
    case Administrador = 'administrador';
    case GestorDeCursos = 'gestor_de_cursos';
    case Docente = 'docente';
    case Estudiante = 'estudiante';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::GestorDeCursos => 'Gestor de cursos',
            self::Docente => 'Docente',
            self::Estudiante => 'Estudiante',
        };
    }
}
