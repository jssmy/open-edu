<?php

namespace App\Modules\Enrollments\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Application\EnrollStudent;
use App\Modules\Enrollments\Infrastructure\Http\Requests\StoreEnrollmentRequest;
use App\Modules\Students\Domain\StudentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseEnrollmentController extends Controller
{
    public function __construct(private readonly StudentRepository $students) {}

    public function store(
        StoreEnrollmentRequest $request,
        int $courseId,
        EnrollStudent $enroll,
    ): RedirectResponse {
        $course = Course::query()->findOrFail($courseId);
        $enroll($request->validated('student_id'), $course->id, $request->user()->id);

        return to_route('courses.students', $course)->with('success', 'Estudiante inscrito.');
    }

    public function students(Request $request, int $courseId): Response
    {
        $course = Course::query()->findOrFail($courseId);
        Gate::authorize('view', $course);
        $enrolled = $this->students->forCourse($courseId);
        $enrolledIds = array_column($enrolled, 'id');
        $canManageEnrollments = $request->user()->can('enrollments.manage');
        $available = $canManageEnrollments
            ? array_values(array_map(
                fn ($student): array => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'identifier' => $student->identifier,
                ],
                array_filter(
                    $this->students->all(),
                    fn ($student): bool => $student->isActive && ! in_array($student->id, $enrolledIds, true),
                ),
            ))
            : [];

        return Inertia::render('Courses/Enrollments', [
            'course' => ['id' => $course->id, 'name' => $course->name],
            'students' => $enrolled,
            'availableStudents' => $available,
            'canManageEnrollments' => $canManageEnrollments,
        ]);
    }
}
