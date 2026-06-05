<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Module;

class ModuleController extends Controller
{
    public function index()
    {
        $this->authorize('view modules');
        $modules = Module::with('parent')->orderBy('sort_order')->get();
        return view('modules.index', compact('modules'));
    }

    public function data()
    {
        $this->authorize('view modules');

        $parents = Module::whereNull('parent_id')->orderBy('sort_order')->get();
        $orderedModules = collect();
        foreach ($parents as $parent) {
            $orderedModules->push($parent);
            $children = Module::with('parent')
                ->where('parent_id', $parent->id)
                ->orderBy('sort_order')
                ->get();
            foreach ($children as $child) {
                $orderedModules->push($child);
            }
        }

        $canEdit   = auth()->user()->can('edit modules');
        $canDelete = auth()->user()->can('delete modules');

        $data = $orderedModules->map(function ($module, $index) use ($canEdit, $canDelete) {
            $parent = $module->parent
                ? '<span class="badge bg-label-primary">' . $module->parent->name . '</span>'
                : '<span class="badge bg-label-secondary">None</span>';

            $icon = $module->icon
                ? '<code><i class="' . $module->icon . ' me-1"></i>' . $module->icon . '</code>'
                : '<span class="text-muted">-</span>';

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.modules.edit', $module) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.modules.destroy', $module) . '" data-row-id="module-row-' . $module->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            $name = $module->parent_id
                ? '<span class="text-muted ps-3"><i class="ti ti-corner-down-right me-1"></i>' . $module->name . '</span>'
                : '<strong>' . $module->name . '</strong>';

            return [
                'index'          => $index + 1,
                'name'           => $name,
                'parent'         => $parent,
                'icon'           => $icon,
                'route'          => $module->route ?? '<span class="text-muted">-</span>',
                'active_pattern' => $module->active_pattern ?? '<span class="text-muted">-</span>',
                'permission'     => $module->permission ? '<code>' . $module->permission . '</code>' : '<span class="text-muted">-</span>',
                'sort_order'     => '<span class="badge bg-label-success">' . $module->sort_order . '</span>',
                'actions'        => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create modules');
        $parents = Module::whereNull('parent_id')->orderBy('name')->get();
        return view('modules.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $this->authorize('create modules');

        $validator = Validator::make($request->all(), [
            'name'           => ['required', 'string', 'max:100', 'unique:modules,name'],
            'parent_id'      => ['nullable', 'exists:modules,id'],
            'icon'           => ['nullable', 'string', 'max:100'],
            'route'          => ['nullable', 'string', 'max:100'],
            'active_pattern' => ['nullable', 'string', 'max:150'],
            'permission'     => ['nullable', 'string', 'max:100'],
            'sort_order'     => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Module::create($request->all());

        return response()->json([
            'status'  => 'success',
            'message' => 'Module created successfully.',
        ]);
    }

    public function edit(Module $module)
    {
        $this->authorize('edit modules');
        $parents = Module::whereNull('parent_id')->where('id', '!=', $module->id)->orderBy('name')->get();
        return view('modules.edit', compact('module', 'parents'));
    }

    public function update(Request $request, Module $module)
    {
        $this->authorize('edit modules');

        $validator = Validator::make($request->all(), [
            'name'           => ['required', 'string', 'max:100', 'unique:modules,name,' . $module->id],
            'parent_id'      => ['nullable', 'exists:modules,id'],
            'icon'           => ['nullable', 'string', 'max:100'],
            'route'          => ['nullable', 'string', 'max:100'],
            'active_pattern' => ['nullable', 'string', 'max:150'],
            'permission'     => ['nullable', 'string', 'max:100'],
            'sort_order'     => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $module->update($request->all());

        return response()->json([
            'status'  => 'success',
            'message' => 'Module updated successfully.',
        ]);
    }

    public function destroy(Module $module)
    {
        $this->authorize('delete modules');
        $module->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Module deleted successfully.',
        ]);
    }
}
