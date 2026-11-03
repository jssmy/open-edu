<?php

namespace App\Modules\Identity\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\UpdateRolePermissions;
use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\RolePermissionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role as RoleModel;

class RoleController extends Controller
{
    public function __construct(private readonly RolePermissionRepository $roles) {}

    public function index(): Response
    {
        return Inertia::render('Roles/Index', [
            'roles' => collect(Role::cases())->map(fn (Role $r) => [
                'id' => RoleModel::findByName($r->value, 'web')->id,
                'name' => $r->value,
                'label' => $r->label(),
                'locked' => $r === Role::Administrador,
                'permissions' => $this->roles->permissionsOf($r),
            ])->all(),
            'permissions' => $this->roles->allPermissions(),
        ]);
    }

    public function update(int $id, Request $request, UpdateRolePermissions $update): RedirectResponse
    {
        $role = Role::from(RoleModel::findOrFail($id)->name);
        $data = $request->validate(['permissions' => ['present', 'array'], 'permissions.*' => ['string']]);
        $update($role, $data['permissions'], $request->user()->id);

        return back()->with('success', 'Permisos actualizados.');
    }
}
