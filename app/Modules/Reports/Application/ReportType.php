<?php

namespace App\Modules\Reports\Application;

enum ReportType: string
{
    case Enrollments = 'enrollments';
    case Progress = 'progress';
    case Results = 'results';

    public function filename(): string
    {
        return $this->value.'.csv';
    }

    /** @return array<string, string> */
    public function headings(): array
    {
        return match ($this) {
            self::Enrollments => [
                'course_id' => 'ID del curso',
                'course_name' => 'Curso',
                'student_id' => 'ID del estudiante',
                'student_name' => 'Estudiante',
                'student_email' => 'Correo',
                'status' => 'Estado de inscripción',
                'enrolled_at' => 'Fecha de inscripción',
            ],
            self::Progress => [
                'course_id' => 'ID del curso',
                'course_name' => 'Curso',
                'student_id' => 'ID del estudiante',
                'student_name' => 'Estudiante',
                'enrollment_status' => 'Estado de inscripción',
                'completed_lessons' => 'Lecciones completadas',
                'total_lessons' => 'Lecciones totales',
                'percentage' => 'Avance (%)',
            ],
            self::Results => [
                'course_id' => 'ID del curso',
                'course_name' => 'Curso',
                'assessment_id' => 'ID de la evaluación',
                'assessment_title' => 'Evaluación',
                'student_id' => 'ID del estudiante',
                'student_name' => 'Estudiante',
                'attempt_number' => 'Intento',
                'score' => 'Puntaje',
                'total_score' => 'Puntaje total',
                'passed' => 'Aprobado',
                'submitted_at' => 'Fecha de envío',
            ],
        };
    }
}
