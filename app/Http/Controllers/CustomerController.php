<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index()
    {
        $this->authorize('view customers');
        return view('customers.index');
    }

    public function data()
    {
        $this->authorize('view customers');

        $customers = Customer::latest()->get();
        $canEdit   = auth()->user()->can('edit customers');
        $canDelete = auth()->user()->can('delete customers');

        $data = $customers->map(function ($customer, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input customer-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.customers.toggle-status', $customer) . '" ' . ($customer->status === 'active' ? 'checked' : '') . ' /></div>'
                : status_badge($customer->status);

            $actions = '';
            if ($canEdit) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-info me-1" data-common-modal="' . route('admin.customers.edit', $customer) . '"><i class="ti ti-pencil"></i></button>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.customers.destroy', $customer) . '" data-row-id="customer-row-' . $customer->id . '"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'      => $index + 1,
                'name'       => $customer->name,
                'phone'      => $customer->phone ?? '-',
                'email'      => $customer->email ?? '-',
                'status'     => $status,
                'created_at' => format_date($customer->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create customers');
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create customers');

        $validator = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:100'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'email'    => ['nullable', 'email', 'unique:customers,email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Customer::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'status'   => $request->has('status') ? 'active' : 'inactive',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer created successfully.',
        ]);
    }

    public function edit(Customer $customer)
    {
        $this->authorize('edit customers');
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize('edit customers');

        $validator = Validator::make($request->all(), [
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'unique:customers,email,' . $customer->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $customer->update([
            'name'   => $request->name,
            'phone'  => $request->phone,
            'email'  => $request->email,
            'status' => $request->has('status') ? 'active' : 'inactive',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer updated successfully.',
        ]);
    }

    public function toggleStatus(Customer $customer)
    {
        $this->authorize('edit customers');

        $customer->update([
            'status' => $customer->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer status updated successfully.',
        ]);
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete customers');

        $customer->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer deleted successfully.',
        ]);
    }
}
