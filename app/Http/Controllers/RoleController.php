<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorize('view roles');
        $user = auth()->user();
        $isRestricted = $user->location_id && $user->type !== 'super-admin';

        $roles = Role::where('name', '!=', 'super-admin')
            ->when($isRestricted, fn($q) => $q->where('location_id', $user->location_id))
            ->withCount('users')
            ->with('permissions')
            ->orderBy('id', 'desc')
            ->get();
        return view('roles.index', compact('roles'));
    }

    public function data()
    {
        $this->authorize('view roles');
        $user = auth()->user();
        $isRestricted = $user->location_id && $user->type !== 'super-admin';

        $roles = Role::where('name', '!=', 'super-admin')
            ->when($isRestricted, fn($q) => $q->where('location_id', $user->location_id))
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
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
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

        $locations = \App\Models\Location::where('status', 1)->orderBy('name')->get();

        return view('roles.create', compact('permissions', 'locations'));
    }

    public function store(Request $request)
    {
        $this->authorize('create roles');

        $user = auth()->user();
        $isRestricted = $user->location_id && $user->type !== 'super-admin';

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
            'location_id'   => [$isRestricted ? 'nullable' : 'required', 'exists:locations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'location_id' => $isRestricted ? $user->location_id : ($request->location_id ?: null)
        ]);
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

        $user = auth()->user();
        $isRestricted = $user->location_id && $user->type !== 'super-admin';

        if ($isRestricted && $role->location_id !== $user->location_id) {
            abort(403, 'Unauthorized action.');
        }
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
        $locations = \App\Models\Location::where('status', 1)->orderBy('name')->get();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissionIds', 'locations'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('edit roles');

        $user = auth()->user();
        $isRestricted = $user->location_id && $user->type !== 'super-admin';

        if ($isRestricted && $role->location_id !== $user->location_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:100', 'unique:roles,name,' . $role->id],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
            'location_id'   => [$isRestricted ? 'nullable' : 'required', 'exists:locations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $locationId = $isRestricted ? $user->location_id : ($request->location_id ?: null);
        $role->update([
            'name' => $request->name,
            'location_id' => $locationId
        ]);
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

        $user = auth()->user();
        $isRestricted = $user->location_id && $user->type !== 'super-admin';

        if ($isRestricted && $role->location_id !== $user->location_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

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
