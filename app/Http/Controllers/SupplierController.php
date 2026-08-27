<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index()
    {
        $this->authorize('view suppliers');
        return view('suppliers.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view suppliers');

        $query = Supplier::with('createdBy');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $searchValue = $request->input('search.value');
        if ($searchValue) {
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('phone', 'like', "%{$searchValue}%")
                  ->orWhere('gst_no', 'like', "%{$searchValue}%");
            });
        }

        $recordsTotal = Supplier::count();
        $recordsFiltered = (clone $query)->count();

        $columnsMap = [
            1 => 'name',
            2 => 'phone',
            3 => 'state',
            4 => 'gst_no',
            5 => 'address',
            7 => 'created_at',
        ];

        $orderColumn = $request->input('order.0.column');
        $orderDir    = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($orderColumn !== null && isset($columnsMap[$orderColumn])) {
            $query->orderBy($columnsMap[$orderColumn], $orderDir);
        } else {
            $query->orderBy('id', 'desc');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0) $length = 25;

        $suppliers = $query
            ->skip($start)
            ->take($length)
            ->get();

        $canEdit   = auth()->user()->can('edit suppliers');
        $canDelete = auth()->user()->can('delete suppliers');

        $data = $suppliers->map(function ($supplier, $index) use ($start, $canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input supplier-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.suppliers.toggle-status', $supplier) . '" ' . ($supplier->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($supplier->status);

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.suppliers.edit', $supplier) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.suppliers.destroy', $supplier) . '" data-row-id="supplier-row-' . $supplier->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            return [
                'index'      => $start + $index + 1,
                'name'       => $supplier->name,
                'phone'      => $supplier->phone ?? '-',
                'state'      => $supplier->state ?? '-',
                'gst_no'     => $supplier->gst_no ?? '-',
                'address'    => $supplier->address ?? '-',
                'status'     => $status,
                'created_at' => format_date($supplier->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create()
    {
        $this->authorize('create suppliers');
        $states = \App\Models\State::where('status', \App\Models\State::STATUS_ACTIVE)->orderBy('name')->get();
        return view('suppliers.create', compact('states'));
    }

    public function store(Request $request)
    {
        $this->authorize('create suppliers');

        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string', 'max:100'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'state'   => ['nullable', 'string', 'max:100'],
            'gst_no'  => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Supplier::create([
            'name'       => $request->name,
            'phone'      => $request->phone,
            'address'    => $request->address,
            'state'      => $request->state,
            'gst_no'     => $request->gst_no,
            'status'     => $request->has('status') ? 1 : 2,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier created successfully.',
        ]);
    }

    public function edit(Supplier $supplier)
    {
        $this->authorize('edit suppliers');
        $states = \App\Models\State::where('status', \App\Models\State::STATUS_ACTIVE)->orderBy('name')->get();
        return view('suppliers.edit', compact('supplier', 'states'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('edit suppliers');

        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string', 'max:100'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'state'   => ['nullable', 'string', 'max:100'],
            'gst_no'  => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $supplier->update([
            'name'    => $request->name,
            'phone'   => $request->phone,
            'address' => $request->address,
            'state'   => $request->state,
            'gst_no'  => $request->gst_no,
            'status'  => $request->has('status') ? 1 : 2,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier updated successfully.',
            'data'    => $supplier->only(['id', 'name', 'gst_no']),
        ]);
    }

    public function toggleStatus(Supplier $supplier)
    {
        $this->authorize('edit suppliers');

        $supplier->update([
            'status' => $supplier->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier status updated successfully.',
        ]);
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete suppliers');

        $supplier->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier deleted successfully.',
        ]);
    }
}
