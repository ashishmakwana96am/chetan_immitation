<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function index()
    {
        $this->authorize('view locations');
        $locations = Location::with('createdBy')->orderBy('id', 'desc')->get();
        return view('locations.index', compact('locations'));
    }

    public function data(Request $request)
    {
        $this->authorize('view locations');

        $query = Location::with('createdBy')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('is_default')) {
            $query->where('is_default', (bool) $request->is_default);
        }

        $locations = $query->get();

        $canEdit          = auth()->user()->can('edit locations');
        $canDelete        = auth()->user()->can('delete locations');
        $canManageBalance = auth()->user()->hasRole('super-admin') && auth()->user()->can('manage branch balances');

        $data = $locations->map(function ($location, $index) use ($canEdit, $canDelete, $canManageBalance) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0">
                    <input class="form-check-input location-status-toggle" type="checkbox" role="switch"
                        data-url="' . route('admin.locations.toggle-status', $location) . '"
                        ' . ($location->status == 1 ? 'checked' : '') . ' />
                   </div>'
                : '<span class="badge ' . ($location->status == 1 ? 'bg-label-success' : 'bg-label-danger') . '">' . ($location->status == 1 ? 'Active' : 'Inactive') . '</span>';

            $actions = '';
            if ($canEdit || $canDelete || $canManageBalance) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canManageBalance) {
                    $actions .= '<a class="dropdown-item" href="' . route('admin.locations.balance.show', $location) . '"><i class="ti ti-cash me-2"></i>Manage Balance</a>';
                }
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.locations.edit', $location) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete && !$location->is_default) {
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.locations.destroy', $location) . '" data-row-id="location-row-' . $location->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            $nameDisplay = $location->name . ($location->is_default ? ' <span class="badge bg-label-success ms-1 fs-tiny" style="font-size: 0.65rem;">Default</span>' : '');

            return [
                'index'        => $index + 1,
                'name'         => $nameDisplay,
                'address'      => $location->address ?? '-',
                'phone'        => $location->phone ?? '-',
                'gst_number'   => $location->gst_number ?? '-',
                'cash_balance' => $location->cash_balance < 0 ? '<span class="text-danger">' . format_price($location->cash_balance) . '</span>' : format_price($location->cash_balance),
                'bank_balance' => $location->bank_balance < 0 ? '<span class="text-danger">' . format_price($location->bank_balance) . '</span>' : format_price($location->bank_balance),
                'status'       => $status,
                'actions'      => $actions,
            ];
        });

        $totalCash = $locations->sum('cash_balance');
        $totalBank = $locations->sum('bank_balance');

        return response()->json([
            'status'  => 'success',
            'data'    => $data,
            'summary' => [
                'total_cash' => format_price($totalCash),
                'total_bank' => format_price($totalBank),
            ]
        ]);
    }

    public function create()
    {
        $this->authorize('create locations');
        return view('locations.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create locations');

        $validator = Validator::make($request->all(), [
            'name'       => ['required', 'string', 'max:100'],
            'address'    => ['nullable', 'string', 'max:255'],
            'phone'      => ['required', 'string', 'max:20'],
            'gst_number' => ['required', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/i'],
        ]);

        // $validator->after(function ($validator) use ($request) {
        //     if ($request->boolean('is_default')) {
        //         $exists = Location::where('is_default', true)->exists();
        //         if ($exists) {
        //             $validator->errors()->add('is_default', 'A default location is already set. Please unset the current default location first.');
        //         }
        //     }
        // });

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Location::create([
            'name'       => $request->name,
            'slug'       => generate_slug(Location::class, $request->name),
            'address'    => $request->address,
            'phone'      => $request->phone,
            'gst_number' => $request->gst_number ? strtoupper($request->gst_number) : null,
            // 'is_default' => $request->boolean('is_default'),
            'status'     => $request->has('status') ? 1 : 2,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Location created successfully.',
        ]);
    }

    public function edit(Location $location)
    {
        $this->authorize('edit locations');
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $this->authorize('edit locations');

        $validator = Validator::make($request->all(), [
            'name'       => ['required', 'string', 'max:100'],
            'address'    => ['nullable', 'string', 'max:255'],
            'phone'      => ['required', 'string', 'max:20'],
            'gst_number' => ['required', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/i'],
        ]);

        // $validator->after(function ($validator) use ($request, $location) {
        //     if (!$request->boolean('is_default') && $location->is_default) {
        //         $otherDefault = Location::where('is_default', true)
        //             ->where('id', '!=', $location->id)
        //             ->exists();
        //         if (!$otherDefault) {
        //             $validator->errors()->add('is_default', 'At least one location must be set as default. Please set another location as default first.');
        //         }
        //     }
        // });

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        // if ($request->boolean('is_default')) {
        //     Location::where('is_default', true)
        //         ->where('id', '!=', $location->id)
        //         ->update(['is_default' => false]);
        // }

        $location->update([
            'name'       => $request->name,
            'slug'       => generate_slug(Location::class, $request->name, $location->id),
            'address'    => $request->address,
            'phone'      => $request->phone,
            'gst_number' => $request->gst_number ? strtoupper($request->gst_number) : null,
            // 'is_default' => $request->boolean('is_default'),
            'status'     => $request->has('status') ? 1 : 2,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Location updated successfully.',
        ]);
    }

    public function toggleStatus(Location $location)
    {
        $this->authorize('edit locations');

        $location->update([
            'status' => $location->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Location status updated successfully.',
        ]);
    }

    public function destroy(Location $location)
    {
        $this->authorize('delete locations');

        if ($location->is_default) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete the default location.',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Location deleted successfully.',
        ]);
    }
}
