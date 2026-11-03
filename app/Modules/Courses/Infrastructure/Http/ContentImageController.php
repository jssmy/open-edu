<?php

namespace App\Modules\Courses\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Domain\FileStorage;
use App\Modules\Courses\Infrastructure\Http\Requests\SaveContentImageRequest;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\CourseMediaImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class ContentImageController extends Controller
{
    public function store(
        SaveContentImageRequest $request,
        Course $course,
        FileStorage $files,
    ): JsonResponse {
        $file = $request->file('file');
        $mimeType = $file->getMimeType();
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($extension === null) {
            throw ValidationException::withMessages([
                'file' => 'El archivo debe ser una imagen JPEG, PNG o WebP válida.',
            ]);
        }

        $id = (string) Str::uuid();
        $path = $files->store(
            'courses/'.$course->id.'/content-images',
            $file->getPathname(),
            $id.'.'.$extension,
        );

        try {
            $image = DB::transaction(fn (): CourseMediaImage => CourseMediaImage::query()->create([
                'id' => $id,
                'course_id' => $course->id,
                'storage_path' => $path,
                'mime_type' => $mimeType,
                'size_bytes' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]));
        } catch (Throwable $exception) {
            try {
                $files->delete($path);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        return response()->json([
            'id' => $image->id,
            'src' => route('course-images.show', [$course, $image], false),
        ], 201);
    }

    public function show(Request $request, Course $course, CourseMediaImage $image): StreamedResponse
    {
        abort_unless($image->course_id === $course->id, 404);

        if ($request->user()->hasRole('estudiante')) {
            abort_unless(Gate::forUser($request->user())->allows('accessCourse', $course), 404);
        } else {
            Gate::authorize('view', $course);
        }

        $disk = Storage::disk('local');
        abort_unless($disk->exists($image->storage_path), 404);

        return $disk->response(
            $image->storage_path,
            basename($image->storage_path),
            [
                'Content-Type' => $image->mime_type,
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }
}
