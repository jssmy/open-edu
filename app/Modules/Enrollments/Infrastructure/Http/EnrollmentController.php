<?php

namespace App\Modules\Enrollments\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Enrollments\Application\ChangeEnrollmentStatus;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Http\Requests\ChangeEnrollmentStatusRequest;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use Illuminate\Http\RedirectResponse;

class EnrollmentController extends Controller
{
    public function status(
        ChangeEnrollmentStatusRequest $request,
        Enrollment $enrollment,
        ChangeEnrollmentStatus $changeStatus,
    ): RedirectResponse {
        $changeStatus(
            $enrollment->id,
            EnrollmentStatus::from($request->validated('status')),
            $request->user()->id,
        );

        return back()->with('success', 'Estado de inscripción actualizado.');
    }
}
