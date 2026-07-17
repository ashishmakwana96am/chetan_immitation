<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseAllocation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
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
        $invoices = Purchase::with(['supplier', 'createdBy'])
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
        $canDelete                     = auth()->user()->hasRole('super-admin');
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
                3 => 'bg-label-primary',
            ];
            $paymentLabels = [
                1 => 'Pending',
                2 => 'Paid',
                3 => 'Partially Paid',
            ];
            $paymentStatusBadge = '<span class="badge ' . ($paymentColors[$invoice->payment_status] ?? 'bg-label-secondary') . '">' . ($paymentLabels[$invoice->payment_status] ?? 'Pending') . '</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.purchases.show', $invoice) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            // if ($canDownloadPurchases) {
            //     $actions .= '<a href="' . route('admin.purchases.pdf', $invoice) . '" class="dropdown-item" target="_blank"><i class="ti ti-file-text me-2"></i>PDF</a>';
            // }
            if ($canEdit) {
                $actions .= '<a href="' . route('admin.purchases.edit', $invoice) . '" class="dropdown-item"><i class="ti ti-pencil me-2"></i>Edit</a>';
            }
            if ($canEditPurchasesStatus && $invoice->status == 1) {
                $actions .= '<button class="dropdown-item change-purchase-status-btn" data-url="' . route('admin.purchases.status', $invoice) . '" data-current="' . $invoice->status . '"><i class="ti ti-adjustments-horizontal me-2"></i>Update Status</button>';
            }
            if ($canEditPurchasesPaymentStatus && ($invoice->status == 1 || ($invoice->status == 2 && $invoice->payment_status != 2))) {
                $actions .= '<button class="dropdown-item change-purchase-payment-status-btn" data-url="' . route('admin.purchases.update-payment-status', $invoice) . '" data-history-url="' . route('admin.purchases.payment-history', $invoice) . '" data-current="' . ($invoice->payment_status ?? 1) . '"><i class="ti ti-credit-card me-2"></i>Update Payment Status</button>';
            }
            if ($canDelete) {
                $actions .= '<div class="dropdown-divider"></div>';
                $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.purchases.destroy', $invoice) . '" data-row-id="purchase-row-' . $invoice->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
            }
            $actions .= '</div></div>';

            return [
                'index'          => $index + 1,
                'invoice_no'     => '<code>' . $invoice->invoice_no . '</code>' . ($invoice->is_gst ? ' <span class="badge bg-label-success ms-1 fs-tiny" style="font-size: 0.65rem;">GST</span>' : ''),
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

    public function show(Purchase $purchase)
    {
        $this->authorize('view purchases');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locationId   = $isRestricted ? $user->location_id : null;

        if ($isRestricted) {
            $hasAllocation = $purchase->items()->whereHas('allocations', function($q) use ($user) {
                $q->where('location_id', $user->location_id);
            })->exists();
            if (!$hasAllocation) {
                abort(403);
            }
        }

        $purchase->load(['supplier', 'createdBy', 'items.product.variants.attributeValue.attribute', 'items.product.primaryImage', 'items.allocations.location', 'payments.createdBy']);
        return view('purchases.show', compact('purchase', 'locationId', 'isRestricted'));
    }

    public function create()
    {
        $this->authorize('create purchases');
        $suppliers = Supplier::where('status', 1)->orderBy('name')->get();
        $products  = Product::with(['variants.attributeValue.attribute', 'primaryImage'])->where('status', 1)->orderBy('name')->get();
        $locations = Location::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $invoiceNo = generate_invoice_no('PS', Purchase::class);
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
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.custom_size_value' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.discount_type'  => ['nullable', 'string', 'in:flat,percentage'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_type'          => ['nullable', 'string', 'in:flat,percentage'],
            'discount_value'         => ['nullable', 'numeric', 'min:0'],
            'status'                 => ['nullable', 'integer', 'in:1,2,3'],
            'payment_status'         => ['nullable', 'integer', 'in:1,2,3'],
            'payment_method'         => ['nullable', 'string', 'in:cash,online'],
            'paid_amount'            => ['nullable', 'numeric', 'min:0.01', 'required_if:payment_status,3'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $defaultLocation = $this->defaultPurchaseLocation();
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        try {
            DB::transaction(function () use ($request, $defaultLocation) {
            $itemsTotal = 0.0;
            $itemsData = [];

            foreach ($request->items as $itemData) {
                $qty = (int)$itemData['quantity'];
                $price = (float)$itemData['purchase_price'];
                $subtotal = $qty * $price;

                $discVal = (float)($itemData['discount_value'] ?? 0);
                $discType = $itemData['discount_type'] ?? 'flat';

                $discAmount = 0.0;
                if ($discType === 'flat') {
                    $discAmount = $discVal;
                } else if ($discType === 'percentage') {
                    $discAmount = $subtotal * ($discVal / 100);
                }

                if ($discAmount > $subtotal) {
                    $discAmount = $subtotal;
                }

                $itemTotal = $subtotal - $discAmount;
                $itemsTotal += $itemTotal;

                $product = Product::find($itemData['product_id']);
                $customSizeValue = $this->resolveCustomSizeValue($product, $itemData);

                $itemsData[] = [
                    'product_id'         => $itemData['product_id'],
                    'product_variant_id' => $itemData['product_variant_id'] ?? null,
                    'custom_size_value'  => $customSizeValue,
                    'purchase_price'     => $price,
                    'discount_type'      => $discType,
                    'discount_value'     => $discVal,
                    'discount_amount'    => $discAmount,
                    'quantity'           => $qty,
                    'total'              => $itemTotal,
                ];
            }

            $orderDiscVal = (float)($request->discount_value ?? 0);
            $orderDiscType = $orderDiscVal > 0 ? ($request->discount_type ?? 'flat') : null;

            $orderDiscountAmount = 0.0;
            if ($orderDiscVal > 0) {
                if ($orderDiscType === 'flat') {
                    $orderDiscountAmount = $orderDiscVal;
                } else if ($orderDiscType === 'percentage') {
                    $orderDiscountAmount = $itemsTotal * ($orderDiscVal / 100);
                }
            }

            if ($orderDiscountAmount > $itemsTotal) {
                $orderDiscountAmount = $itemsTotal;
            }

            $finalAmount = $itemsTotal - $orderDiscountAmount;

            $isGst = $request->boolean('is_gst');
            $taxAmount = 0.0;
            $invoicePrefix = 'PS';

            if ($isGst) {
                $invoicePrefix = 'GP';
                $gstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);
                $taxAmount = $finalAmount * ($gstRate / 100);
            }

            $grandTotal = round($finalAmount + $taxAmount);

            [$paymentStatus, $paidAmount] = $this->resolvePaymentStatus(
                (int) ($request->payment_status ?? Purchase::PAYMENT_STATUS_PENDING),
                $grandTotal,
                (float) ($request->paid_amount ?? 0)
            );

            $invoice = Purchase::create([
                'supplier_id'     => $request->supplier_id,
                'location_id'     => $defaultLocation->id,
                'invoice_no'      => generate_invoice_no($invoicePrefix, Purchase::class),
                'is_gst'          => $isGst,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $grandTotal,
                'discount_type'   => $orderDiscType,
                'discount_value'  => $orderDiscVal,
                'discount_amount' => $orderDiscountAmount,
                'status'          => $request->status ?? 2,
                'payment_status'  => $paymentStatus,
                'payment_method'  => $request->payment_method ?? 'cash',
                'paid_amount'     => $paidAmount,
                'created_by'      => auth()->id(),
            ]);

            if ($paidAmount > 0) {
                PurchasePayment::create([
                    'purchase_id' => $invoice->id,
                    'amount'      => $paidAmount,
                    'created_by'  => auth()->id(),
                ]);
            }

            foreach ($itemsData as $item) {
                $createdItem = PurchaseItem::create([
                    'purchase_id'        => $invoice->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'custom_size_value'  => $item['custom_size_value'],
                    'purchase_price'     => $item['purchase_price'],
                    'discount_type'      => $item['discount_type'],
                    'discount_value'     => $item['discount_value'],
                    'discount_amount'    => $item['discount_amount'],
                    'quantity'           => $item['quantity'],
                    'total'              => $item['total'],
                ]);

                PurchaseAllocation::create([
                    'purchase_item_id' => $createdItem->id,
                    'location_id'      => $defaultLocation->id,
                    'quantity'         => $item['quantity'],
                ]);
            }

            if ($invoice->status == 2) {
                $this->approveInvoice($invoice);
            }
            });
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase created successfully.',
        ]);
    }

    public function edit(Purchase $purchase)
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

        $suppliers = Supplier::where('status', 1)->orderBy('name')->get();
        $products  = Product::with(['variants.attributeValue.attribute', 'primaryImage'])->where('status', 1)->orderBy('name')->get();
        $locations = Location::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $purchase->load('items.product');

        $existingItems = $purchase->items->map(function ($item) {
            return [
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'custom_size_value'  => $item->custom_size_value,
                'purchase_price'     => $item->purchase_price,
                'discount_type'      => $item->discount_type ?? 'flat',
                'discount_value'     => $item->discount_value ?? 0,
                'quantity'           => $item->quantity,
            ];
        })->values();

        return view('purchases.edit', compact('purchase', 'suppliers', 'products', 'locations', 'existingItems'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $this->authorize('edit purchases');

        $validator = Validator::make($request->all(), [
            'supplier_id'            => ['required', 'exists:suppliers,id'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.custom_size_value' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.discount_type'  => ['nullable', 'string', 'in:flat,percentage'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_type'          => ['nullable', 'string', 'in:flat,percentage'],
            'discount_value'         => ['nullable', 'numeric', 'min:0'],
            'status'                 => ['nullable', 'integer', 'in:1,2,3'],
            'payment_status'         => ['nullable', 'integer', 'in:1,2,3'],
            'payment_method'         => ['nullable', 'string', 'in:cash,online'],
            'paid_amount'            => ['nullable', 'numeric', 'min:0', 'required_if:payment_status,3'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $defaultLocation = $this->defaultPurchaseLocation();
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        try {
            DB::transaction(function () use ($request, $purchase, $defaultLocation) {
            $itemsTotal = 0.0;
            $itemsData = [];

            foreach ($request->items as $itemData) {
                $qty = (int)$itemData['quantity'];
                $price = (float)$itemData['purchase_price'];
                $subtotal = $qty * $price;

                $discVal = (float)($itemData['discount_value'] ?? 0);
                $discType = $itemData['discount_type'] ?? 'flat';

                $discAmount = 0.0;
                if ($discType === 'flat') {
                    $discAmount = $discVal;
                } else if ($discType === 'percentage') {
                    $discAmount = $subtotal * ($discVal / 100);
                }

                if ($discAmount > $subtotal) {
                    $discAmount = $subtotal;
                }

                $itemTotal = $subtotal - $discAmount;
                $itemsTotal += $itemTotal;

                $product = Product::find($itemData['product_id']);
                $customSizeValue = $this->resolveCustomSizeValue($product, $itemData);

                $itemsData[] = [
                    'product_id'         => $itemData['product_id'],
                    'product_variant_id' => $itemData['product_variant_id'] ?? null,
                    'custom_size_value'  => $customSizeValue,
                    'purchase_price'     => $price,
                    'discount_type'      => $discType,
                    'discount_value'     => $discVal,
                    'discount_amount'    => $discAmount,
                    'quantity'           => $qty,
                    'total'              => $itemTotal,
                ];
            }

            $orderDiscVal = (float)($request->discount_value ?? 0);
            $orderDiscType = $orderDiscVal > 0 ? ($request->discount_type ?? 'flat') : null;

            $orderDiscountAmount = 0.0;
            if ($orderDiscVal > 0) {
                if ($orderDiscType === 'flat') {
                    $orderDiscountAmount = $orderDiscVal;
                } else if ($orderDiscType === 'percentage') {
                    $orderDiscountAmount = $itemsTotal * ($orderDiscVal / 100);
                }
            }

            if ($orderDiscountAmount > $itemsTotal) {
                $orderDiscountAmount = $itemsTotal;
            }

            $finalAmount = $itemsTotal - $orderDiscountAmount;

            $isGst = $request->boolean('is_gst');
            $taxAmount = 0.0;
            $invoicePrefix = 'PS';

            if ($isGst) {
                $invoicePrefix = 'GP';
                $gstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);
                $taxAmount = $finalAmount * ($gstRate / 100);
            }

            $grandTotal = round($finalAmount + $taxAmount);

            $oldStatus = $purchase->status;
            $newStatus = $request->status ?? 2;
            $oldPaidAmount = (float) $purchase->paid_amount;

            if ($oldStatus == Purchase::STATUS_APPROVE) {
                $this->reverseInvoiceStock($purchase);
            }

            [$paymentStatus, $paidAmount] = $this->resolvePaymentStatus(
                (int) ($request->payment_status ?? Purchase::PAYMENT_STATUS_PENDING),
                $grandTotal,
                (float) ($request->paid_amount ?? $oldPaidAmount),
                $oldPaidAmount
            );

            $updateData = [
                'supplier_id'     => $request->supplier_id,
                'is_gst'          => $isGst,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $grandTotal,
                'discount_type'   => $orderDiscType,
                'discount_value'  => $orderDiscVal,
                'discount_amount' => $orderDiscountAmount,
                'status'          => $newStatus,
                'payment_status'  => $paymentStatus,
                'payment_method'  => $request->payment_method ?? $purchase->payment_method ?? 'cash',
                'paid_amount'     => $paidAmount,
            ];

            if ($purchase->is_gst !== $isGst) {
                $updateData['invoice_no'] = generate_invoice_no($invoicePrefix, Purchase::class);
            }

            $purchase->update($updateData);

            $paidAmountDelta = round($paidAmount - $oldPaidAmount, 2);
            if (abs($paidAmountDelta) >= 0.01) {
                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'amount'      => $paidAmountDelta,
                    'created_by'  => auth()->id(),
                ]);
            }

            $purchase->items()->delete();

            foreach ($itemsData as $item) {
                $createdItem = PurchaseItem::create([
                    'purchase_id'        => $purchase->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'custom_size_value'  => $item['custom_size_value'],
                    'purchase_price'     => $item['purchase_price'],
                    'discount_type'      => $item['discount_type'],
                    'discount_value'     => $item['discount_value'],
                    'discount_amount'    => $item['discount_amount'],
                    'quantity'           => $item['quantity'],
                    'total'              => $item['total'],
                ]);

                PurchaseAllocation::create([
                    'purchase_item_id' => $createdItem->id,
                    'location_id'      => $defaultLocation->id,
                    'quantity'         => $item['quantity'],
                ]);
            }

            if ($newStatus == Purchase::STATUS_APPROVE) {
                $this->approveInvoice($purchase);
            }
            });
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase updated successfully.',
        ]);
    }

    public function updateStatus(Request $request, Purchase $purchase)
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
                'message' => 'Purchase is already updated.',
            ], 422);
        }
 
        if ($purchase->status != 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only pending purchase can be updated.',
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
            'message' => 'Purchase status updated to ' . $newStatus . '.',
        ]);
    }

    public function destroy(Purchase $purchase)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $purchase->update(['invoice_no' => 'DEL-' . $purchase->id . '-' . $purchase->invoice_no]);
        $purchase->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase deleted successfully.',
        ]);
    }

    public function pdf(Purchase $purchase)
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

        ActivityLogger::log('Purchase', 'export', $purchase, null, null, 'Invoice PDF exported for purchase #' . $purchase->invoice_no);

        return $pdf->download('purchase-' . $purchase->invoice_no . '.pdf');
    }

    public function getProductPrice(Product $product)
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'purchase_price' => $product->purchase_price,
                'name'           => $product->name,
            ],
        ]);
    }

    private function approveInvoice(Purchase $purchase)
    {
        \App\Services\PurchaseStockService::approve($purchase);
    }

    private function reverseInvoiceStock(Purchase $purchase): void
    {
        \App\Services\PurchaseStockService::reverse($purchase);
    }

    /**
     * @return array{0: int, 1: float} [payment_status, paid_amount]
     */
    private function resolvePaymentStatus(int $requestedStatus, float $total, float $paidAmountInput, float $fallbackPaidAmount = 0.0): array
    {
        if ($requestedStatus === Purchase::PAYMENT_STATUS_PAID) {
            return [Purchase::PAYMENT_STATUS_PAID, round($total, 2)];
        }

        if ($requestedStatus === Purchase::PAYMENT_STATUS_PARTIAL) {
            if (round($paidAmountInput, 2) > round($total, 2)) {
                throw new \RuntimeException('Paid amount cannot be greater than the total purchase amount.');
            }

            if (round($paidAmountInput, 2) >= round($total, 2)) {
                return [Purchase::PAYMENT_STATUS_PAID, round($total, 2)];
            }

            return [Purchase::PAYMENT_STATUS_PARTIAL, round($paidAmountInput, 2)];
        }

        return [Purchase::PAYMENT_STATUS_PENDING, round($fallbackPaidAmount, 2)];
    }

    private function defaultPurchaseLocation(): Location
    {
        $location = Location::where('is_default', true)->first() ?? Location::first();

        if (!$location) {
            throw new \RuntimeException('Please create a default location before creating purchases.');
        }

        return $location;
    }

    /**
     * For a custom_size-mode pair product, resolve and validate the size the
     * purchase line item picked (must match one of the product's configured
     * custom sizes). Returns null for products that aren't in that mode.
     */
    private function resolveCustomSizeValue(?Product $product, array $itemData): ?float
    {
        if (!$product || !$product->pair_product || $product->pair_mode !== 'custom_size') {
            return null;
        }

        $value = isset($itemData['custom_size_value']) ? (float) $itemData['custom_size_value'] : null;

        $validSizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn ($s) => (float) $s);

        if (!$value || !$validSizes->contains(fn ($s) => abs($s - $value) < 0.001)) {
            throw new \RuntimeException('Please select a valid size for "' . $product->name . '".');
        }

        return $value;
    }

    public function paymentHistory(Purchase $purchase)
    {
        $this->authorize('view purchases');

        $payments = $purchase->payments()->with('createdBy')->get()->map(function ($payment) {
            return [
                'amount' => format_price($payment->amount),
                'date'   => $payment->created_at->format('d M Y, h:i A'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_amount' => format_price($purchase->total_amount),
                'paid_amount'  => format_price($purchase->paid_amount),
                'balance_due'  => format_price($purchase->balance_due),
                'balance_due_raw' => (float) $purchase->balance_due,
                'payments'     => $payments,
            ],
        ]);
    }

    public function updatePaymentStatus(Request $request, Purchase $purchase)
    {
        $this->authorize('edit purchases payment status');

        $validator = Validator::make($request->all(), [
            'payment_status' => ['required', 'in:1,2,3'],
            'amount'         => ['nullable', 'numeric', 'min:0.01', 'required_if:payment_status,3'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $newStatus = (int) $request->payment_status;
        $balanceDue = round($purchase->total_amount - $purchase->paid_amount, 2);

        $statusRank = [
            Purchase::PAYMENT_STATUS_PENDING => 0,
            Purchase::PAYMENT_STATUS_PARTIAL => 1,
            Purchase::PAYMENT_STATUS_PAID    => 2,
        ];
        $currentRank = $statusRank[$purchase->payment_status ?? Purchase::PAYMENT_STATUS_PENDING] ?? 0;
        $newRank = $statusRank[$newStatus] ?? 0;

        if ($newRank < $currentRank) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Payment status cannot be moved back to a previous stage.',
            ], 422);
        }

        if ($balanceDue <= 0 && $newStatus !== Purchase::PAYMENT_STATUS_PENDING) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This purchase is already fully paid.',
            ], 422);
        }

        if ($newStatus === Purchase::PAYMENT_STATUS_PARTIAL && round((float) $request->amount, 2) > $balanceDue) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paid amount cannot be greater than the remaining balance due (' . format_price($balanceDue) . ').',
            ], 422);
        }

        DB::transaction(function () use ($purchase, $newStatus, $request, $balanceDue) {
            if ($newStatus === Purchase::PAYMENT_STATUS_PAID) {
                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'amount'      => $balanceDue,
                    'created_by'  => auth()->id(),
                ]);

                $purchase->update([
                    'payment_status' => Purchase::PAYMENT_STATUS_PAID,
                    'paid_amount'    => $purchase->total_amount,
                ]);
            } elseif ($newStatus === Purchase::PAYMENT_STATUS_PARTIAL) {
                $amount = (float) $request->amount;
                $newPaidAmount = round($purchase->paid_amount + $amount, 2);
                $finalStatus = $newPaidAmount >= $purchase->total_amount ? Purchase::PAYMENT_STATUS_PAID : Purchase::PAYMENT_STATUS_PARTIAL;

                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'amount'      => $amount,
                    'created_by'  => auth()->id(),
                ]);

                $purchase->update([
                    'payment_status' => $finalStatus,
                    'paid_amount'    => min($newPaidAmount, $purchase->total_amount),
                ]);
            } else {
                $purchase->update(['payment_status' => Purchase::PAYMENT_STATUS_PENDING]);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier payment status updated successfully.',
        ]);
    }
}
