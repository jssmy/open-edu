<?php

namespace App\Modules\Students\Infrastructure;

use App\Modules\Students\Infrastructure\Models\Student;

class StudentListQuery
{
    public const PER_PAGE = 10;

    /**
     * @param  array{q?: ?string, status?: ?string}  $filters
     * @return array{data: list<array<string, mixed>>, meta: array{page: int, last_page: int, total: int, from: int, to: int}}
     */
    public function paginate(array $filters, ?int $teacherUserId = null, int $page = 1): array
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? null;

        $paginator = Student::query()
            ->with('user')
            ->when($teacherUserId !== null, fn ($query) => $query->whereHas(
                'enrollments.course.teachers',
                fn ($teachers) => $teachers->whereKey($teacherUserId),
            ))
            ->when($term !== '', fn ($query) => $query->where(function ($inner) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $inner->where('identifier', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like));
            }))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true)->whereHas('user', fn ($user) => $user->where('is_active', true)))
            ->when($status === 'inactive', fn ($query) => $query->where(function ($inner): void {
                $inner->where('is_active', false)->orWhereHas('user', fn ($user) => $user->where('is_active', false));
            }))
            ->orderBy('id')
            ->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));

        return [
            'data' => $paginator->getCollection()->map(fn (Student $student): array => [
                'id' => $student->id,
                'user_id' => $student->user_id,
                'name' => $student->user->name,
                'email' => $student->user->email,
                'identifier' => $student->identifier,
                'phone' => $student->phone,
                'is_active' => $student->is_active && $student->user->is_active,
            ])->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'from' => (int) $paginator->firstItem(),
                'to' => (int) $paginator->lastItem(),
            ],
        ];
    }
}
