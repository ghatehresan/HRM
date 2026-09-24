<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request, UserService $users): View
    {
        $this->authorize('viewAny', User::class);

        $search = $request->query('q');

        return view('admin.users.index', [
            'users' => $users->paginateUsers(is_string($search) ? $search : null),
            'q' => is_string($search) ? $search : '',
        ]);
    }

    public function create(UserService $users): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', ['roles' => $users->allRoles()]);
    }

    public function store(StoreUserRequest $request, UserService $users): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->safe()->all();
        $user = $users->createUser($data, (array) ($data['roles'] ?? []));

        return redirect()->route('admin.users.edit', $user)->with('status', 'کاربر ساخته شد.');
    }

    public function edit(User $user, UserService $users): View
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $users->allRoles(),
            'selectedRoles' => $user->roles->pluck('slug')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UserService $users): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->safe()->all();
        $users->updateUser(
            $this->authedUser(),
            $user,
            $data,
            array_key_exists('roles', $data) ? (array) $data['roles'] : null,
        );

        return redirect()->route('admin.users.edit', $user)->with('status', 'کاربر به‌روزرسانی شد.');
    }
}
