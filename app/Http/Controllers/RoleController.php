<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorize('view roles');
        $roles = Role::where('name', '!=', 'super-admin')
            ->withCount('users')
            ->with('permissions')
            ->get();
        return view('roles.index', compact('roles'));
    }

    public function data()
    {
        $this->authorize('view roles');

        $roles     = Role::where('name', '!=', 'super-admin')->withCount('users')->with('permissions')->get();
        $canEdit   = auth()->user()->can('edit roles');
        $canDelete = auth()->user()->can('delete roles');

        $data = $roles->map(function ($role, $index) use ($canEdit, $canDelete) {
            $permissions = $role->permissions->count() > 0
                ? '<span class="badge bg-label-primary">' . $role->permissions->count() . ' permission(s)</span>'
                : '<span class="badge bg-label-secondary">None</span>';

            $actions = '';
            if ($canEdit) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-info me-1" data-common-modal="' . route('admin.roles.edit', $role) . '" data-size="modal-xl"><i class="ti ti-pencil"></i></button>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.roles.destroy', $role) . '" data-row-id="role-row-' . $role->id . '"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'       => $index + 1,
                'name'        => '<span class="text-capitalize">' . $role->name . '</span>',
                'permissions' => $permissions,
                'users'       => '<span class="badge bg-label-info">' . $role->users_count . ' user(s)</span>',
                'created_at'  => format_date($role->created_at),
                'actions'     => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create roles');
        $permissions = Permission::get()->groupBy(function ($permission) {
            $parts = explode(' ', $permission->name);
            return ucfirst(end($parts));
        });
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $this->authorize('create roles');

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $role = Role::create(['name' => $request->name]);
        if ($request->permissions) {
            $role->syncPermissions(Permission::whereIn('id', $request->permissions)->get());
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Role created successfully.',
        ]);
    }

    public function edit(Role $role)
    {
        $this->authorize('edit roles');
        $permissions = Permission::get()->groupBy(function ($permission) {
            $parts = explode(' ', $permission->name);
            return ucfirst(end($parts));
        });
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissionIds'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('edit roles');

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:100', 'unique:roles,name,' . $role->id],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $role->update(['name' => $request->name]);
        $role->syncPermissions(
            $request->permissions ? Permission::whereIn('id', $request->permissions)->get() : []
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Role updated successfully.',
        ]);
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete roles');

        if ($role->users()->count() > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete role. ' . $role->users()->count() . ' user(s) are assigned to this role.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Role deleted successfully.',
        ]);
    }
}
