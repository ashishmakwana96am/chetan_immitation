<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $this->authorize('view customers');
        return view('customers.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view customers');

        $query = Customer::orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $customers = $query->get();
        $canEdit   = auth()->user()->can('edit customers');
        $canDelete = auth()->user()->can('delete customers');

        $data = $customers->map(function ($customer, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input customer-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.customers.toggle-status', $customer) . '" ' . ($customer->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($customer->status);

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.customers.edit', $customer) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.customers.destroy', $customer) . '" data-row-id="customer-row-' . $customer->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            return [
                'id'         => $customer->id,
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
            'phones'   => ['nullable', 'array'],
            'phones.*' => ['nullable', 'digits:10'],
            'email'    => ['nullable', 'email', Rule::unique('customers', 'email')->whereNull('deleted_at')],
        ]);

        $this->validateUniquePhones($validator, $request);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'status'   => $request->has('status') ? 1 : 2,
        ]);

        $phones = array_filter($request->input('phones', []));
        foreach ($phones as $phone) {
            CustomerPhone::create([
                'customer_id' => $customer->id,
                'phone'       => $phone,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer created successfully.',
            'data'    => $customer,
        ]);
    }

    public function edit(Customer $customer)
    {
        $this->authorize('edit customers');
        $customer->load('phones');
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize('edit customers');

        $validator = Validator::make($request->all(), [
            'name'      => ['required', 'string', 'max:100'],
            'phones'    => ['nullable', 'array'],
            'phones.*'  => ['nullable', 'digits:10'],
            'phone_ids' => ['nullable', 'array'],
            'email'     => ['nullable', 'email', Rule::unique('customers', 'email')->ignore($customer->id)->whereNull('deleted_at')],
        ]);

        $this->validateUniquePhones($validator, $request);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $customer->update([
            'name'   => $request->name,
            'email'  => $request->email,
            'status' => $request->has('status') ? 1 : 2,
        ]);

        $submittedPhones = $request->input('phones', []);
        $submittedIds    = $request->input('phone_ids', []);
        $keptIds         = [];

        foreach ($submittedPhones as $index => $phone) {
            $phone = trim((string) $phone);
            if ($phone === '') {
                continue;
            }

            $id = $submittedIds[$index] ?? null;
            $existing = $id ? $customer->phones()->find($id) : null;

            if ($existing) {
                $existing->update(['phone' => $phone]);
                $keptIds[] = $existing->id;
            } else {
                $created = $customer->phones()->create(['phone' => $phone]);
                $keptIds[] = $created->id;
            }
        }

        $customer->phones()->whereNotIn('id', $keptIds)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer updated successfully.',
        ]);
    }

    private function validateUniquePhones($validator, Request $request): void
    {
        $validator->after(function ($validator) use ($request) {
            $allPhones = array_filter($request->input('phones', []));

            if (count($allPhones) !== count(array_unique($allPhones))) {
                $validator->errors()->add('phones', 'The same phone number cannot be added more than once.');
            }
        });
    }

    public function toggleStatus(Customer $customer)
    {
        $this->authorize('edit customers');

        $customer->update([
            'status' => $customer->status == 1 ? 2 : 1,
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
