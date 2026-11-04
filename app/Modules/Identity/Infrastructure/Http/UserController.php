<?php

namespace App\Modules\Identity\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\ChangeUserRole;
use App\Modules\Identity\Application\CreateUser;
use App\Modules\Identity\Application\SetUserStatus;
use App\Modules\Identity\Application\UpdateUser;
use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\RolePermissionRepository;
use App\Modules\Identity\Domain\RoleRecord;
use App\Modules\Identity\Domain\UserRecord;
use App\Modules\Identity\Domain\UserRepository;
use App\Modules\Identity\Infrastructure\Http\Requests\StoreUserRequest;
use App\Modules\Identity\Infrastructure\Http\Requests\UpdateUserRequest;
use App\Modules\Identity\Infrastructure\UserListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserRepository $users, private readonly RolePermissionRepository $roles) {}

    public function index(Request $request, UserListQuery $list): Response
    {
        $filters = $request->only(['q', 'role', 'status']);

        return Inertia::render('Users/Index', [
            'users' => $list->paginate($filters, $request->integer('page', 1)),
            'filters' => ['q' => $filters['q'] ?? '', 'role' => $filters['role'] ?? '', 'status' => $filters['status'] ?? ''],
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $createUser(
            $request->string('name'),
            $request->string('email'),
            $request->string('role')->toString(),
            $request->string('password'),
            $request->user()->id,
        );

        return redirect('/users')->with('success', 'Usuario creado. Deberá cambiar su contraseña al ingresar.');
    }

    public function show(int $id): Response
    {
        $user = $this->users->find($id) ?? abort(404);

        return Inertia::render('Users/Edit', ['user' => $this->present($user), 'roles' => $this->roleOptions()]);
    }

    public function update(int $id, UpdateUserRequest $request, UpdateUser $updateUser, ChangeUserRole $changeRole): RedirectResponse
    {
        $this->users->find($id) ?? abort(404);
        $updateUser($id, $request->string('name'), $request->string('email'), $request->user()->id);

        $role = $request->string('role')->toString();
        if ($this->users->find($id)->roleName !== $role) {
            $changeRole($id, $role, $request->user()->id);
        }

        return redirect('/users')->with('success', 'Usuario actualizado.');
    }

    public function status(int $id, Request $request, SetUserStatus $setStatus): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $this->users->find($id) ?? abort(404);
        $setStatus($id, (bool) $data['is_active'], $request->user()->id);

        return back()->with('success', 'Estado actualizado.');
    }

    /** @return array<string, mixed> */
    private function present(UserRecord $u): array
    {
        return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'is_active' => $u->isActive,
            'role' => $u->roleName, 'role_label' => $u->roleName ? (Role::tryFrom($u->roleName)?->label() ?? $u->roleName) : null];
    }

    /** @return list<array{value: string, label: string}> */
    private function roleOptions(): array
    {
        return array_map(
            fn (RoleRecord $r) => ['value' => $r->name, 'label' => $r->label],
            array_values(array_filter($this->roles->all(), fn (RoleRecord $r) => $r->isActive)),
        );
    }
}
