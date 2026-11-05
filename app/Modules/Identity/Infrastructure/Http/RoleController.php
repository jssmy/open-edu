<?php

namespace App\Modules\Identity\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\CreateRole;
use App\Modules\Identity\Application\SetRoleStatus;
use App\Modules\Identity\Application\UpdateRolePermissions;
use App\Modules\Identity\Domain\PermissionRecord;
use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\RolePermissionRepository;
use App\Modules\Identity\Domain\RoleRecord;
use App\Modules\Identity\Infrastructure\Http\Requests\StoreRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(private readonly RolePermissionRepository $roles) {}

    public function index(Request $request): Response
    {
        $filters = ['q' => (string) $request->query('q', ''), 'status' => (string) $request->query('status', '')];

        return Inertia::render('Roles/Index', [
            'roles' => array_map(fn (RoleRecord $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'label' => $r->label,
                'description' => $r->description,
                'is_active' => $r->isActive,
                'is_system' => $r->isSystem,
                'locked' => $r->name === Role::Administrador->value,
                'user_count' => $r->userCount,
                'permissions' => $this->roles->permissionsOf($r->name),
            ], $this->roles->all($filters['q'], $filters['status'])),
            'filters' => $filters,
            'permissions' => array_map(fn (PermissionRecord $p) => $p->toArray(), $this->roles->allPermissionRecords()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Create', [
            'permissions' => array_map(fn (PermissionRecord $p) => $p->toArray(), $this->roles->allPermissionRecords()),
        ]);
    }

    public function store(StoreRoleRequest $request, CreateRole $create): RedirectResponse
    {
        $create(
            $request->string('name'),
            $request->input('description'),
            $request->input('permissions', []),
            $request->user()->id,
        );

        return redirect('/roles')->with('success', 'Rol creado.');
    }

    public function update(int $id, Request $request, UpdateRolePermissions $update): RedirectResponse
    {
        $role = $this->roles->find($id) ?? abort(404);
        $data = $request->validate(['permissions' => ['present', 'array'], 'permissions.*' => ['string']]);
        $update($role->name, $data['permissions'], $request->user()->id);

        return back()->with('success', 'Permisos actualizados.');
    }

    public function status(int $id, Request $request, SetRoleStatus $setStatus): RedirectResponse
    {
        $data = $request->validate(['active' => ['required', 'boolean'], 'confirm' => ['sometimes', 'boolean']]);
        $this->roles->find($id) ?? abort(404);
        $setStatus($id, (bool) $data['active'], (bool) ($data['confirm'] ?? false), $request->user()->id);

        return back()->with('success', 'Estado del rol actualizado.');
    }
}
