<?php

namespace App\Modules\Progress\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Progress\Application\IssueCourseCertificate;
use App\Modules\Progress\Infrastructure\CertificatePdfGenerator;
use App\Modules\Progress\Infrastructure\Models\Certificate;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CertificateController extends Controller
{
    public function issue(
        Request $request,
        Course $course,
        IssueCourseCertificate $issueCertificate,
    ): RedirectResponse {
        Gate::authorize('accessCourse', $course);

        $user = $request->user();
        $student = Student::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $issueCertificate($course->id, $user->id, $student?->id);

        return to_route('study.courses.show', $course)->with('success', 'Certificado emitido exitosamente.');
    }

    public function download(Request $request, Certificate $certificate): StreamedResponse
    {
        $user = $request->user();
        $isOwner = $certificate->user_id === $user->id;
        $canManage = $user->can('courses.manage') || $user->can('enrollments.manage') || $user->can('reports.view');

        abort_unless($isOwner || $canManage, 403);

        $storagePath = CertificatePdfGenerator::resolveStoragePath($certificate->file_url);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($storagePath), 404);

        return $disk->download($storagePath, "{$certificate->code}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
