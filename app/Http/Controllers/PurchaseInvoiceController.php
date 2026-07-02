<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseAllocation;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        $this->authorize('view purchases');
        $suppliers = Supplier::where('status', 1)->orderBy('name')->get();
        return view('purchases.index', compact('suppliers'));
    }

    public function data(Request $request)
    {
        $this->authorize('view purchases');

        $user = auth()->user();
        $invoices = PurchaseInvoice::with(['supplier', 'createdBy'])
            ->when($user->location_id && !$user->hasRole('super-admin'), function($q) use ($user) {
                $q->whereHas('items.allocations', function($sub) use ($user) {
                    $sub->where('location_id', $user->location_id);
                });
            })
            ->when($request->supplier_id, function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            })
            ->when($request->status, function($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->payment_status, function($q) use ($request) {
                $q->where('payment_status', $request->payment_status);
            })
            ->when($request->product_id, function($q) use ($request) {
                $q->whereHas('items', function($sub) use ($request) {
                    $sub->where('product_id', $request->product_id);
                });
            })
            ->when($request->start_date, function($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->start_date);
            })
            ->when($request->end_date, function($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->end_date);
            })
            ->orderBy('id', 'desc')
            ->get();
        $canEdit                       = auth()->user()->can('edit purchases');
        $canDelete                     = auth()->user()->can('delete purchases');
        $canEditPurchasesStatus        = auth()->user()->can('edit purchases status');
        $canEditPurchasesPaymentStatus = auth()->user()->can('edit purchases payment status');
        $canDownloadPurchases          = auth()->user()->can('download purchases');

        $data = $invoices->map(function ($invoice, $index) use ($canEdit, $canDelete, $canEditPurchasesStatus, $canEditPurchasesPaymentStatus, $canDownloadPurchases) {
            $statusColors = [
                1 => 'bg-label-secondary',
                2 => 'bg-label-success',
                3 => 'bg-label-danger',
            ];
            $statusLabels = [
                1 => 'Pending',
                2 => 'Approve',
                3 => 'Decline',
            ];
            $statusBadge = '<span class="badge ' . ($statusColors[$invoice->status] ?? 'bg-label-secondary') . '">' . ($statusLabels[$invoice->status] ?? ucfirst($invoice->status)) . '</span>';

            $paymentColors = [
                1 => 'bg-label-warning',
                2 => 'bg-label-info',
            ];
            $paymentLabels = [
                1 => 'Pending',
                2 => 'Paid',
            ];
            $paymentStatusBadge = '<span class="badge ' . ($paymentColors[$invoice->payment_status] ?? 'bg-label-secondary') . '">' . ($paymentLabels[$invoice->payment_status] ?? 'Pending') . '</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.purchases.show', $invoice) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            // if ($canDownloadPurchases) {
            //     $actions .= '<a href="' . route('admin.purchases.pdf', $invoice) . '" class="dropdown-item" target="_blank"><i class="ti ti-file-text me-2"></i>PDF</a>';
            // }
            if ($canEdit && $invoice->status == 1) {
                $actions .= '<a href="' . route('admin.purchases.edit', $invoice) . '" class="dropdown-item"><i class="ti ti-pencil me-2"></i>Edit</a>';
            }
            if ($canEditPurchasesStatus && $invoice->status == 1) {
                $actions .= '<button class="dropdown-item change-purchase-status-btn" data-url="' . route('admin.purchases.status', $invoice) . '" data-current="' . $invoice->status . '"><i class="ti ti-adjustments-horizontal me-2"></i>Update Status</button>';
            }
            if ($canEditPurchasesPaymentStatus && ($invoice->status == 1 || ($invoice->status == 2 && $invoice->payment_status == 1))) {
                $actions .= '<button class="dropdown-item change-purchase-payment-status-btn" data-url="' . route('admin.purchases.update-payment-status', $invoice) . '" data-current="' . ($invoice->payment_status ?? 1) . '"><i class="ti ti-credit-card me-2"></i>Update Payment Status</button>';
            }
            if ($canDelete && $invoice->status == 1) {
                $actions .= '<div class="dropdown-divider"></div>';
                $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.purchases.destroy', $invoice) . '" data-row-id="purchase-row-' . $invoice->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
            }
            $actions .= '</div></div>';

            return [
                'index'          => $index + 1,
                'invoice_no'     => '<code>' . $invoice->invoice_no . '</code>',
                'supplier'       => $invoice->supplier->name ?? '-',
                'total_amount'   => format_price($invoice->total_amount),
                'status'         => $statusBadge,
                'payment_status' => $paymentStatusBadge,
                'date_group'     => $invoice->created_at->format('d M Y'),
                'date_sort'      => $invoice->created_at->format('Ymd'),
                'actions'        => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function show(PurchaseInvoice $purchase)
    {
        $this->authorize('view purchases');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $hasAllocation = $purchase->items()->whereHas('allocations', function($q) use ($user) {
                $q->where('location_id', $user->location_id);
            })->exists();
            if (!$hasAllocation) {
                abort(403);
            }
        }

        $purchase->load(['supplier', 'createdBy', 'items.product.variants.attributeValue.attribute', 'items.product.primaryImage', 'items.allocations.location']);
        return view('purchases.show', compact('purchase'));
    }

    public function create()
    {
        $this->authorize('create purchases');
        $suppliers = Supplier::where('status', 1)->orderBy('name')->get();
        $products  = Product::with(['variants.attributeValue.attribute', 'primaryImage'])->where('status', 1)->orderBy('name')->get();
        $user      = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locations = Location::where('id', $user->location_id)->where('status', 1)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }
        $invoiceNo = generate_invoice_no('PUR', PurchaseInvoice::class);
        return view('purchases.create', compact('suppliers', 'products', 'locations', 'invoiceNo'));
    }

    public function store(Request $request)
    {
        $this->authorize('create purchases');

        $validator = Validator::make($request->all(), [
            'supplier_id'            => ['required', 'exists:suppliers,id'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'status'                 => ['nullable', 'integer', 'in:1,2,3'],
            'payment_status'         => ['nullable', 'integer', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        // Validate allocation totals match item quantities
        foreach ($request->items as $index => $item) {
            $allocatedQty = collect($item['allocations'] ?? [])->sum(fn($a) => (int) $a['quantity']);
            if ($allocatedQty !== (int) $item['quantity']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['items' => ['Item #' . ($index + 1) . ': Allocated (' . $allocatedQty . ') must equal item quantity (' . $item['quantity'] . ')']],
                ], 422);
            }
        }

        DB::transaction(function () use ($request) {
            $totalAmount = collect($request->items)->sum(fn($item) => $item['purchase_price'] * $item['quantity']);

            $invoice = PurchaseInvoice::create([
                'supplier_id'    => $request->supplier_id,
                'invoice_no'     => generate_invoice_no('PUR', PurchaseInvoice::class),
                'total_amount'   => $totalAmount,
                'status'         => $request->status ?? 2,
                'payment_status' => $request->payment_status ?? 1,
                'created_by'     => auth()->id(),
            ]);

            foreach ($request->items as $itemData) {
                $item = PurchaseItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id'          => $itemData['product_id'],
                    'product_variant_id'  => $itemData['product_variant_id'] ?? null,
                    'purchase_price'      => $itemData['purchase_price'],
                    'quantity'            => $itemData['quantity'],
                    'total'               => $itemData['purchase_price'] * $itemData['quantity'],
                ]);

                // Only save allocations with qty > 0
                foreach ($itemData['allocations'] ?? [] as $allocationData) {
                    if ((int) $allocationData['quantity'] <= 0) continue;
                    PurchaseAllocation::create([
                        'purchase_item_id' => $item->id,
                        'location_id'      => $allocationData['location_id'],
                        'quantity'         => $allocationData['quantity'],
                    ]);
                }
            }

            if ($invoice->status == 2) {
                $this->approveInvoice($invoice);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase invoice created successfully.',
        ]);
    }

    public function edit(PurchaseInvoice $purchase)
    {
        $this->authorize('edit purchases');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $hasAllocation = $purchase->items()->whereHas('allocations', function($q) use ($user) {
                $q->where('location_id', $user->location_id);
            })->exists();
            if (!$hasAllocation) {
                abort(403);
            }
        }

        if ($purchase->status !== 1) {
            return redirect()->route('admin.purchases.show', $purchase)
                ->with('error', 'Only pending purchases can be edited.');
        }

        $suppliers = Supplier::where('status', 1)->orderBy('name')->get();
        $products  = Product::with(['variants.attributeValue.attribute', 'primaryImage'])->where('status', 1)->orderBy('name')->get();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locations = Location::where('id', $user->location_id)->where('status', 1)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }
        $purchase->load(['items.product', 'items.allocations']);

        $existingItems = $purchase->items->map(function ($item) {
            return [
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'purchase_price'     => $item->purchase_price,
                'quantity'           => $item->quantity,
                'allocations'        => $item->allocations->map(function ($a) {
                    return [
                        'location_id' => $a->location_id,
                        'quantity'    => $a->quantity,
                    ];
                })->values(),
            ];
        })->values();

        return view('purchases.edit', compact('purchase', 'suppliers', 'products', 'locations', 'existingItems'));
    }

    public function update(Request $request, PurchaseInvoice $purchase)
    {
        $this->authorize('edit purchases');

        if ($purchase->status != 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only pending invoices can be edited.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id'            => ['required', 'exists:suppliers,id'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'status'                 => ['nullable', 'integer', 'in:1,2,3'],
            'payment_status'         => ['nullable', 'integer', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        foreach ($request->items as $index => $item) {
            $allocatedQty = collect($item['allocations'] ?? [])->sum(fn($a) => (int) $a['quantity']);
            if ($allocatedQty !== (int) $item['quantity']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['items' => ['Item #' . ($index + 1) . ': Allocated (' . $allocatedQty . ') must equal item quantity (' . $item['quantity'] . ')']],
                ], 422);
            }
        }

        DB::transaction(function () use ($request, $purchase) {
            $totalAmount = collect($request->items)->sum(fn($item) => $item['purchase_price'] * $item['quantity']);

            $oldStatus = $purchase->status;
            $newStatus = $request->status ?? 2;
  
            $purchase->update([
                'supplier_id'    => $request->supplier_id,
                'total_amount'   => $totalAmount,
                'status'         => $newStatus,
                'payment_status' => $request->payment_status ?? 1,
            ]);

            $purchase->items()->delete();

            foreach ($request->items as $itemData) {
                $item = PurchaseItem::create([
                    'purchase_invoice_id' => $purchase->id,
                    'product_id'          => $itemData['product_id'],
                    'product_variant_id'  => $itemData['product_variant_id'] ?? null,
                    'purchase_price'      => $itemData['purchase_price'],
                    'quantity'            => $itemData['quantity'],
                    'total'               => $itemData['purchase_price'] * $itemData['quantity'],
                ]);

                foreach ($itemData['allocations'] ?? [] as $allocationData) {
                    if ((int) $allocationData['quantity'] <= 0) continue;
                    PurchaseAllocation::create([
                        'purchase_item_id' => $item->id,
                        'location_id'      => $allocationData['location_id'],
                        'quantity'         => $allocationData['quantity'],
                    ]);
                }
            }

            if ($newStatus == 2 && $oldStatus != 2) {
                $this->approveInvoice($purchase);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase invoice updated successfully.',
        ]);
    }

    public function updateStatus(Request $request, PurchaseInvoice $purchase)
    {
        $this->authorize('edit purchases status');

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:1,2,3'],
        ]);
 
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }
 
        $newStatus = $request->status;
 
        if ($purchase->status == $newStatus) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invoice is already updated.',
            ], 422);
        }
 
        if ($purchase->status != 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only pending invoices can be updated.',
            ], 422);
        }
 
        DB::transaction(function () use ($purchase, $newStatus) {
            $purchase->update(['status' => $newStatus]);
 
            if ($newStatus == 2) {
                $this->approveInvoice($purchase);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Invoice status updated to ' . $newStatus . '.',
        ]);
    }

    public function destroy(PurchaseInvoice $purchase)
    {
        $this->authorize('delete purchases');

        if ($purchase->status != 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only pending invoices can be deleted.',
            ], 422);
        }

        $purchase->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase invoice deleted successfully.',
        ]);
    }

    public function pdf(PurchaseInvoice $purchase)
    {
        $this->authorize('view purchases');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $hasAllocation = $purchase->items()->whereHas('allocations', function($q) use ($user) {
                $q->where('location_id', $user->location_id);
            })->exists();
            if (!$hasAllocation) {
                abort(403);
            }
        }

        $purchase->load(['supplier', 'createdBy', 'items.product.variants.attributeValue.attribute', 'items.allocations.location']);

        $pdf = Pdf::loadView('purchases.pdf', compact('purchase'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('purchase-' . $purchase->invoice_no . '.pdf');
    }

    public function getProductPrice(Product $product)
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'purchase_price' => $product->purchase_price,
                'name'           => $product->name,
                'sku'            => $product->sku,
            ],
        ]);
    }

    private function approveInvoice(PurchaseInvoice $purchase)
    {
        $purchase->load('items.allocations');
        foreach ($purchase->items as $item) {
            foreach ($item->allocations as $allocation) {
                Inventory::updateOrCreate(
                    [
                        'product_id'  => $item->product_id,
                        'location_id' => $allocation->location_id,
                    ],
                    ['created_by' => auth()->id()]
                );
                Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $allocation->location_id)
                    ->increment('quantity', $allocation->quantity);
            }
        }
    }

    public function updatePaymentStatus(Request $request, PurchaseInvoice $purchase)
    {
        $this->authorize('edit purchases payment status');

        $validator = Validator::make($request->all(), [
            'payment_status' => ['required', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $purchase->update([
            'payment_status' => $request->payment_status,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier payment status updated successfully.',
        ]);
    }
}
