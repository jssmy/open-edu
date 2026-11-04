<?php

namespace App\Modules\Scheduling\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
                'course.units.lessons',
                'teachers',
                'sessions.classroom',
            ])
            ->latest('starts_on')
            ->get();

        return Inertia::render('Teacher/Classes', [
            'classes' => $offerings->map(fn (CourseOffering $offering): array => [
                'id' => $offering->id,
                'group_code' => $offering->group_code,
                'modality' => $offering->modality,
                'starts_on' => $offering->starts_on?->toDateString(),
                'ends_on' => $offering->ends_on?->toDateString(),
                'course' => [
                    'id' => $offering->course->id,
                    'title' => $offering->course->name,
                    'description' => $offering->course->description,
                    'cover_image_url' => $offering->course->cover_image_path
                        ? route('courses.cover', $offering->course)
                        : null,
                    'units_count' => $offering->course->units->count(),
                    'lessons_count' => $offering->course->units->sum(fn ($u) => $u->lessons->count()),
                ],
            ])->all(),
        ]);
    }

    public function viewer(Request $request, CourseOffering $offering): Response
    {
        Gate::authorize('viewOwn', $offering);

        $course = $offering->course;
        abort_if($course === null, 404);

        $course->load([
            'units' => fn ($q) => $q->orderBy('position')->with([
                'lessons' => fn ($lq) => $lq->orderBy('position'),
            ]),
        ]);

        $courseData = [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'intro_video_embed_url' => $course->introVideoEmbedUrl(),
            'units' => $course->units->map(fn ($unit): array => [
                'id' => $unit->id,
                'title' => $unit->title,
                'position' => $unit->position,
                'lessons' => $unit->lessons->map(fn ($lesson): array => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'position' => $lesson->position,
                    'content_type' => $lesson->content_type,
                    'content' => $lesson->content,
                    'is_completed' => false,
                ])->all(),
            ])->all(),
            'progress' => [
                'completed_lessons' => 0,
                'total_lessons' => $course->units->sum(fn ($u) => $u->lessons->count()),
                'percentage' => 0,
            ],
            'assessments' => [],
        ];

        return Inertia::render('Study/CourseViewer', [
            'course' => $courseData,
            'isTeacherView' => true,
            'backUrl' => '/my/classes',
        ]);
    }
}
