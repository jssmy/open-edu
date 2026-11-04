<?php

namespace App\Modules\Scheduling\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Scheduling\Application\ManageClassrooms;
use App\Modules\Scheduling\Infrastructure\Http\Requests\SaveClassroomRequest;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('scheduling.manage');

        $term = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();

        $paginator = Classroom::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10, ['*'], 'page', max(1, $request->integer('page', 1)));

        return Inertia::render('Scheduling/Classrooms', [
            'classrooms' => [
                'data' => $paginator->getCollection()->map(fn (Classroom $c): array => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'capacity' => $c->capacity,
                    'is_active' => $c->is_active,
                ])->all(),
                'meta' => [
                    'page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'from' => (int) $paginator->firstItem(),
                    'to' => (int) $paginator->lastItem(),
                ],
            ],
            'filters' => ['q' => $term, 'status' => $status],
        ]);
    }

    public function store(SaveClassroomRequest $request, ManageClassrooms $manage): RedirectResponse
    {
        $manage->save($request->validated());

        return back()->with('success', 'Aula creada.');
    }

    public function update(
        SaveClassroomRequest $request,
        Classroom $classroom,
        ManageClassrooms $manage,
    ): RedirectResponse {
        $manage->save($request->validated(), $classroom->id);

        return back()->with('success', 'Aula actualizada.');
    }

    public function destroy(Classroom $classroom, ManageClassrooms $manage): RedirectResponse
    {
        Gate::authorize('scheduling.manage');

        $manage->delete($classroom->id);

        return back()->with('success', 'Aula eliminada.');
    }
}

