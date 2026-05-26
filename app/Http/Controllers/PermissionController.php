<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $this->authorize('view permissions');
        $permissions = Permission::withCount('roles')->latest()->get();
        return view('permissions.index', compact('permissions'));
    }

    public function data()
    {
        $this->authorize('view permissions');

        $permissions = Permission::withCount('roles')->latest()->get();
        $canEdit     = auth()->user()->can('edit permissions');
        $canDelete   = auth()->user()->can('delete permissions');

        $data = $permissions->map(function ($permission, $index) use ($canEdit, $canDelete) {
            $roles = $permission->roles_count > 0
                ? '<span class="badge bg-label-primary">' . $permission->roles_count . ' role(s)</span>'
                : '<span class="badge bg-label-secondary">None</span>';

            $actions = '';
            if ($canEdit) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-info me-1" data-common-modal="' . route('admin.permissions.edit', $permission) . '"><i class="ti ti-pencil"></i></button>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.permissions.destroy', $permission) . '" data-row-id="permission-row-' . $permission->id . '"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'      => $index + 1,
                'name'       => $permission->name,
                'roles'      => $roles,
                'created_at' => format_date($permission->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create permissions');
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create permissions');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', 'unique:permissions,name'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Permission::create(['name' => $request->name]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Permission created successfully.',
        ]);
    }

    public function edit(Permission $permission)
    {
        $this->authorize('edit permissions');
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $this->authorize('edit permissions');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', 'unique:permissions,name,' . $permission->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $permission->update(['name' => $request->name]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Permission updated successfully.',
        ]);
    }

    public function destroy(Permission $permission)
    {
        $this->authorize('delete permissions');

        $permission->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Permission deleted successfully.',
        ]);
    }
}
