<?php

namespace App\Modules\Identity\Infrastructure;

use App\Models\User;
use App\Modules\Identity\Domain\Role;

class UserListQuery
{
    public const PER_PAGE = 10;

    /**
     * @param  array{q?: ?string, role?: ?string, status?: ?string}  $filters
     * @return array{data: list<array<string, mixed>>, meta: array{page: int, last_page: int, total: int, from: int, to: int}}
     */
    public function paginate(array $filters, int $page = 1): array
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $role = trim((string) ($filters['role'] ?? ''));
        $status = $filters['status'] ?? null;

        $paginator = User::query()
            ->with('roles')
            ->when($term !== '', fn ($query) => $query->where(function ($inner) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $inner->where('name', 'like', $like)->orWhere('email', 'like', $like);
            }))
            ->when($role !== '', fn ($query) => $query->role($role))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));

        return [
            'data' => $paginator->getCollection()->map(function (User $user): array {
                $roleName = $user->getRoleNames()->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'role' => $roleName,
                    'role_label' => $roleName ? (Role::tryFrom($roleName)?->label() ?? $roleName) : null,
                ];
            })->all(),
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
