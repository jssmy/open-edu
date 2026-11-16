<?php

namespace App\Modules\Scheduling\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Progress\Application\GetCourseProgress;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class TeacherClassController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewOwn', CourseOffering::class);

        $teacherId = $request->user()->id;

        $offerings = CourseOffering::query()
            ->where(function ($query) use ($teacherId): void {
                $query->whereHas('teachers', fn ($q) => $q->where('users.id', $teacherId))
                    ->orWhereHas('sessions', fn ($q) => $q->where('course_sessions.teacher_id', $teacherId));
            })
            ->with([
                'course:id,name,description,cover_path,cover_mime,status',
                'course.units:id,course_id',
                'course.units.lessons:id,unit_id,is_archived',
                'course.teachers:id,name',
                'teachers:id,name',
            ])
            ->select(['id', 'course_id', 'group_code', 'starts_on', 'ends_on', 'modality', 'status'])
            ->get();

        $todayDate = now()->toDateString();

        $sortedOfferings = $offerings->sort(function (CourseOffering $a, CourseOffering $b) use ($todayDate) {
            $statusA = $a->starts_on === null ? 2 : ($a->starts_on->toDateString() >= $todayDate ? 0 : 1);
            $statusB = $b->starts_on === null ? 2 : ($b->starts_on->toDateString() >= $todayDate ? 0 : 1);

            if ($statusA !== $statusB) {
                return $statusA <=> $statusB;
            }

            $dateA = $a->starts_on?->toDateString() ?? '';
            $dateB = $b->starts_on?->toDateString() ?? '';

            if ($dateA !== $dateB) {
                return $dateA <=> $dateB;
            }

            return $a->id <=> $b->id;
        })->values();

        return Inertia::render('Teacher/Classes', [
            'classes' => $sortedOfferings->map(function (CourseOffering $offering): array {
                $excerpt = Str::limit(trim(RichTextDocumentValidator::plainText($offering->course->description)), 180);

                return [
                    'id' => $offering->id,
                    'group_code' => $offering->group_code,
                    'modality' => $offering->modality,
                    'starts_on' => $offering->starts_on?->toDateString(),
                    'ends_on' => $offering->ends_on?->toDateString(),
                    'course' => [
                        'id' => $offering->course->id,
                        'name' => $offering->course->name,
                        'title' => $offering->course->name,
                        'description' => $offering->course->description,
                        'excerpt' => $excerpt !== '' ? $excerpt : 'Sin descripción.',
                        'cover_url' => $offering->course->coverUrl(),
                        'cover_image_url' => $offering->course->coverUrl(),
                        'units_count' => $offering->course->units->count(),
                        'lessons_count' => $offering->course->units->sum(fn ($u) => $u->lessons->count()),
                        'status' => $offering->course->status,
                        'teachers' => $offering->course->teachers->map(fn ($teacher): array => [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                        ])->values()->all(),
                    ],
                ];
            })->all(),
        ]);
    }

    public function viewer(
        Request $request,
        CourseOffering $offering,
        GetCourseProgress $getCourseProgress,
    ): Response {
        Gate::authorize('viewOwn', $offering);

        $course = $offering->course;
        abort_if($course === null, 404);

        $user = $request->user();

        $courseData = $getCourseProgress(
            courseId: $course->id,
            studentId: null,
            userId: $user->id,
            courseOfferingId: $offering->id,
        );

        abort_if($courseData === null, 404);

        $courseData['course_offering_id'] = $offering->id;
        $courseData['intro_video_embed_url'] = $course->introVideoEmbedUrl();
        $courseData['assessments'] = [];

        return Inertia::render('Study/CourseViewer', [
            'course' => $courseData,
            'isTeacherView' => true,
            'canMarkComplete' => true,
            'course_offering_id' => $offering->id,
            'backUrl' => '/my/classes',
        ]);
    }
}
