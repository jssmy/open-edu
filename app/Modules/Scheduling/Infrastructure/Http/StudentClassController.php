<?php

namespace App\Modules\Scheduling\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Scheduling\Application\GetStudentClasses;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class StudentClassController extends Controller
{
    public function index(Request $request, GetStudentClasses $getStudentClasses): Response
    {
        $student = Student::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->firstOrFail();

        $data = $getStudentClasses($student->id);
        $status = $request->query('status', 'in_progress');

        return Inertia::render('Student/MyClasses', [
            'classes' => $data['classes'],
            'filters' => [
                'status' => is_string($status) ? $status : 'in_progress',
            ],
        ]);
    }
}
