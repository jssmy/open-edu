<?php

namespace App\Modules\Enrollments\Application;

use App\Modules\Enrollments\Domain\Enrollment;
use App\Modules\Enrollments\Domain\EnrollmentRepository;
use App\Shared\Audit\Auditor;

final class EnrollTeacher
{
    public function __construct(
        private readonly EnrollmentRepository $enrollments,
        private readonly Auditor $auditor,
    ) {}

    public function __invoke(
        int $teacherUserId,
        int $courseId,
        ?int $actorId = null,
    ): Enrollment {
        $enrollment = $this->enrollments->enrollTeacherInCourse($teacherUserId, $courseId);

        $this->auditor->log('enrollment.teacher_created', $actorId, "enrollment:{$enrollment->id}", [
            'user_id' => $teacherUserId,
            'course_id' => $courseId,
        ]);

        return $enrollment;
    }
}
