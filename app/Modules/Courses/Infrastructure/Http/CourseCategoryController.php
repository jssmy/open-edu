<?php

namespace App\Modules\Courses\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Application\CreateCourseCategory;
use App\Modules\Courses\Infrastructure\Http\Requests\SaveCourseCategoryRequest;
use Illuminate\Http\RedirectResponse;

final class CourseCategoryController extends Controller
{
    public function store(
        SaveCourseCategoryRequest $request,
        CreateCourseCategory $createCategory,
    ): RedirectResponse {
        $createCategory(
            (string) $request->validated('name'),
            (string) ($request->validated('role_name') ?? 'estudiante'),
        );

        return back()->with('success', 'Categoría creada.');
    }
}
