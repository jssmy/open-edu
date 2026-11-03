<?php

namespace App\Modules\Enrollments\Application;

use App\Modules\Courses\Domain\CourseRepository;
use App\Modules\Enrollments\Domain\Enrollment;
use App\Modules\Enrollments\Domain\EnrollmentRepository;
use App\Modules\Students\Domain\StudentRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class EnrollStudent
{
    public function __construct(
        private readonly StudentRepository $students,
        private readonly CourseRepository $courses,
        private readonly EnrollmentRepository $enrollments,
        private readonly Auditor $auditor,
    ) {}

    public function __invoke(int $studentId, int $courseId, ?int $actorId = null): Enrollment
    {
        $student = $this->students->find($studentId);
        if ($student === null || ! $student->isActive) {
            throw new DomainException('Selecciona un estudiante activo.', 'student_id');
        }

        if (! $this->courses->isPublished($courseId)) {
            throw new DomainException('Solo se puede inscribir en cursos publicados.', 'course');
        }

        if ($this->enrollments->findForStudentAndCourse($studentId, $courseId) !== null) {
            throw new DomainException('El estudiante ya está inscrito en este curso.', 'student_id');
        }

        $enrollment = $this->enrollments->create($studentId, $courseId);
        $this->auditor->log('enrollment.created', $actorId, "enrollment:{$enrollment->id}", [
            'student_id' => $studentId,
            'course_id' => $courseId,
        ]);

        return $enrollment;
    }
}
