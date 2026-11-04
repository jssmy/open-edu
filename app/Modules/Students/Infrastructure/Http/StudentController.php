<?php

namespace App\Modules\Students\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Students\Application\RegisterStudent;
use App\Modules\Students\Application\SetStudentStatus;
use App\Modules\Students\Application\UpdateStudent;
use App\Modules\Students\Domain\Student as StudentRecord;
use App\Modules\Students\Domain\StudentRepository;
use App\Modules\Students\Infrastructure\Http\Requests\SaveStudentRequest;
use App\Modules\Students\Infrastructure\Models\Student;
use App\Modules\Students\Infrastructure\StudentListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(private readonly StudentRepository $students) {}

    public function index(Request $request, StudentListQuery $list): Response
    {
        Gate::authorize('viewAny', Student::class);
        $teacherId = $request->user()->hasRole('docente') ? $request->user()->id : null;

        return Inertia::render('Students/Index', [
            'students' => $list->paginate($request->only(['q', 'status']), $teacherId, $request->integer('page', 1)),
            'filters' => ['q' => $request->string('q')->toString(), 'status' => $request->string('status')->toString()],
            'canManageStudents' => $request->user()->can('students.manage'),
        ]);
    }

    public function store(SaveStudentRequest $request, RegisterStudent $register): RedirectResponse
    {
        $data = $request->validated();
        $register(
            $data['name'],
            $data['email'],
            $data['temporary_password'],
            $data['identifier'] ?? null,
            $data['phone'] ?? null,
            $request->user()->id,
        );

        return to_route('students.index')->with('success', 'Estudiante registrado.');
    }

    public function show(Request $request, int $studentId): Response
    {
        $student = Student::query()->findOrFail($studentId);
        Gate::authorize('view', $student);
        $record = $this->students->find($studentId) ?? abort(404);
        $teacherId = $request->user()->hasRole('docente') ? $request->user()->id : null;

        return Inertia::render('Students/Show', [
            'student' => $this->present($record),
            'courses' => $this->students->coursesFor($studentId, $teacherId),
            'canManageStudents' => $request->user()->can('students.manage'),
        ]);
    }

    public function courses(Request $request, int $studentId): Response
    {
        return $this->show($request, $studentId);
    }

    public function update(
        SaveStudentRequest $request,
        int $studentId,
        UpdateStudent $update,
    ): RedirectResponse {
        $data = $request->validated();
        $update(
            $studentId,
            $data['name'],
            $data['email'],
            $data['identifier'] ?? null,
            $data['phone'] ?? null,
            $request->user()->id,
        );

        return to_route('students.show', $studentId)->with('success', 'Estudiante actualizado.');
    }

    public function status(Request $request, int $studentId, SetStudentStatus $setStatus): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $setStatus($studentId, (bool) $data['is_active'], $request->user()->id);

        return back()->with('success', 'Estado del estudiante actualizado.');
    }

    /** @return array{id: int, user_id: int, name: string, email: string, identifier: ?string, phone: ?string, is_active: bool} */
    private function present(StudentRecord $student): array
    {
        return [
            'id' => $student->id,
            'user_id' => $student->userId,
            'name' => $student->name,
            'email' => $student->email,
            'identifier' => $student->identifier,
            'phone' => $student->phone,
            'is_active' => $student->isActive,
        ];
    }
}
