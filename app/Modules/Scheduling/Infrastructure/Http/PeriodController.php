<?php

namespace App\Modules\Scheduling\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Scheduling\Application\ManagePeriods;
use App\Modules\Scheduling\Infrastructure\Http\Requests\SavePeriodRequest;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PeriodController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('scheduling.manage');

        $term = trim($request->string('q')->toString());

        $paginator = AcademicPeriod::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->orderByDesc('starts_on')
            ->paginate(10, ['*'], 'page', max(1, $request->integer('page', 1)));

        return Inertia::render('Scheduling/Periods', [
            'periods' => [
                'data' => $paginator->getCollection()->map(fn (AcademicPeriod $p): array => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'starts_on' => $p->starts_on->toDateString(),
                    'ends_on' => $p->ends_on->toDateString(),
                    'has_ended' => $p->hasEnded(),
                ])->all(),
                'meta' => [
                    'page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'from' => (int) $paginator->firstItem(),
                    'to' => (int) $paginator->lastItem(),
                ],
            ],
            'filters' => ['q' => $term],
        ]);
    }

    public function store(SavePeriodRequest $request, ManagePeriods $manage): RedirectResponse
    {
        $manage->save($request->validated());

        return back()->with('success', 'Periodo académico creado.');
    }

    public function update(
        SavePeriodRequest $request,
        AcademicPeriod $period,
        ManagePeriods $manage,
    ): RedirectResponse {
        $manage->save($request->validated(), $period->id);

        return back()->with('success', 'Periodo académico actualizado.');
    }

    public function destroy(AcademicPeriod $period, ManagePeriods $manage): RedirectResponse
    {
        Gate::authorize('scheduling.manage');

        $manage->delete($period->id);

        return back()->with('success', 'Periodo académico eliminado.');
    }
}

