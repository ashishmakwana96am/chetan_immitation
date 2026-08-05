<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    private function restrictedLocationId(): ?int
    {
        $user = auth()->user();
        return ($user->location_id && !$user->hasRole('super-admin')) ? (int) $user->location_id : null;
    }

    public function index()
    {
        $this->authorize('view customers');
        $isSuperAdmin = auth()->user()->hasRole('super-admin');
        return view('customers.index', compact('isSuperAdmin'));
    }

    public function data(Request $request)
    {
        $this->authorize('view customers');

        $locationId = $this->restrictedLocationId();

        $query = Customer::with('location')->orderBy('id', 'desc');

        if ($locationId) {
            $query->where('location_id', $locationId);
        }
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

            $creditCustomer = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input customer-credit-toggle" type="checkbox" role="switch" data-url="' . route('admin.customers.toggle-credit-customer', $customer) . '" ' . ($customer->is_credit_customer ? 'checked' : '') . ' /></div>'
                : ($customer->is_credit_customer ? '<span class="badge bg-label-info">Yes</span>' : '<span class="badge bg-label-secondary">No</span>');

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
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
                'id'               => $customer->id,
                'index'            => $index + 1,
                'name'             => $customer->name,
                'is_credit_customer' => (bool) $customer->is_credit_customer,
                'phone'            => $customer->phone ?? '-',
                'email'            => $customer->email ?? '-',
                'branch'           => $customer->location->name ?? '-',
                'gst_no'           => $customer->gst_no ? '<code>' . e($customer->gst_no) . '</code>' : '-',
                'state'            => $customer->state ?: '-',
                'gst_no_raw'       => $customer->gst_no ?? '',
                'state_raw'        => $customer->state ?? '',
                'status'           => $status,
                'credit_customer'  => $creditCustomer,
                'created_at'       => format_date($customer->created_at),
                'actions'          => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create customers');
        $isSuperAdmin = auth()->user()->hasRole('super-admin');
        $locations = $isSuperAdmin ? Location::where('status', 1)->orderBy('name')->get() : collect();
        return view('customers.create', compact('isSuperAdmin', 'locations'));
    }

    public function store(Request $request)
    {
        $this->authorize('create customers');

        $restrictedLocationId = $this->restrictedLocationId();

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100'],
            'phones'      => ['nullable', 'array'],
            'phones.*'    => ['nullable', 'digits:10'],
            'email'       => ['nullable', 'email', Rule::unique('customers', 'email')->whereNull('deleted_at')],
            'gst_no'      => ['nullable', 'string', 'max:15'],
            'state'       => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string'],
            'location_id' => [$restrictedLocationId ? 'nullable' : 'required', 'exists:locations,id'],
        ], [], [
            'location_id' => 'branch',
        ]);

        $this->validateUniquePhones($validator, $request);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::create([
            'location_id' => $restrictedLocationId ?? $request->location_id,
            'name'     => $request->name,
            'email'    => $request->email,
            'gst_no'   => $request->gst_no ? strtoupper(trim($request->gst_no)) : null,
            'state'    => $request->state ? trim($request->state) : null,
            'address'  => $request->address ? trim($request->address) : null,
            'status'   => $request->has('status') ? 1 : 2,
            'is_credit_customer' => $request->has('is_credit_customer'),
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

        $restrictedLocationId = $this->restrictedLocationId();
        if ($restrictedLocationId && $customer->location_id != $restrictedLocationId) {
            abort(403);
        }

        $customer->load('phones');
        $isSuperAdmin = auth()->user()->hasRole('super-admin');
        $locations = $isSuperAdmin ? Location::where('status', 1)->orderBy('name')->get() : collect();
        return view('customers.edit', compact('customer', 'isSuperAdmin', 'locations'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize('edit customers');

        $restrictedLocationId = $this->restrictedLocationId();
        if ($restrictedLocationId && $customer->location_id != $restrictedLocationId) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100'],
            'phones'      => ['nullable', 'array'],
            'phones.*'    => ['nullable', 'digits:10'],
            'phone_ids'   => ['nullable', 'array'],
            'email'       => ['nullable', 'email', Rule::unique('customers', 'email')->ignore($customer->id)->whereNull('deleted_at')],
            'gst_no'      => ['nullable', 'string', 'max:15'],
            'state'       => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string'],
            'location_id' => [$restrictedLocationId ? 'nullable' : 'required', 'exists:locations,id'],
        ], [], [
            'location_id' => 'branch',
        ]);

        $this->validateUniquePhones($validator, $request);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $isCreditCustomer = $request->has('is_credit_customer');

        $custBal = $customer->customerBalance ? (float) $customer->customerBalance->balance : 0.00;

        if ($customer->is_credit_customer && !$isCreditCustomer && $custBal != 0) {
            return response()->json([
                'status'  => 'error',
                'message' => [
                    'is_credit_customer' => ['Cannot remove credit customer status. This customer has a balance of ' . format_price($custBal) . '. Please clear the balance first.'],
                ],
            ], 422);
        }

        $customer->update([
            'location_id' => $restrictedLocationId ?? $request->location_id,
            'name'     => $request->name,
            'email'    => $request->email,
            'gst_no'   => $request->gst_no ? strtoupper(trim($request->gst_no)) : null,
            'state'    => $request->state ? trim($request->state) : null,
            'address'  => $request->address ? trim($request->address) : null,
            'status'   => $request->has('status') ? 1 : 2,
            'is_credit_customer' => $isCreditCustomer,
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

        $restrictedLocationId = $this->restrictedLocationId();
        if ($restrictedLocationId && $customer->location_id != $restrictedLocationId) {
            abort(403);
        }

        $customer->update([
            'status' => $customer->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer status updated successfully.',
        ]);
    }

    public function toggleCreditCustomer(Customer $customer)
    {
        $this->authorize('edit customers');

        $restrictedLocationId = $this->restrictedLocationId();
        if ($restrictedLocationId && $customer->location_id != $restrictedLocationId) {
            abort(403);
        }

        $custBal = $customer->customerBalance ? (float) $customer->customerBalance->balance : 0.00;

        if ($customer->is_credit_customer && $custBal != 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot remove credit customer status. This customer has a balance of ' . format_price($custBal) . '. Please clear the balance first.',
            ], 422);
        }

        $customer->update([
            'is_credit_customer' => !$customer->is_credit_customer,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer credit status updated successfully.',
        ]);
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete customers');

        $restrictedLocationId = $this->restrictedLocationId();
        if ($restrictedLocationId && $customer->location_id != $restrictedLocationId) {
            abort(403);
        }

        if ((float) $customer->balance != 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete this customer. They have a balance of ' . format_price($customer->balance) . '. Please clear the balance first, then delete this customer.',
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer deleted successfully.',
        ]);
    }
}
