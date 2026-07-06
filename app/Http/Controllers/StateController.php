<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StateController extends Controller
{
    public function index()
    {
        $this->authorize('view states');
        return view('states.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view states');

        $query = State::with('createdBy')->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $states = $query->get();
        $canEdit   = auth()->user()->can('edit states');
        $canDelete = auth()->user()->can('delete states');

        $data = $states->map(function ($state, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input state-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.states.toggle-status', $state) . '" ' . ($state->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($state->status);

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.states.edit', $state) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.states.destroy', $state) . '" data-row-id="state-row-' . $state->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            return [
                'index'           => $index + 1,
                'name'            => $state->name,
                'shipping_charge' => format_price($state->shipping_charge),
                'delivery_days'   => $state->delivery_days . ' day' . ($state->delivery_days == 1 ? '' : 's'),
                'status'          => $status,
                'actions'         => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create states');
        return view('states.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create states');

        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'max:100', 'unique:states,name'],
            'shipping_charge' => ['required', 'numeric', 'min:0'],
            'delivery_days'   => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        State::create([
            'name'            => $request->name,
            'shipping_charge' => $request->shipping_charge,
            'delivery_days'   => $request->delivery_days,
            'status'          => $request->has('status') ? 1 : 2,
            'created_by'      => auth()->id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'State created successfully.',
        ]);
    }

    public function edit(State $state)
    {
        $this->authorize('edit states');
        return view('states.edit', compact('state'));
    }

    public function update(Request $request, State $state)
    {
        $this->authorize('edit states');

        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'max:100', 'unique:states,name,' . $state->id],
            'shipping_charge' => ['required', 'numeric', 'min:0'],
            'delivery_days'   => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $state->update([
            'name'            => $request->name,
            'shipping_charge' => $request->shipping_charge,
            'delivery_days'   => $request->delivery_days,
            'status'          => $request->has('status') ? 1 : 2,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'State updated successfully.',
        ]);
    }

    public function toggleStatus(State $state)
    {
        $this->authorize('edit states');

        $state->update([
            'status' => $state->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'State status updated successfully.',
        ]);
    }

    public function destroy(State $state)
    {
        $this->authorize('delete states');

        $state->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'State deleted successfully.',
        ]);
    }
}
