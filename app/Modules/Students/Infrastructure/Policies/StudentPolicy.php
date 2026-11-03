<?php

namespace App\Modules\Students\Infrastructure\Policies;

use App\Models\User;
use App\Modules\Students\Domain\StudentRepository;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Auth\Access\Response;

final class StudentPolicy
{
    public function __construct(private readonly StudentRepository $students) {}

    public function viewAny(User $user): bool
    {
        return $user->can('students.view');
    }

    public function view(User $user, Student $student): Response
    {
        if ($user->can('students.manage')) {
            return Response::allow();
        }

        if ($user->hasRole('docente') && $this->students->canTeacherView($student->id, $user->id)) {
            return Response::allow();
        }

        if ($user->hasRole('estudiante') && $student->user_id === $user->id) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }
}
