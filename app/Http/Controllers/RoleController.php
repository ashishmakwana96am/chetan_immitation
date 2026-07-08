<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorize('view roles');
        $currentUserRoles = auth()->user()->roles->pluck('name')->toArray();
        $roles = Role::where('name', '!=', 'super-admin')
            ->whereNotIn('name', $currentUserRoles)
            ->withCount('users')
            ->with('permissions')
            ->orderBy('id', 'desc')
            ->get();
        return view('roles.index', compact('roles'));
    }

    public function data()
    {
        $this->authorize('view roles');

        $currentUserRoles = auth()->user()->roles->pluck('name')->toArray();
        $roles = Role::where('name', '!=', 'super-admin')
            ->whereNotIn('name', $currentUserRoles)
            ->withCount('users')
            ->with('permissions')
            ->orderBy('id', 'desc')
            ->get();
        $canEdit   = auth()->user()->can('edit roles');
        $canDelete = auth()->user()->can('delete roles');

        $data = $roles->map(function ($role, $index) use ($canEdit, $canDelete) {
            $permissions = $role->permissions->count() > 0
                ? '<span class="badge bg-label-primary">' . $role->permissions->count() . ' permission(s)</span>'
                : '<span class="badge bg-label-secondary">None</span>';

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.roles.edit', $role) . '" data-size="modal-xl"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.roles.destroy', $role) . '" data-row-id="role-row-' . $role->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
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
        $customOrder = [
            'Users', 'Roles', 'Locations', 
            'Categories', 'Sub Categories', 'Products', 
            'Suppliers', 'Purchases', 
            'Customers', 'Sales', 'Reports'
        ];
        $permissions = Permission::whereNotIn('module', ['Permissions', 'Modules'])->get()->groupBy(function ($permission) {
            if (!empty($permission->module)) {
                return $permission->module;
            }
            if (str_contains($permission->name, 'sub categories')) {
                return 'Sub Categories';
            }
            if (str_contains($permission->name, 'password')) {
                return 'Users';
            }
            $parts = explode(' ', $permission->name);
            return ucfirst(end($parts));
        })->sortBy(function ($val, $key) use ($customOrder) {
            $idx = array_search($key, $customOrder);
            return $idx !== false ? $idx : 999;
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
        $permissionNames = [];
        if ($request->permissions) {
            $permissions = Permission::whereIn('id', $request->permissions)->get();
            $role->syncPermissions($permissions);
            $permissionNames = $permissions->pluck('name')->all();
        }

        ActivityLogger::log('Role Management', 'create', $role, null, ['name' => $role->name, 'permissions' => $permissionNames], 'Role "' . $role->name . '" created');

        return response()->json([
            'status'  => 'success',
            'message' => 'Role created successfully.',
        ]);
    }

    public function edit(Role $role)
    {
        $this->authorize('edit roles');
        $customOrder = [
            'Users', 'Roles', 'Locations', 
            'Categories', 'Sub Categories', 'Products', 
            'Suppliers', 'Purchases', 
            'Customers', 'Sales', 'Reports'
        ];
        $permissions = Permission::whereNotIn('module', ['Permissions', 'Modules'])->get()->groupBy(function ($permission) {
            if (!empty($permission->module)) {
                return $permission->module;
            }
            if (str_contains($permission->name, 'sub categories')) {
                return 'Sub Categories';
            }
            if (str_contains($permission->name, 'password')) {
                return 'Users';
            }
            $parts = explode(' ', $permission->name);
            return ucfirst(end($parts));
        })->sortBy(function ($val, $key) use ($customOrder) {
            $idx = array_search($key, $customOrder);
            return $idx !== false ? $idx : 999;
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

        $oldName = $role->name;
        $oldPermissionNames = $role->permissions->pluck('name')->all();

        $role->update(['name' => $request->name]);
        $newPermissions = $request->permissions ? Permission::whereIn('id', $request->permissions)->get() : collect();
        $role->syncPermissions($newPermissions);
        $newPermissionNames = $newPermissions->pluck('name')->all();

        ActivityLogger::log(
            'Role Management',
            'update',
            $role,
            ['name' => $oldName, 'permissions' => $oldPermissionNames],
            ['name' => $role->name, 'permissions' => $newPermissionNames],
            'Role "' . $role->name . '" updated'
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

        $roleName = $role->name;
        $role->delete();

        ActivityLogger::log('Role Management', 'delete', null, ['name' => $roleName], null, 'Role "' . $roleName . '" deleted');

        return response()->json([
            'status'  => 'success',
            'message' => 'Role deleted successfully.',
        ]);
    }
}
