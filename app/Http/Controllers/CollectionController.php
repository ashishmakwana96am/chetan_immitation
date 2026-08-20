<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CollectionController extends Controller
{
    public function index()
    {
        $this->authorize('view collections');
        return view('collections.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view collections');

        $query = Collection::with('createdBy')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $collections = $query->get();
        $canEdit     = auth()->user()->can('edit collections');
        $canDelete   = auth()->user()->can('delete collections');

        $data = $collections->map(function ($collection, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input collection-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.collections.toggle-status', $collection) . '" ' . ($collection->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($collection->status);

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.collections.edit', $collection) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.collections.destroy', $collection) . '" data-row-id="collection-row-' . $collection->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            return [
                'id'         => $collection->id,
                'index'      => $index + 1,
                'name'       => $collection->name,
                'short_name' => $collection->short_name ?? '-',
                'status'     => $status,
                'created_at' => format_date($collection->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create collections');
        return view('collections.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create collections');

        $validator = Validator::make($request->all(), [
            'name'       => ['required', 'string', 'max:150', Rule::unique('collections', 'name')->whereNull('deleted_at')],
            'short_name' => ['required', 'string', 'max:50'],
        ], [], [
            'name'       => 'Collection Name',
            'short_name' => 'Collection Short Name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Collection::create([
            'name'       => $request->name,
            'short_name' => $request->short_name,
            'status'     => $request->has('status') ? 1 : 2,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Collection created successfully.',
        ]);
    }

    public function edit(Collection $collection)
    {
        $this->authorize('edit collections');
        return view('collections.edit', compact('collection'));
    }

    public function update(Request $request, Collection $collection)
    {
        $this->authorize('edit collections');

        $validator = Validator::make($request->all(), [
            'name'       => ['required', 'string', 'max:150', Rule::unique('collections', 'name')->ignore($collection->id)->whereNull('deleted_at')],
            'short_name' => ['required', 'string', 'max:50'],
        ], [], [
            'name'       => 'Collection Name',
            'short_name' => 'Collection Short Name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $collection->update([
            'name'       => $request->name,
            'short_name' => $request->short_name,
            'status'     => $request->has('status') ? 1 : 2,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Collection updated successfully.',
        ]);
    }

    public function toggleStatus(Collection $collection)
    {
        $this->authorize('edit collections');

        $collection->update([
            'status' => $collection->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Collection status updated successfully.',
        ]);
    }

    public function destroy(Collection $collection)
    {
        $this->authorize('delete collections');

        $collection->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Collection deleted successfully.',
        ]);
    }
}
