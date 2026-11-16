<?php

namespace App\Modules\Enrollments\Infrastructure;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\Enrollment as EnrollmentRecord;
use App\Modules\Enrollments\Domain\EnrollmentRepository;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Scheduling\Domain\ConflictDetector;
use App\Modules\Scheduling\Domain\CourseSession as DomainSession;
use App\Modules\Scheduling\Domain\SessionStatus;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Scheduling\Infrastructure\Models\CourseSession;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

final class EloquentEnrollmentRepository implements EnrollmentRepository
{
    public function find(int $enrollmentId): ?EnrollmentRecord
    {
        $enrollment = Enrollment::query()->find($enrollmentId);

        return $enrollment ? $this->toRecord($enrollment) : null;
    }

    public function findForStudentAndCourse(int $studentId, int $courseId): ?EnrollmentRecord
    {
        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();

        return $enrollment ? $this->toRecord($enrollment) : null;
    }

    public function create(int $studentId, int $courseId, ?int $courseOfferingId = null): EnrollmentRecord
    {
        $course = Course::query()->with('category:id,role_name')->find($courseId);
        if ($course !== null && $course->effectiveRoleName() !== 'estudiante') {
            throw new DomainException('Este curso no pertenece a una categoría de estudiantes.', 'course');
        }

        $existing = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();

        if ($existing !== null && $existing->status === EnrollmentStatus::Cancelled) {
            $existing->update([
                'course_offering_id' => $courseOfferingId,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => now(),
            ]);

            return $this->toRecord($existing->fresh());
        }

        return $this->toRecord(Enrollment::query()->create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'course_offering_id' => $courseOfferingId,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]));
    }

    public function enrollInOffering(int $studentId, int $courseOfferingId, ConflictDetector $detector): EnrollmentRecord
    {
        return DB::transaction(function () use ($studentId, $courseOfferingId, $detector): EnrollmentRecord {
            $offering = CourseOffering::query()
                ->with(['course.category:id,role_name', 'sessions'])
                ->lockForUpdate()
                ->find($courseOfferingId);

            if ($offering === null) {
                throw new DomainException('La programación académica seleccionada no existe.', 'course_offering_id');
            }

            if ($offering->course === null || $offering->course->status !== 'published') {
                throw new DomainException('Solo se puede matricular en cursos publicados.', 'course');
            }

            if ($offering->course->effectiveRoleName() !== 'estudiante') {
                throw new DomainException('Solo los cursos de categorías de estudiantes admiten matrícula académica.', 'course_offering_id');
            }

            if (! $offering->isEnrollable()) {
                throw new DomainException(
                    'La programación académica seleccionada no está disponible para matrícula.',
                    'course_offering_id',
                );
            }

            $existing = Enrollment::query()
                ->where('student_id', $studentId)
                ->where('course_id', $offering->course_id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && $existing->status !== EnrollmentStatus::Cancelled) {
                throw new DomainException('El estudiante ya cuenta con una matrícula activa en este curso.', 'student_id');
            }

            $activeCount = Enrollment::query()
                ->where('course_offering_id', $offering->id)
                ->where('status', EnrollmentStatus::Active)
                ->count();

            if ($activeCount >= $offering->max_students) {
                throw new DomainException(
                    'La programación académica seleccionada no tiene cupos disponibles (0 vacantes).',
                    'course_offering_id',
                );
            }

            $proposedSessions = $offering->sessions
                ->filter(fn (CourseSession $s): bool => $s->effectiveStatus() !== SessionStatus::Cancelled->value)
                ->map(fn (CourseSession $s): DomainSession => $this->toDomainSession($s, $offering->course->name))
                ->values()
                ->all();

            $existingSessions = CourseSession::query()
                ->where('status', '!=', SessionStatus::Cancelled->value)
                ->whereHas('offering', function ($query) use ($studentId, $offering): void {
                    $query->where('course_id', '!=', $offering->course_id)
                        ->whereHas('enrollments', function ($e) use ($studentId): void {
                            $e->where('student_id', $studentId)->where('status', EnrollmentStatus::Active->value);
                        });
                })
                ->with('offering.course:id,name')
                ->get()
                ->map(fn (CourseSession $s): DomainSession => $this->toDomainSession($s, $s->offering?->course?->name))
                ->all();

            $conflict = $detector->findConflict($proposedSessions, $existingSessions);
            if ($conflict !== null) {
                /** @var DomainSession $occupied */
                $occupied = $conflict['existing'];
                $courseLabel = $occupied->courseName ?? 'otro curso';

                throw new DomainException(
                    "Cruce de horario el {$occupied->sessionDate} ({$occupied->startsAt}–{$occupied->endsAt}) con el curso «{$courseLabel}» ({$occupied->title}).",
                    'course_offering_id',
                );
            }

            if ($existing !== null) {
                $existing->update([
                    'course_offering_id' => $offering->id,
                    'status' => EnrollmentStatus::Active,
                    'enrolled_at' => now(),
                ]);

                return $this->toRecord($existing->fresh());
            }

            $created = Enrollment::query()->create([
                'student_id' => $studentId,
                'course_id' => $offering->course_id,
                'course_offering_id' => $offering->id,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => now(),
            ]);

            return $this->toRecord($created);
        });
    }

    public function enrollTeacherInCourse(int $teacherUserId, int $courseId): EnrollmentRecord
    {
        return DB::transaction(function () use ($teacherUserId, $courseId): EnrollmentRecord {
            $user = User::query()->find($teacherUserId);
            if ($user === null || ! $user->is_active) {
                throw new DomainException('El usuario no se encuentra activo.', 'course_id');
            }

            $course = Course::query()->with('category:id,role_name')->lockForUpdate()->find($courseId);
            if ($course === null || $course->status !== 'published') {
                throw new DomainException('Solo puedes inscribirte en cursos publicados.', 'course_id');
            }

            $requiredRole = $course->effectiveRoleName();
            if ($requiredRole === 'estudiante') {
                throw new DomainException('Los cursos de categorías de estudiantes requieren matrícula por grupo y horario.', 'course_id');
            }

            if (! $user->hasRole($requiredRole) && ! $user->can('courses.manage')) {
                throw new DomainException('Este curso no está habilitado para tu rol.', 'course_id');
            }

            $existing = Enrollment::query()
                ->where('user_id', $teacherUserId)
                ->where('course_id', $courseId)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && $existing->status !== EnrollmentStatus::Cancelled) {
                return $this->toRecord($existing);
            }

            if ($existing !== null) {
                $existing->update([
                    'course_offering_id' => null,
                    'status' => EnrollmentStatus::Active,
                    'enrolled_at' => now(),
                ]);

                return $this->toRecord($existing->fresh());
            }

            $created = Enrollment::query()->create([
                'student_id' => null,
                'user_id' => $teacherUserId,
                'course_id' => $courseId,
                'course_offering_id' => null,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => now(),
            ]);

            return $this->toRecord($created);
        });
    }

    public function setStatus(int $enrollmentId, EnrollmentStatus $status): void
    {
        Enrollment::query()->whereKey($enrollmentId)->update(['status' => $status]);
    }

    private function toRecord(Enrollment $enrollment): EnrollmentRecord
    {
        return new EnrollmentRecord(
            $enrollment->id,
            $enrollment->student_id,
            $enrollment->course_id,
            $enrollment->status,
            $enrollment->enrolled_at->toDateTimeImmutable(),
            $enrollment->course_offering_id,
            $enrollment->user_id,
        );
    }

    private function toDomainSession(CourseSession $session, ?string $courseName = null): DomainSession
    {
        return DomainSession::fromPrimitives(
            title: $session->title,
            sessionDate: $session->session_date->toDateString(),
            startsAt: $session->starts_at,
            endsAt: $session->ends_at,
            teacherId: $session->teacher_id,
            classroomId: $session->classroom_id,
            meetingUrl: $session->meeting_url,
            status: $session->effectiveStatus(),
            id: $session->id,
            courseName: $courseName,
        );
    }
}
