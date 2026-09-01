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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PurchaseStockService;

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
        $query = Purchase::with([
                'supplier:id,name',
                'items.product:id,name',
                'createdBy:id,name'
            ])
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
            });

        $searchValue = $request->input('search.value');
        if ($searchValue) {
            $query->where(function ($sq) use ($searchValue) {
                $sq->where('invoice_no', 'like', "%{$searchValue}%")
                   ->orWhereHas('supplier', fn($lq) => $lq->where('name', 'like', "%{$searchValue}%"))
                   ->orWhereHas('createdBy', fn($uq) => $uq->where('name', 'like', "%{$searchValue}%"));
            });
        }

        $recordsTotal = Purchase::count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0) $length = 25;

        $orderColumnMap = [
            1 => 'invoice_no',
            2 => 'supplier',
            3 => 'total_amount',
            4 => 'status',
            5 => 'payment_status',
            6 => 'payment_method',
            8 => 'created_at',
            9 => 'created_at',
        ];

        $orderArr = $request->input('order', []);
        $sortKey = 'created_at';
        $sortDir = 'desc';
        if (!empty($orderArr) && isset($orderArr[0]['column'], $orderArr[0]['dir'])) {
            $colIdx = (int) $orderArr[0]['column'];
            $dir = strtolower($orderArr[0]['dir']) === 'asc' ? 'asc' : 'desc';
            if (isset($orderColumnMap[$colIdx])) {
                $sortKey = $orderColumnMap[$colIdx];
                $sortDir = $dir;
            }
        }

        if ($sortKey === 'supplier') {
            $query->leftJoin('suppliers as supp', 'purchases.supplier_id', '=', 'supp.id')
                  ->select('purchases.*')
                  ->orderBy('supp.name', $sortDir);
        } else if ($sortKey === 'invoice_no') {
            $query->orderByRaw("LENGTH(purchases.invoice_no) {$sortDir}")
                  ->orderBy("purchases.invoice_no", $sortDir);
        } else {
            $query->orderBy("purchases.{$sortKey}", $sortDir);
        }
        $query->orderBy('purchases.id', 'desc');

        $invoices = (clone $query)
            ->skip($start)
            ->take($length)
            ->get();

        $canEdit                       = auth()->user()->can('edit purchases');
        $canDelete                     = auth()->user()->hasRole('super-admin');
        $canEditPurchasesStatus        = auth()->user()->can('edit purchases status');
        $canEditPurchasesPaymentStatus = auth()->user()->can('edit purchases payment status');
        $canDownloadPurchases          = auth()->user()->can('download purchases');

        $data = $invoices->map(function ($invoice, $index) use ($start, $canEdit, $canDelete, $canEditPurchasesStatus, $canEditPurchasesPaymentStatus, $canDownloadPurchases) {
            $canEditRecord = $canEdit && can_modify_past_date_record($invoice->created_at);
            $canDeleteRecord = $canDelete && can_modify_past_date_record($invoice->created_at);
            $canStatusRecord = $canEditPurchasesStatus && can_modify_past_date_record($invoice->created_at);
            $canPaymentStatusRecord = $canEditPurchasesPaymentStatus && can_modify_past_date_record($invoice->created_at);

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
            $actions .= '<button class="dropdown-item purchase-print-barcode-btn" data-purchase-ids="' . $invoice->id . '"><i class="ti ti-printer me-2"></i>Print Barcode</button>';
            if ($canEditRecord) {
                $actions .= '<a href="' . route('admin.purchases.edit', $invoice) . '" class="dropdown-item"><i class="ti ti-pencil me-2"></i>Edit</a>';
            }
            if ($canStatusRecord && $invoice->status == 1) {
                $actions .= '<button class="dropdown-item change-purchase-status-btn" data-url="' . route('admin.purchases.status', $invoice) . '" data-current="' . $invoice->status . '"><i class="ti ti-adjustments-horizontal me-2"></i>Update Status</button>';
            }
            if ($canPaymentStatusRecord && ($invoice->status == 1 || ($invoice->status == 2 && $invoice->payment_status != 2))) {
                $actions .= '<button class="dropdown-item change-purchase-payment-status-btn" data-url="' . route('admin.purchases.update-payment-status', $invoice) . '" data-history-url="' . route('admin.purchases.payment-history', $invoice) . '" data-current="' . ($invoice->payment_status ?? 1) . '"><i class="ti ti-credit-card me-2"></i>Update Payment Status</button>';
            }
            if ($canDeleteRecord) {
                $actions .= '<div class="dropdown-divider"></div>';
                $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.purchases.destroy', $invoice) . '" data-row-id="purchase-row-' . $invoice->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
            }
            $actions .= '</div></div>';

            return [
                'index'          => $start + $index + 1,
                'invoice_no'     => '<code>' . e($invoice->invoice_no) . '</code>',
                'raw_invoice_no' => $invoice->invoice_no,
                'supplier'       => e($invoice->supplier->name ?? '-'),
                'status'         => $statusBadge,
                'payment_status' => $paymentStatusBadge,
                'total_amount'     => format_price($invoice->total_amount ?? 0),
                'raw_total_amount' => (float) ($invoice->total_amount ?? 0),
                'created_by'     => e($invoice->createdBy->name ?? '-'),
                'payment_method' => match (strtolower((string) ($invoice->payment_method ?? ''))) {
                    'online_cash' => 'Online + Cash',
                    'cash'        => 'Cash',
                    'bank'        => 'Bank Transfer',
                    'cheque'      => 'Cheque',
                    ''            => '-',
                    default       => 'Online',
                },
                'date_group'     => $invoice->created_at->format('d M Y'),
                'date_sort'      => $invoice->created_at->format('YmdHis'),
                'actions'        => $actions,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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
        $locations = Location::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $invoiceNo = generate_invoice_no('PS', Purchase::class);
        return view('purchases.create', compact('suppliers', 'locations', 'invoiceNo'));
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
            'items.*.mrp'            => ['nullable', 'numeric', 'min:0'],
            'items.*.custom_size_value' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.discount_type'  => ['nullable', 'string', 'in:flat,percentage'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_type'          => ['nullable', 'string', 'in:flat,percentage'],
            'discount_value'         => ['nullable', 'numeric', 'min:0'],
            'status'                 => ['nullable', 'integer', 'in:1,2,3'],
            'payment_status'         => ['nullable', 'integer', 'in:1,2,3'],
            'payment_method'         => ['nullable', 'string', 'in:cash,online'],
            'paid_amount'            => ['nullable', 'numeric', 'min:0.01', 'required_if:payment_status,3'],
        ], [], [
            'supplier_id' => 'supplier',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_gst')) {
            $supplier = Supplier::find($request->supplier_id);
            if (!$supplier || !$supplier->gst_no) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['is_gst' => ['Selected supplier has no GST No. Please add a GST No to the supplier before creating a GST bill.']],
                ], 422);
            }
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
                    'mrp'                => isset($itemData['mrp']) && (float)$itemData['mrp'] > 0 ? (float)$itemData['mrp'] : null,
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

            $targetAmountToDeduct = max($paidAmount, $grandTotal);

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

            $dateInput = $request->input('created_at') ?: $request->input('purchase_date');
            if ($dateInput && (auth()->user()->hasRole('super-admin') || auth()->user()->can('edit past date records'))) {
                $timeStr = now()->format('H:i:s');
                try {
                    $pDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', trim($dateInput) . ' ' . $timeStr);
                } catch (\Throwable $e) {
                    $pDate = \Carbon\Carbon::parse(trim($dateInput) . ' ' . $timeStr);
                }
                \Illuminate\Support\Facades\DB::table('purchases')->where('id', $invoice->id)->update(['created_at' => $pDate->toDateTimeString()]);
            }

            $advDeducted = \App\Models\SupplierAdvancePayment::adjustAdvanceForPurchase($invoice, $targetAmountToDeduct);
            $remDirect = max(0.0, round($paidAmount - $advDeducted, 2));

            if ($remDirect > 0) {
                PurchasePayment::create([
                    'purchase_id' => $invoice->id,
                    'amount'      => $remDirect,
                    'created_by'  => auth()->id(),
                ]);
            }

            $finalPaid = round($advDeducted + $remDirect, 2);
            $finalStatus = ($finalPaid >= $grandTotal)
                ? Purchase::PAYMENT_STATUS_PAID
                : ($finalPaid > 0 ? Purchase::PAYMENT_STATUS_PARTIAL : Purchase::PAYMENT_STATUS_PENDING);

            if ($invoice->paid_amount != $finalPaid || $invoice->payment_status != $finalStatus) {
                Purchase::withoutEvents(fn () => $invoice->update([
                    'paid_amount'    => min($finalPaid, $grandTotal),
                    'payment_status' => $finalStatus,
                ]));
                $invoice->paid_amount = min($finalPaid, $grandTotal);
                $invoice->payment_status = $finalStatus;
            }

            (new \App\Observers\PurchaseObserver())->updated($invoice);

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

                $productObj = \App\Models\Product::find($item['product_id']);
                $itemMultiplier = \App\Services\PurchaseBatchService::multiplierForProduct($productObj, $item['pair_type'] ?? null, $item['custom_size_value'] ?? null);
                $batchStockQty = (float) $item['quantity'] * $itemMultiplier;

                \App\Services\PurchaseBatchService::addBatchStock($defaultLocation->id, (int)$item['product_id'], !empty($item['product_variant_id']) ? (int)$item['product_variant_id'] : null, $createdItem->id, (float)$item['purchase_price'], (float)$batchStockQty);
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

        if (!can_modify_past_date_record($purchase->created_at)) {
            return redirect()->route('admin.purchases.index')->with('error', 'You do not have permission to edit past date records.');
        }

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
        $locations = Location::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $existingItems = $purchase->items->map(function ($item) {
            $product = $item->product;
            return [
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'custom_size_value'  => $item->custom_size_value,
                'purchase_price'     => $item->purchase_price,
                'discount_type'      => $item->discount_type ?? 'flat',
                'discount_value'     => $item->discount_value ?? 0,
                'quantity'           => $item->quantity,
                'product'            => $product ? [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'label' => $product->name . ' (' . ($product->barcode ?? '-') . ')',
                    'price' => (float) $product->sale_price,
                    'purchase_price' => (float) $product->purchase_price,
                    'image' => $product->primary_image_url,
                    'type' => $product->type ?? ($product->is_variable ? 'variable' : 'simple'),
                    'pair_product' => (bool) $product->pair_product,
                    'custom_sizes' => $product->custom_sizes ?? [],
                    'variants' => $product->variants->map(fn($v) => [
                        'id' => $v->id,
                        'attr_name' => $v->attributeValue->attribute->name ?? 'Attribute',
                        'value_name' => $v->attributeValue->value ?? '',
                        'sale_price' => (float) ($v->sale_price ?? $product->sale_price),
                        'purchase_price' => (float) ($v->purchase_price ?? $product->purchase_price),
                    ])->values()->toArray(),
                ] : null,
            ];
        })->values();

        return view('purchases.edit', compact('purchase', 'suppliers', 'locations', 'existingItems'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $this->authorize('edit purchases');

        if (!can_modify_past_date_record($purchase->created_at)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You do not have permission to edit past date records.',
            ], 403);
        }

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
        ], [], [
            'supplier_id' => 'supplier',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_gst')) {
            $supplier = Supplier::find($request->supplier_id);
            if (!$supplier || !$supplier->gst_no) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['is_gst' => ['Selected supplier has no GST No. Please add a GST No to the supplier before creating a GST bill.']],
                ], 422);
            }
        }

        try {
            $defaultLocation = $this->defaultPurchaseLocation();
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        try {
            DB::transaction(function () use ($request, $purchase, $defaultLocation) {
            $oldItemsSnapshot = $purchase->items->map(function ($item) {
                return [
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                    'price'              => (float) $item->purchase_price,
                ];
            })->values()->all();

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

            $dateInput = $request->input('created_at') ?: $request->input('purchase_date');
            if ($dateInput && (auth()->user()->hasRole('super-admin') || auth()->user()->can('edit past date records'))) {
                $timeStr = $purchase->created_at ? $purchase->created_at->format('H:i:s') : now()->format('H:i:s');
                try {
                    $pDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', trim($dateInput) . ' ' . $timeStr);
                } catch (\Throwable $e) {
                    $pDate = \Carbon\Carbon::parse(trim($dateInput) . ' ' . $timeStr);
                }
                $updateData['created_at'] = $pDate;
            }

            $oldFieldsSnapshot = $purchase->only(array_keys($updateData));

            Purchase::withoutEvents(fn () => Purchase::withoutActivityLogging(fn () => $purchase->update($updateData)));

            $targetPay = $paidAmount;
            \App\Models\SupplierAdvancePayment::restoreAdvanceForPurchase($purchase);

            if ($paymentStatus === Purchase::PAYMENT_STATUS_PENDING) {
                // Remove payments linked to this purchase and reset paid_amount
                PurchasePayment::where('purchase_id', $purchase->id)->delete();
                $finalPaid = 0.0;
                $finalStatus = Purchase::PAYMENT_STATUS_PENDING;
            } else {
                $advDeducted = \App\Models\SupplierAdvancePayment::adjustAdvanceForPurchase($purchase, $targetPay);
                $remDirect = max(0.0, round($paidAmount - $advDeducted, 2));

                $existingDirectPaid = (float) \App\Models\PurchasePayment::where('purchase_id', $purchase->id)
                    ->whereNull('bulk_purchase_payment_id')
                    ->where(function ($q) {
                        $q->where('is_advance', false)->orWhereNull('is_advance');
                    })->sum('amount');

                if ($remDirect > $existingDirectPaid) {
                    $paidAmountDelta = round($remDirect - $existingDirectPaid, 2);
                    if ($paidAmountDelta >= 0.01) {
                        PurchasePayment::create([
                            'purchase_id' => $purchase->id,
                            'amount'      => $paidAmountDelta,
                            'created_by'  => auth()->id(),
                        ]);
                    }
                } elseif ($remDirect < $existingDirectPaid) {
                    // If paid amount was decreased
                    $diffToReduce = round($existingDirectPaid - $remDirect, 2);
                    if ($diffToReduce >= 0.01) {
                        $directPayments = PurchasePayment::where('purchase_id', $purchase->id)
                            ->whereNull('bulk_purchase_payment_id')
                            ->where(function ($q) {
                                $q->where('is_advance', false)->orWhereNull('is_advance');
                            })->latest()->get();

                        foreach ($directPayments as $dp) {
                            if ($diffToReduce <= 0) break;
                            if ((float) $dp->amount <= $diffToReduce) {
                                $diffToReduce -= (float) $dp->amount;
                                $dp->delete();
                            } else {
                                $dp->update(['amount' => (float) $dp->amount - $diffToReduce]);
                                $diffToReduce = 0;
                            }
                        }
                    }
                }

                $finalPaid = round($advDeducted + $remDirect, 2);
                $finalStatus = ($finalPaid >= $grandTotal)
                    ? Purchase::PAYMENT_STATUS_PAID
                    : ($finalPaid > 0 ? Purchase::PAYMENT_STATUS_PARTIAL : Purchase::PAYMENT_STATUS_PENDING);
            }

            $purchase->update([
                'paid_amount'    => min($finalPaid, $grandTotal),
                'payment_status' => $finalStatus,
            ]);

            (new \App\Observers\PurchaseObserver())->updated($purchase);

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

                $productObj = \App\Models\Product::find($item['product_id']);
                $itemMultiplier = \App\Services\PurchaseBatchService::multiplierForProduct($productObj, $item['pair_type'] ?? null, $item['custom_size_value'] ?? null);
                $batchStockQty = (float) $item['quantity'] * $itemMultiplier;

                \App\Services\PurchaseBatchService::addBatchStock((int)$defaultLocation->id, (int)$item['product_id'], !empty($item['product_variant_id']) ? (int)$item['product_variant_id'] : null, $createdItem->id, (float)$item['purchase_price'], (float)$batchStockQty);
            }

            if ($newStatus == Purchase::STATUS_APPROVE) {
                $this->approveInvoice($purchase);
            }

            $newItemsSnapshot = collect($itemsData)->map(function ($item) {
                return [
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity'           => $item['quantity'],
                    'price'              => (float) $item['purchase_price'],
                ];
            })->values()->all();

            ActivityLogger::log(
                'Purchase',
                'update',
                $purchase,
                ['fields' => $oldFieldsSnapshot, 'items' => $oldItemsSnapshot],
                ['fields' => $updateData, 'items' => $newItemsSnapshot],
                'Purchase #' . $purchase->invoice_no . ' updated'
            );
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

        if (!can_modify_past_date_record($purchase->created_at)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You do not have permission to edit past date records.',
            ], 403);
        }

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
 
        $oldStatus = (int) $purchase->status;

        DB::transaction(function () use ($purchase, $newStatus) {
            Purchase::withoutActivityLogging(fn () => $purchase->update(['status' => $newStatus]));

            if ($newStatus == 2) {
                $this->approveInvoice($purchase);
            }
        });

        $statusLabels = [
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Declined',
        ];
        $statusName = $statusLabels[$newStatus] ?? $newStatus;

        ActivityLogger::log(
            'Purchase',
            'update',
            $purchase,
            ['status' => $oldStatus],
            ['status' => (int) $newStatus],
            'Purchase #' . $purchase->invoice_no . ' status changed to ' . $statusName
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase status updated to ' . $statusName . '.',
        ]);
    }

    public function destroy(Purchase $purchase)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        if (!can_modify_past_date_record($purchase->created_at)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You do not have permission to delete past date records.',
            ], 403);
        }

        $originalInvoiceNo = $purchase->invoice_no;

        DB::transaction(function () use ($purchase) {
            \App\Models\SupplierAdvancePayment::restoreAdvanceForPurchase($purchase);

            if ($purchase->status == Purchase::STATUS_APPROVE) {
                foreach ($purchase->items as $pItem) {
                    $itemMultiplier = \App\Services\PurchaseBatchService::multiplierForProduct($pItem->product, $pItem->pair_type ?? null, $pItem->custom_size_value ?? null);
                    $batchStockQty = (float) $pItem->quantity * $itemMultiplier;
                    \App\Services\PurchaseBatchService::deductBatchStock((int)$purchase->location_id, (int)$pItem->product_id, !empty($pItem->product_variant_id) ? (int)$pItem->product_variant_id : null, (float)$pItem->purchase_price, (float)$batchStockQty);
                }
                PurchaseStockService::reverse($purchase, 'deletion');
            }

            Purchase::withoutActivityLogging(function () use ($purchase) {
                $itemIds = $purchase->items()->pluck('id');
                PurchaseAllocation::whereIn('purchase_item_id', $itemIds)->delete();
                $purchase->items()->delete();

                $purchase->update(['invoice_no' => 'DEL-' . $purchase->id . '-' . $purchase->invoice_no]);
                $purchase->delete();
            });
        });

        ActivityLogger::log(
            'Purchase',
            'delete',
            $purchase,
            ['invoice_no' => $originalInvoiceNo],
            null,
            'Purchase ' . $originalInvoiceNo . ' deleted'
        );

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

        if (request()->boolean('auto_print') && !request()->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Purchase ' . $purchase->invoice_no,
                'pdfUrl' => route('admin.purchases.pdf', [$purchase, 'auto_print' => 1, 'stream' => 1]),
            ]);
        }

        $purchase->load(['supplier', 'createdBy', 'items.product.variants.attributeValue.attribute', 'items.allocations.location']);

        $pdf = Pdf::loadView('purchases.pdf', compact('purchase'))
            ->setPaper('a4', 'portrait');

        ActivityLogger::log('Purchase', 'export', $purchase, null, null, 'Invoice PDF exported for purchase #' . $purchase->invoice_no);

        if (request()->boolean('stream')) {
            return $pdf->stream('purchase-' . $purchase->invoice_no . '.pdf');
        }

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
        PurchaseStockService::approve($purchase);
    }

    private function reverseInvoiceStock(Purchase $purchase): void
    {
        PurchaseStockService::reverse($purchase);
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

        return [Purchase::PAYMENT_STATUS_PENDING, 0.0];
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
        if (!$product || !$product->pair_product) {
            return null;
        }

        $value = isset($itemData['custom_size_value']) ? (float) $itemData['custom_size_value'] : null;
        $validSizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn ($s) => (float) $s)->filter(fn ($s) => $s > 0);

        if ($value && $validSizes->contains(fn ($s) => abs($s - $value) < 0.001)) {
            return $value;
        }

        if ($validSizes->count() > 0) {
            return (float) $validSizes->max();
        }

        return 2.0;
    }

    public function paymentHistory(Purchase $purchase)
    {
        $this->authorize('view purchases');

        $actualPaid = (float) $purchase->payments()->sum('amount');
        if (abs((float) $purchase->paid_amount - $actualPaid) > 0.001) {
            $finalStatus = $actualPaid >= (float) $purchase->total_amount
                ? Purchase::PAYMENT_STATUS_PAID
                : ($actualPaid > 0 ? Purchase::PAYMENT_STATUS_PARTIAL : Purchase::PAYMENT_STATUS_PENDING);

            Purchase::withoutActivityLogging(fn () => $purchase->update([
                'paid_amount'    => min($actualPaid, (float) $purchase->total_amount),
                'payment_status' => $finalStatus,
            ]));
            $purchase->paid_amount = min($actualPaid, (float) $purchase->total_amount);
            $purchase->payment_status = $finalStatus;
        }

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

    public function barcodeItems(Purchase $purchase)
    {
        $this->authorize('view purchases');

        $items = $purchase->items()->with(['product', 'variant.attributeValue'])->get()
            ->filter(fn ($item) => $item->product && !empty($item->product->barcode))
            ->map(function ($item) {
                $qty = (int) $item->quantity;

                return [
                    'id'                  => $item->product->id,
                    'name'                => $item->product->name,
                    'barcode'             => $item->product->barcode,
                    'quantity'            => max(1, $qty),
                    'pair_product'        => (bool) $item->product->pair_product,
                    'pair_mode'           => $item->product->pair_mode ?? 'custom_size',
                    'custom_sizes'        => $item->product->custom_sizes ?? [],
                    'custom_size_value'   => $item->custom_size_value,
                    'selected_variant_id' => $item->product_variant_id,
                    'variant_label'       => $item->variant?->attributeValue?->value,
                ];
            })
            ->values();

        return response()->json(['status' => 'success', 'items' => $items]);
    }

    public function updatePaymentStatus(Request $request, Purchase $purchase)
    {
        $this->authorize('edit purchases payment status');

        if (!can_modify_past_date_record($purchase->created_at)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You do not have permission to edit past date records.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_status' => ['required', 'in:1,2,3'],
            'amount'         => ['nullable', 'numeric', 'min:0.01', 'required_if:payment_status,3'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $newStatus = (int) $request->payment_status;
        $balanceDue = round($purchase->total_amount - $purchase->paid_amount, 2);

        if ($newStatus === Purchase::PAYMENT_STATUS_PARTIAL && round((float) $request->amount, 2) > $balanceDue) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paid amount cannot be greater than the remaining balance due (' . format_price($balanceDue) . ').',
            ], 422);
        }

        $oldPaymentStatus = (int) $purchase->payment_status;
        $oldPaidAmount = (float) $purchase->paid_amount;

        DB::transaction(function () use ($purchase, $newStatus, $request, $balanceDue, $oldPaidAmount) {
            if ($newStatus === Purchase::PAYMENT_STATUS_PAID) {
                $purchase->payment_status = Purchase::PAYMENT_STATUS_PAID;
                $advDeducted = \App\Models\SupplierAdvancePayment::adjustAdvanceForPurchase($purchase, $balanceDue);
                $remDirect = max(0.0, round($balanceDue - $advDeducted, 2));

                if ($remDirect > 0) {
                    PurchasePayment::create([
                        'purchase_id' => $purchase->id,
                        'amount'      => $remDirect,
                        'created_by'  => auth()->id(),
                    ]);
                }

                $purchase->update([
                    'payment_status' => Purchase::PAYMENT_STATUS_PAID,
                    'paid_amount'    => $purchase->total_amount,
                ]);
            } elseif ($newStatus === Purchase::PAYMENT_STATUS_PARTIAL) {
                $amount = (float) $request->amount;
                $purchase->payment_status = Purchase::PAYMENT_STATUS_PARTIAL;
                $advDeducted = \App\Models\SupplierAdvancePayment::adjustAdvanceForPurchase($purchase, $amount);
                $remDirect = max(0.0, round($amount - $advDeducted, 2));

                if ($remDirect > 0) {
                    PurchasePayment::create([
                        'purchase_id' => $purchase->id,
                        'amount'      => $remDirect,
                        'created_by'  => auth()->id(),
                    ]);
                }

                $newPaidAmount = round($oldPaidAmount + $amount, 2);
                $finalStatus = $newPaidAmount >= (float) $purchase->total_amount ? Purchase::PAYMENT_STATUS_PAID : Purchase::PAYMENT_STATUS_PARTIAL;

                $purchase->update([
                    'payment_status' => $finalStatus,
                    'paid_amount'    => min($newPaidAmount, (float) $purchase->total_amount),
                ]);
            } else {
                \App\Models\SupplierAdvancePayment::restoreAdvanceForPurchase($purchase);
                $purchase->payments()->delete();
                $purchase->update([
                    'payment_status' => Purchase::PAYMENT_STATUS_PENDING,
                    'paid_amount'    => 0,
                ]);
            }
        });

        ActivityLogger::log(
            'Purchase',
            'update',
            $purchase,
            ['payment_status' => $oldPaymentStatus, 'paid_amount' => $oldPaidAmount],
            ['payment_status' => (int) $purchase->payment_status, 'paid_amount' => (float) $purchase->paid_amount],
            'Purchase #' . $purchase->invoice_no . ' payment status updated'
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier payment status updated successfully.',
        ]);
    }

    public function getMappedProductsJson()
    {
        return response()->json($this->getMappedProducts());
    }

    private function getMappedProducts()
    {
        return Cache::store('file')->remember('all_mapped_products_purchases', 1800, function () {
            $products = Product::with([
                'variants.attributeValue.attribute',
                'primaryImage',
                'inventories',
            ])->where('status', 1)->orderBy('name')->get();

            Product::preloadVariantStock($products);

            $mapped = $products->map(function ($p) {
                $data = [
                    'id'             => $p->id,
                    'name'           => $p->name,
                    'barcode'        => $p->barcode,
                    'type'           => $p->type,
                    'purchase_price' => $p->purchase_price,
                    'image'          => $p->primary_image_url,
                    'pair_product'   => (bool) $p->pair_product,
                    'pair_mode'      => $p->pair_mode,
                    'custom_sizes'   => $p->custom_sizes ?? [],
                ];

                if ($p->type === 'variable') {
                    $data['variants'] = $p->variants->filter(function ($v) {
                        return $v->status == 1;
                    })->values()->map(function ($v) {
                        return [
                            'id'                 => $v->id,
                            'attribute_value_id' => $v->attribute_value_id,
                            'purchase_price'     => $v->purchase_price,
                            'sale_price'         => $v->sale_price,
                            'custom_sizes'       => $v->custom_sizes ?? [],
                            'attr_name'          => $v->attributeValue->attribute->name ?? '',
                            'value_name'         => $v->attributeValue->value ?? '',
                        ];
                    })->all();
                }

                $stockByLocation = [];
                if ($p->type === 'variable') {
                    $variantStock = $p->getVariantStock();
                    foreach ($variantStock as $locId => $locData) {
                        $stockByLocation[$locId] = [
                            'parent'   => $locData['parent'],
                            'variants' => $locData['variants'],
                        ];
                    }
                } else {
                    foreach ($p->inventories as $inv) {
                        $stockByLocation[$inv->location_id] = $inv->quantity;
                    }
                }
                $data['stock_by_location'] = $stockByLocation;

                return $data;
            })->values()->all();

            Product::clearPreloadedVariantStock();

            return $mapped;
        });
    }
}
