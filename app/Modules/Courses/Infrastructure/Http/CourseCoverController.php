<?php

namespace App\Modules\Courses\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Domain\DefaultCourseCover;
use App\Modules\Courses\Infrastructure\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class CourseCoverController extends Controller
{
    public function show(Request $request, Course $course): Response
    {
        if ($request->user()->hasRole('estudiante')) {
            abort_unless(Gate::forUser($request->user())->allows('accessCourse', $course), 404);
        } else {
            Gate::authorize('view', $course);
        }

        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=86400'];
        $disk = Storage::disk('local');

        if ($course->cover_path !== null && $disk->exists($course->cover_path)) {
            return $disk->response($course->cover_path, basename($course->cover_path), [
                ...$headers,
                'Content-Type' => $course->cover_mime,
                'Content-Disposition' => 'inline',
            ]);
        }

        return response(DefaultCourseCover::svg($course->id, $course->name), 200, [
            ...$headers,
            'Content-Type' => 'image/svg+xml',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
        ]);
    }
}
