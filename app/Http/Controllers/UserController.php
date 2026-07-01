<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Crud/Users', [
            'users' => User::query()
                ->with(['departments:id,name', 'roles:id,name'])
                ->latest()
                ->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $departmentIds = $data['department_ids'] ?? [];
        $role = $data['role'];

        unset($data['department_ids'], $data['role']);

        $user = User::create($data);
        $user->departments()->sync($departmentIds);
        $user->syncRoles([$role]);

        return back()->with('success', 'Usuario creado.');
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $departmentIds = $data['department_ids'] ?? [];
        $role = $data['role'];

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        unset($data['department_ids'], $data['role']);

        $user->update($data);
        $user->departments()->sync($departmentIds);
        $user->syncRoles([$role]);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', Password::defaults()],
        ]);
    }
}
