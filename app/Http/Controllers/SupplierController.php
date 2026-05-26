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

    public function data()
    {
        $this->authorize('view suppliers');

        $suppliers = Supplier::with('createdBy')->latest()->get();
        $canEdit   = auth()->user()->can('edit suppliers');
        $canDelete = auth()->user()->can('delete suppliers');

        $data = $suppliers->map(function ($supplier, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input supplier-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.suppliers.toggle-status', $supplier) . '" ' . ($supplier->status === 'active' ? 'checked' : '') . ' /></div>'
                : status_badge($supplier->status);

            $actions = '';
            if ($canEdit) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-info me-1" data-common-modal="' . route('admin.suppliers.edit', $supplier) . '"><i class="ti ti-pencil"></i></button>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.suppliers.destroy', $supplier) . '" data-row-id="supplier-row-' . $supplier->id . '"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'      => $index + 1,
                'name'       => $supplier->name,
                'phone'      => $supplier->phone ?? '-',
                'address'    => $supplier->address ?? '-',
                'status'     => $status,
                'created_by' => $supplier->createdBy->name ?? '-',
                'created_at' => format_date($supplier->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create suppliers');
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create suppliers');

        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string', 'max:100'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
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
            'status'     => $request->has('status') ? 'active' : 'inactive',
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
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('edit suppliers');

        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string', 'max:100'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
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
            'status'  => $request->has('status') ? 'active' : 'inactive',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier updated successfully.',
        ]);
    }

    public function toggleStatus(Supplier $supplier)
    {
        $this->authorize('edit suppliers');

        $supplier->update([
            'status' => $supplier->status === 'active' ? 'inactive' : 'active',
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
