<?php

namespace App\Modules\Enrollments\Application;

use App\Modules\Courses\Domain\CourseRepository;
use App\Modules\Enrollments\Domain\Enrollment;
use App\Modules\Enrollments\Domain\EnrollmentRepository;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Scheduling\Domain\ConflictDetector;
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
        private readonly ConflictDetector $detector = new ConflictDetector,
    ) {}

    public function __invoke(
        int $studentId,
        ?int $courseId = null,
        ?int $actorId = null,
        ?int $courseOfferingId = null,
    ): Enrollment {
        $student = $this->students->find($studentId);
        if ($student === null || ! $student->isActive) {
            throw new DomainException('Selecciona un estudiante activo.', 'student_id');
        }

        if ($courseOfferingId !== null) {
            $enrollment = $this->enrollments->enrollInOffering($studentId, $courseOfferingId, $this->detector);
            $this->auditor->log('enrollment.created', $actorId, "enrollment:{$enrollment->id}", [
                'student_id' => $studentId,
                'course_id' => $enrollment->courseId,
                'course_offering_id' => $enrollment->courseOfferingId,
            ]);

            return $enrollment;
        }

        if ($courseId === null || ! $this->courses->isPublished($courseId)) {
            throw new DomainException('Solo se puede inscribir en cursos publicados.', 'course');
        }

        $existing = $this->enrollments->findForStudentAndCourse($studentId, $courseId);
        if ($existing !== null && $existing->status !== EnrollmentStatus::Cancelled) {
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
