<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(RoleService $roles): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', ['roles' => $roles->paginateRoles()]);
    }

    public function create(RoleService $roles): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.create', [
            'grouped' => $roles->groupedPermissions(),
            'selected' => [],
        ]);
    }

    public function store(StoreRoleRequest $request, RoleService $roles): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $data = $request->safe()->all();
        $role = $roles->createRole($data, (array) ($data['permissions'] ?? []));

        return redirect()->route('admin.roles.edit', $role)->with('status', 'نقش ساخته شد.');
    }

    public function edit(Role $role, RoleService $roles): View
    {
        $this->authorize('update', $role);

        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'grouped' => $roles->groupedPermissions(),
            'selected' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, RoleService $roles): RedirectResponse
    {
        $this->authorize('update', $role);

        $data = $request->safe()->all();
        $roles->updateRole(
            $role,
            $data,
            array_key_exists('permissions', $data) ? (array) $data['permissions'] : null,
        );

        return redirect()->route('admin.roles.edit', $role)->with('status', 'نقش به‌روزرسانی شد.');
    }
}
