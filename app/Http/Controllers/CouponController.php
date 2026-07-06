<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    public function index()
    {
        $this->authorize('view coupons');
        return view('coupons.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view coupons');

        $query = Coupon::with('createdBy')->withCount('orders')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        $coupons = $query->get();
        $canEdit   = auth()->user()->can('edit coupons');
        $canDelete = auth()->user()->can('delete coupons');

        $data = $coupons->map(function ($coupon, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input coupon-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.coupons.toggle-status', $coupon) . '" ' . ($coupon->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($coupon->status);

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.coupons.edit', $coupon) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete && !$coupon->is_protected) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.coupons.destroy', $coupon) . '" data-row-id="coupon-row-' . $coupon->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            $discount = $coupon->discount_type === 'percentage' 
                ? number_format($coupon->discount_value, 0) . '%' 
                : format_price($coupon->discount_value);

            $validity = '-';
            if ($coupon->start_date && $coupon->end_date) {
                $validity = $coupon->start_date->format('d M Y') . ' to ' . $coupon->end_date->format('d M Y');
            } elseif ($coupon->start_date) {
                $validity = 'From ' . $coupon->start_date->format('d M Y');
            } elseif ($coupon->end_date) {
                $validity = 'Until ' . $coupon->end_date->format('d M Y');
            }

            $name = htmlspecialchars($coupon->name);
            if ($coupon->is_protected) {
                $name .= ' <span class="badge bg-label-info">System</span>';
            }

            return [
                'index'          => $index + 1,
                'name'           => $name,
                'code'           => '<code class="fw-bold text-primary">' . htmlspecialchars($coupon->code) . '</code>',
                'discount'       => $discount,
                'usage_limit'    => $coupon->usage_limit ? $coupon->usage_limit : 'Unlimited',
                'usage_count'    => $coupon->orders_count,
                'validity'       => $validity,
                'status'         => $status,
                'created_at'     => format_date($coupon->created_at),
                'actions'        => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create coupons');
        return view('coupons.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create coupons');

        $validator = Validator::make($request->all(), [
            'name'           => ['required', 'string', 'max:100'],
            'code'           => ['required', 'string', 'max:50', 'alpha_dash', 'unique:coupons,code'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'discount_type'  => ['required', 'string', 'in:flat,percentage'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'usage_limit'    => ['nullable', 'integer', 'min:1'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Coupon::create([
            'name'           => $request->name,
            'code'           => strtoupper($request->code),
            'description'    => $request->description,
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_value,
            'usage_limit'    => $request->usage_limit,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'status'         => $request->has('status') ? 1 : 2,
            'created_by'     => auth()->id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Coupon created successfully.',
        ]);
    }

    public function edit(Coupon $coupon)
    {
        $this->authorize('edit coupons');
        return view('coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $this->authorize('edit coupons');

        $validator = Validator::make($request->all(), [
            'name'           => ['required', 'string', 'max:100'],
            'code'           => ['required', 'string', 'max:50', 'alpha_dash', 'unique:coupons,code,' . $coupon->id],
            'description'    => ['nullable', 'string', 'max:5000'],
            'discount_type'  => ['required', 'string', 'in:flat,percentage'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'usage_limit'    => ['nullable', 'integer', 'min:1'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $coupon->update([
            'name'           => $request->name,
            'code'           => strtoupper($request->code),
            'description'    => $request->description,
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_value,
            'usage_limit'    => $request->usage_limit,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'status'         => $request->has('status') ? 1 : 2,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Coupon updated successfully.',
        ]);
    }

    public function toggleStatus(Coupon $coupon)
    {
        $this->authorize('edit coupons');

        $coupon->update([
            'status' => $coupon->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Coupon status updated successfully.',
        ]);
    }

    public function destroy(Coupon $coupon)
    {
        $this->authorize('delete coupons');

        if ($coupon->is_protected) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This coupon is protected by the system and cannot be deleted.',
            ], 422);
        }

        $coupon->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Coupon deleted successfully.',
        ]);
    }
}
