<?php

namespace App\Modules\Progress\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Domain\ContentType;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Progress\Application\CompleteLesson;
use App\Modules\Progress\Application\GetCourseProgress;
use App\Modules\Progress\Application\GetMyCourses;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StudyController extends Controller
{
    public function index(Request $request, GetMyCourses $getMyCourses): Response
    {
        $student = $this->activeStudent($request);
        $status = $request->query('status', 'in_progress');

        return Inertia::render('Study/MyCourses', [
            'courses' => $getMyCourses($student->id),
            'filters' => [
                'status' => is_string($status) ? $status : 'in_progress',
            ],
        ]);
    }

    public function show(Request $request, Course $course, GetCourseProgress $getCourseProgress): Response
    {
        Gate::authorize('accessCourse', $course);
        $user = $request->user();
        $student = $this->optionalActiveStudent($request);
        $courseData = $getCourseProgress($course->id, $student?->id, $user->id);

        abort_if($courseData === null, 404);

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active->value)
            ->when(
                $student !== null,
                fn ($query) => $query->where('student_id', $student->id),
                fn ($query) => $query->where('user_id', $user->id),
            )
            ->with('courseOffering')
            ->first();

        $isFinished = false;
        $endsOn = null;

        if ($enrollment?->courseOffering?->ends_on !== null) {
            $endsOn = $enrollment->courseOffering->ends_on->toDateString();
            if ($endsOn < now()->toDateString()) {
                $isFinished = true;
            }
        }

        $courseData['is_finished'] = $isFinished;
        $courseData['ends_on'] = $endsOn;
        $courseData['assessments'] = $course->assessments()
            ->where('is_published', true)
            ->orderBy('title')
            ->get(['id', 'title', 'total_score'])
            ->map(fn ($assessment): array => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'total_score' => $assessment->total_score,
            ])->all();
        $courseData['intro_video_embed_url'] = $course->introVideoEmbedUrl();

        $backUrl = $student === null && $user->hasRole('docente')
            ? '/courses'
            : '/my/classes';

        return Inertia::render('Study/CourseViewer', [
            'course' => $courseData,
            'isReadOnly' => $isFinished,
            'backUrl' => $backUrl,
        ]);
    }

    public function complete(
        Request $request,
        Lesson $lesson,
        CompleteLesson $completeLesson,
    ): RedirectResponse {
        $course = $lesson->unit->course;
        abort_if($course === null, 404);
        Gate::authorize('accessCourse', $course);
        abort_if($lesson->is_archived, 404);

        $user = $request->user();
        $student = $this->optionalActiveStudent($request);

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active->value)
            ->when(
                $student !== null,
                fn ($query) => $query->where('student_id', $student->id),
                fn ($query) => $query->where('user_id', $user->id),
            )
            ->with('courseOffering')
            ->first();

        if ($enrollment?->courseOffering?->ends_on !== null) {
            if ($enrollment->courseOffering->ends_on->toDateString() < now()->toDateString()) {
                abort(403, 'El curso ha finalizado. Modo solo lectura.');
            }
        }

        $completeLesson($student?->id, $lesson->id, $user->id);

        return to_route('study.courses.show', $course)->with('success', 'Lección completada.');
    }

    public function content(Lesson $lesson): StreamedResponse
    {
        $course = $lesson->unit->course;
        abort_if($course === null, 404);
        abort_if($lesson->is_archived, 404);

        if (! in_array($lesson->content_type, [
            ContentType::Document->value,
            ContentType::Image->value,
            ContentType::Video->value,
        ], true) || ! is_string($lesson->content) || ! str_starts_with($lesson->content, 'courses/')) {
            abort(404);
        }

        $storage = Storage::disk('local');
        abort_unless($storage->exists($lesson->content), 404);

        return $storage->response($lesson->content);
    }

    private function activeStudent(Request $request): Student
    {
        return Student::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function optionalActiveStudent(Request $request): ?Student
    {
        return Student::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();
    }
}
