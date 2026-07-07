<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use App\Models\Module;

class PermissionController extends Controller
{
    public function index()
    {
        $this->authorize('view permissions');
        $permissions = Permission::withCount('roles')->orderBy('id', 'desc')->get();
        $modules     = Permission::whereNotNull('module')->distinct()->orderBy('module')->pluck('module');
        return view('permissions.index', compact('permissions', 'modules'));
    }

    public function data(Request $request)
    {
        $this->authorize('view permissions');

        $query = Permission::withCount('roles')->orderBy('id', 'desc');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        $permissions = $query->get();
        $canEdit     = auth()->user()->can('edit permissions');
        $canDelete   = auth()->user()->can('delete permissions');

        $data = $permissions->map(function ($permission, $index) use ($canEdit, $canDelete) {
            $roles = $permission->roles_count > 0
                ? '<span class="badge bg-label-primary">' . $permission->roles_count . ' role(s)</span>'
                : '<span class="badge bg-label-secondary">None</span>';

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.permissions.edit', $permission) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.permissions.destroy', $permission) . '" data-row-id="permission-row-' . $permission->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            return [
                'index'      => $index + 1,
                'name'       => $permission->name,
                'module'     => '<span class="badge bg-label-info">' . ($permission->module ?? 'None') . '</span>',
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
        $modules = Module::whereNotNull('parent_id')->orderBy('name')->get();
        return view('permissions.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $this->authorize('create permissions');

        $validator = Validator::make($request->all(), [
            'name'   => ['required', 'string', 'max:100', 'unique:permissions,name'],
            'module' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $permission = Permission::create([
            'name'   => $request->name,
            'module' => $request->module,
        ]);

        ActivityLogger::log('Permission Management', 'create', $permission, null, ['name' => $permission->name, 'module' => $permission->module], 'Permission "' . $permission->name . '" created');

        return response()->json([
            'status'  => 'success',
            'message' => 'Permission created successfully.',
        ]);
    }

    public function edit(Permission $permission)
    {
        $this->authorize('edit permissions');
        $modules = Module::whereNotNull('parent_id')->orderBy('name')->get();
        return view('permissions.edit', compact('permission', 'modules'));
    }

    public function update(Request $request, Permission $permission)
    {
        $this->authorize('edit permissions');

        $validator = Validator::make($request->all(), [
            'name'   => ['required', 'string', 'max:100', 'unique:permissions,name,' . $permission->id],
            'module' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $old = ['name' => $permission->name, 'module' => $permission->module];

        $permission->update([
            'name'   => $request->name,
            'module' => $request->module,
        ]);

        ActivityLogger::log('Permission Management', 'update', $permission, $old, ['name' => $permission->name, 'module' => $permission->module], 'Permission "' . $permission->name . '" updated');

        return response()->json([
            'status'  => 'success',
            'message' => 'Permission updated successfully.',
        ]);
    }

    public function destroy(Permission $permission)
    {
        $this->authorize('delete permissions');

        $name = $permission->name;
        $permission->delete();

        ActivityLogger::log('Permission Management', 'delete', null, ['name' => $name], null, 'Permission "' . $name . '" deleted');

        return response()->json([
            'status'  => 'success',
            'message' => 'Permission deleted successfully.',
        ]);
    }
}
