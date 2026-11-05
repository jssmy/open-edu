<?php

namespace App\Modules\Scheduling\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Scheduling\Application\GetStudentClasses;
use Illuminate\Http\Request;
use Inertia\Response;

final class ClassDispatcherController extends Controller
{
    public function __invoke(
        Request $request,
        StudentClassController $studentController,
        TeacherClassController $teacherController,
    ): Response {
        $user = $request->user();

        if ($user->hasRole('estudiante')) {
            return $studentController->index($request, app(GetStudentClasses::class));
        }

        return $teacherController->index($request);
    }
}
