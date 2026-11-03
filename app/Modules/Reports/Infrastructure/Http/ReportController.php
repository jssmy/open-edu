<?php

namespace App\Modules\Reports\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Reports\Application\GetReports;
use App\Modules\Reports\Application\ReportType;
use App\Modules\Reports\Infrastructure\CsvExporter;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function courseProgress(Course $course, GetReports $reports): Response
    {
        Gate::authorize('viewCourseProgress', $course);

        return Inertia::render('Progress/Course', $reports->courseProgress($course->id));
    }

    public function myProgress(Request $request, GetReports $reports): Response
    {
        $student = Student::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->firstOrFail();
        Gate::authorize('viewMyProgress', $student);

        return Inertia::render('Progress/MyProgress', [
            'courses' => $reports->myProgress($student->id),
        ]);
    }

    public function enrollments(
        Request $request,
        GetReports $reports,
        CsvExporter $csvExporter,
    ): Response|StreamedResponse {
        return $this->report($request, ReportType::Enrollments, $reports, $csvExporter);
    }

    public function progress(
        Request $request,
        GetReports $reports,
        CsvExporter $csvExporter,
    ): Response|StreamedResponse {
        return $this->report($request, ReportType::Progress, $reports, $csvExporter);
    }

    public function results(
        Request $request,
        GetReports $reports,
        CsvExporter $csvExporter,
    ): Response|StreamedResponse {
        return $this->report($request, ReportType::Results, $reports, $csvExporter);
    }

    private function report(
        Request $request,
        ReportType $report,
        GetReports $reports,
        CsvExporter $csvExporter,
    ): Response|StreamedResponse {
        $filterRules = [
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'student_id' => ['sometimes', 'integer', 'exists:students,id'],
            'format' => ['sometimes', Rule::in(['csv'])],
        ];

        if ($report === ReportType::Results) {
            $filterRules['status'] = ['prohibited'];
            $filterRules['passed'] = ['sometimes', 'boolean'];
        } else {
            $filterRules['status'] = ['sometimes', Rule::enum(EnrollmentStatus::class)];
            $filterRules['passed'] = ['prohibited'];
        }

        $validated = $request->validate($filterRules);
        $filters = [];

        if (isset($validated['course_id'])) {
            $filters['course_id'] = (int) $validated['course_id'];
            $course = Course::query()->findOrFail($filters['course_id']);
            Gate::authorize('viewCourseProgress', $course);
        }

        if (isset($validated['student_id'])) {
            $filters['student_id'] = (int) $validated['student_id'];
        }

        if (isset($validated['status'])) {
            $filters['status'] = $validated['status'];
        }

        if (array_key_exists('passed', $validated)) {
            $filters['passed'] = $request->boolean('passed');
        }

        $teacherId = $request->user()->hasRole('docente')
            ? $request->user()->id
            : null;

        if (($validated['format'] ?? null) === 'csv') {
            return $csvExporter->download(
                $report,
                $reports->exportRows($report, $filters, $teacherId),
            );
        }

        $courses = Course::query()
            ->when(
                $teacherId !== null,
                fn ($query) => $query->whereHas(
                    'teachers',
                    fn ($teachers) => $teachers->whereKey($teacherId),
                ),
            )
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Course $course): array => ['id' => $course->id, 'name' => $course->name])
            ->all();
        $students = Student::query()
            ->where('is_active', true)
            ->when(
                $teacherId !== null,
                fn ($query) => $query->whereHas(
                    'enrollments.course.teachers',
                    fn ($teachers) => $teachers->whereKey($teacherId),
                ),
            )
            ->with('user:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->user->name,
            ])
            ->all();

        return Inertia::render('Reports/Index', [
            'report' => $report->value,
            'rows' => $reports->rows($report, $filters, $teacherId),
            'filters' => $filters,
            'courses' => $courses,
            'students' => $students,
        ]);
    }
}
