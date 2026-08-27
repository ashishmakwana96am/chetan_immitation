<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Services\ActivityLogger;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurchaseBillController extends Controller
{
    protected $exportService;

    public function __construct(ReportExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function index()
    {
        $this->authorize('view purchase bills');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        return view('purchase-bills.index', compact('locations', 'isRestricted'));
    }

    public function pendingCount()
    {
        $this->authorize('view purchase bills');

        $user = auth()->user();

        $count = PurchaseBill::where('status', PurchaseBill::STATUS_PENDING)
            ->when($user->location_id && !$user->hasRole('super-admin'), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('from_location_id', $user->location_id)
                        ->orWhere('to_location_id', $user->location_id);
                });
            })
            ->count();

        return response()->json(['status' => 'success', 'count' => $count]);
    }

    public function data(Request $request)
    {
        $this->authorize('view purchase bills');

        $user = auth()->user();

        $query = PurchaseBill::query()
            ->when($user->location_id && !$user->hasRole('super-admin'), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('from_location_id', $user->location_id)
                        ->orWhere('to_location_id', $user->location_id);
                });
            })
            ->when($request->from_location_id, fn ($q) => $q->where('from_location_id', $request->from_location_id))
            ->when($request->to_location_id, fn ($q) => $q->where('to_location_id', $request->to_location_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->product_id, fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('product_id', $request->product_id)))
            ->when($request->start_date, fn ($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn ($q) => $q->whereDate('created_at', '<=', $request->end_date));

        $searchValue = $request->input('search.value');
        if ($searchValue) {
            $query->where(function ($sq) use ($searchValue) {
                $sq->where('transfer_no', 'like', "%{$searchValue}%")
                   ->orWhereHas('fromLocation', fn($lq) => $lq->where('name', 'like', "%{$searchValue}%"))
                   ->orWhereHas('toLocation', fn($lq) => $lq->where('name', 'like', "%{$searchValue}%"))
                   ->orWhereHas('createdBy', fn($uq) => $uq->where('name', 'like', "%{$searchValue}%"));
            });
        }

        $recordsTotal = PurchaseBill::count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0) $length = 25;

        $orderColumnMap = [
            1  => 'transfer_no',
            2  => 'from_location',
            3  => 'to_location',
            4  => 'items_count',
            5  => 'total_amount',
            6  => 'total_mrp',
            9  => 'created_by',
            11 => 'created_at',
            12 => 'created_at',
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

        if ($sortKey === 'from_location') {
            $query->leftJoin('locations as from_loc', 'purchase_bills.from_location_id', '=', 'from_loc.id')
                  ->select('purchase_bills.*')
                  ->orderBy('from_loc.name', $sortDir);
        } elseif ($sortKey === 'to_location') {
            $query->leftJoin('locations as to_loc', 'purchase_bills.to_location_id', '=', 'to_loc.id')
                  ->select('purchase_bills.*')
                  ->orderBy('to_loc.name', $sortDir);
        } elseif ($sortKey === 'created_by') {
            $query->leftJoin('users as u', 'purchase_bills.created_by', '=', 'u.id')
                  ->select('purchase_bills.*')
                  ->orderBy('u.name', $sortDir);
        } elseif ($sortKey === 'items_count') {
            $query->orderBy(
                DB::raw('(SELECT COALESCE(SUM(quantity), 0) FROM purchase_bill_items WHERE purchase_bill_items.purchase_bill_id = purchase_bills.id)'),
                $sortDir
            );
        } elseif ($sortKey === 'total_amount') {
            $query->orderBy(
                DB::raw('(SELECT COALESCE(SUM(pbi.quantity * COALESCE(pbi.purchase_price, 0)), 0) 
                          FROM purchase_bill_items pbi 
                          WHERE pbi.purchase_bill_id = purchase_bills.id)'),
                $sortDir
            );
        } elseif ($sortKey === 'total_mrp') {
            $query->orderBy(
                DB::raw('(SELECT COALESCE(SUM(pbi.quantity * COALESCE(pbi.mrp, 0)), 0) 
                          FROM purchase_bill_items pbi 
                          WHERE pbi.purchase_bill_id = purchase_bills.id)'),
                $sortDir
            );
        } else {
            $query->orderBy("purchase_bills.{$sortKey}", $sortDir);
        }
        $query->orderBy('purchase_bills.id', 'desc');

        $transfers = (clone $query)
            ->with(['fromLocation', 'toLocation', 'createdBy', 'items.product', 'items.variant'])
            ->skip($start)
            ->take($length)
            ->get();

        $allFilteredIds = (clone $query)->pluck('purchase_bills.id');
        $grandTotals = DB::table('purchase_bill_items')
            ->whereIn('purchase_bill_items.purchase_bill_id', $allFilteredIds)
            ->selectRaw('
                SUM(purchase_bill_items.quantity * COALESCE(purchase_bill_items.purchase_price, 0)) as grand_total_amount,
                SUM(purchase_bill_items.quantity * COALESCE(purchase_bill_items.mrp, 0)) as grand_total_mrp
            ')
            ->first();

        $grandTotalAmount = (float) ($grandTotals->grand_total_amount ?? 0);
        $grandTotalMrp    = (float) ($grandTotals->grand_total_mrp ?? 0);

        $canAccept = auth()->user()->can('accept purchase bills');
        $canReject = auth()->user()->can('reject purchase bills');
        $canEditPaymentStatus = auth()->user()->can('edit purchase bills payment status');
        $canEdit = auth()->user()->can('edit purchase bills');

        $data = $transfers->map(function ($transfer, $index) use ($start, $canAccept, $canReject, $canEditPaymentStatus, $canEdit) {
            $canEditRecord = $canEdit && can_modify_past_date_record($transfer->created_at);
            $canAcceptRecord = $canAccept;
            $canRejectRecord = $canReject;
            $canPaymentStatusRecord = $canEditPaymentStatus && can_modify_past_date_record($transfer->created_at);

            $statusBadge = $this->statusBadge($transfer->status);
            $paymentStatusBadge = $this->paymentStatusBadge((int) ($transfer->payment_status ?? PurchaseBill::PAYMENT_STATUS_PENDING));

            [$totalAmount, $totalMrp] = $this->purchaseBillTotals($transfer);

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.purchase-bills.show', $transfer) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            if ($transfer->status == PurchaseBill::STATUS_PENDING) {
                if ($canEditRecord) {
                    $actions .= '<a href="' . route('admin.purchase-bills.edit', $transfer) . '" class="dropdown-item"><i class="ti ti-pencil me-2"></i>Edit</a>';
                }
                if ($canAcceptRecord) {
                    $actions .= '<button class="dropdown-item text-success purchase-bill-action" data-url="' . route('admin.purchase-bills.accept', $transfer) . '" data-method="PATCH" data-title="Accept Purchase Bill" data-text="Stock will move from source to destination location."><i class="ti ti-check me-2"></i>Accept</button>';
                }
                if ($canRejectRecord) {
                    $actions .= '<button class="dropdown-item text-danger purchase-bill-action" data-url="' . route('admin.purchase-bills.reject', $transfer) . '" data-method="PATCH" data-title="Reject Purchase Bill" data-text="No inventory stock will be changed."><i class="ti ti-x me-2"></i>Reject</button>';
                }
            }
            if ($canPaymentStatusRecord && $transfer->status == PurchaseBill::STATUS_ACCEPTED && (int) ($transfer->payment_status ?? PurchaseBill::PAYMENT_STATUS_PENDING) !== PurchaseBill::PAYMENT_STATUS_PAID) {
                $actions .= '<button class="dropdown-item change-purchase-bill-payment-status-btn" data-url="' . route('admin.purchase-bills.update-payment-status', $transfer) . '" data-history-url="' . route('admin.purchase-bills.payment-history', $transfer) . '" data-current="' . ((int) ($transfer->payment_status ?? PurchaseBill::PAYMENT_STATUS_PENDING)) . '"><i class="ti ti-credit-card me-2"></i>Update Payment Status</button>';
            }
            $actions .= '</div></div>';

            return [
                'index' => $start + $index + 1,
                'transfer_no' => '<code>' . e($transfer->transfer_no) . '</code>',
                'from_location' => e($transfer->fromLocation->name ?? '-'),
                'to_location' => e($transfer->toLocation->name ?? '-'),
                'items_count' => (int) $transfer->items->sum('quantity'),
                'total_amount' => format_price($totalAmount),
                'total_amount_raw' => round($totalAmount, 2),
                'total_mrp' => format_price($totalMrp),
                'total_mrp_raw' => round($totalMrp, 2),
                'status' => $statusBadge,
                'payment_status' => $paymentStatusBadge,
                'created_by' => e($transfer->createdBy->name ?? '-'),
                'date_group' => $transfer->created_at->format('d M Y'),
                'date_sort' => $transfer->created_at->format('YmdHis'),
                'actions' => $actions,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'grand_total_amount' => format_price($grandTotalAmount),
            'grand_total_mrp' => format_price($grandTotalMrp),
            'data' => $data,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('export purchase bills');

        $user = auth()->user();

        $transfers = PurchaseBill::with(['fromLocation', 'toLocation', 'createdBy', 'items.product', 'items.variant'])
            ->withCount('items')
            ->when($user->location_id && !$user->hasRole('super-admin'), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('from_location_id', $user->location_id)
                        ->orWhere('to_location_id', $user->location_id);
                });
            })
            ->when($request->from_location_id, fn ($q) => $q->where('from_location_id', $request->from_location_id))
            ->when($request->to_location_id, fn ($q) => $q->where('to_location_id', $request->to_location_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->product_id, fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('product_id', $request->product_id)))
            ->when($request->start_date, fn ($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn ($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        if ($transfers->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        $spreadsheet = $this->exportService->exportPurchaseBills($transfers);

        ActivityLogger::log('Purchase Bill', 'export', null, null, null, 'Purchase bills exported to Excel');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'purchase_bills_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function create()
    {
        $this->authorize('create purchase bills');

        $user = auth()->user();
        $canChooseSource = $user->hasRole('super-admin');

        try {
            $defaultLocation = $this->resolveSourceLocation();
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.purchase-bills.index')->with('error', $e->getMessage());
        }

        $sourceLocations = $canChooseSource
            ? Location::where('status', 1)->orderBy('name')->get()
            : collect([$defaultLocation]);
        $destinationLocations = Location::where('status', 1)->orderBy('name')->get();
        $transferNo = generate_invoice_no('ST', PurchaseBill::class, 'transfer_no');

        return view('purchase-bills.create', compact('defaultLocation', 'sourceLocations', 'canChooseSource', 'destinationLocations', 'transferNo'));
    }

    public function store(Request $request)
    {
        $this->authorize('create purchase bills');

        try {
            $defaultLocation = $this->resolveSourceLocation($request->input('from_location_id'));
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
        $request->merge(['from_location_id' => $defaultLocation->id]);

        $validator = Validator::make($request->all(), [
            'from_location_id' => ['required', 'exists:locations,id'],
            'to_location_id' => ['required', 'exists:locations,id', 'different:from_location_id'],
            'payment_method' => ['required', 'string', 'in:cash,online'],
            'payment_status' => ['nullable', 'integer', 'in:1,2,3'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.pair_type' => ['nullable', 'string', 'in:single,pair'],
            'items.*.custom_size_value' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [], [
            'from_location_id' => 'source location',
            'to_location_id'   => 'destination location',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $stockError = $this->getStockError($request->items, (int) $defaultLocation->id);
        if ($stockError) {
            return response()->json(['status' => 'error', 'message' => ['items' => [$stockError]]], 422);
        }

        DB::transaction(function () use ($request, $defaultLocation) {
            $transfer = PurchaseBill::create([
                'transfer_no' => generate_invoice_no('ST', PurchaseBill::class, 'transfer_no'),
                'from_location_id' => $defaultLocation->id,
                'to_location_id' => $request->to_location_id,
                'status' => PurchaseBill::STATUS_PENDING,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_status ?? PurchaseBill::PAYMENT_STATUS_PENDING,
                'remarks' => $request->remarks,
                'created_by' => auth()->id(),
            ]);

            foreach ($this->normalizeItems($request->items) as $item) {
                $product = Product::find($item['product_id']);
                $customSizeVal = $this->resolveCustomSizeValue($product, $item);
                $variantObj = !empty($item['product_variant_id']) ? ProductVariant::find($item['product_variant_id']) : null;
                $itemPurchasePrice = $variantObj ? $variantObj->purchase_price : ($product ? $product->purchase_price : 0);
                $itemMrp = $variantObj ? $variantObj->mrp : ($product ? $product->mrp : null);

                PurchaseBillItem::create([
                    'purchase_bill_id'   => $transfer->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'pair_type'          => $item['pair_type'] ?? 'single',
                    'custom_size_value'  => $customSizeVal,
                    'purchase_price'     => $itemPurchasePrice,
                    'mrp'                => $itemMrp,
                    'quantity'           => $item['quantity'],
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Purchase bill created successfully.']);
    }

    public function edit(PurchaseBill $purchaseBill)
    {
        $this->authorize('edit purchase bills');
        $this->guardLocationAccess($purchaseBill);

        if (!can_modify_past_date_record($purchaseBill->created_at)) {
            return redirect()->route('admin.purchase-bills.index')->with('error', 'You do not have permission to edit past date records.');
        }

        if ($purchaseBill->status != PurchaseBill::STATUS_PENDING) {
            return redirect()->route('admin.purchase-bills.show', $purchaseBill)->with('error', 'Only pending purchase bills can be edited.');
        }

        $user = auth()->user();
        $canChooseSource = $user->hasRole('super-admin');
        $defaultLocation = $purchaseBill->fromLocation;

        $sourceLocations = $canChooseSource
            ? Location::where('status', 1)->orderBy('name')->get()
            : collect([$defaultLocation]);
        $destinationLocations = Location::where('status', 1)->orderBy('name')->get();

        $purchaseBill->load('items.product', 'items.variant');
        $existingItems = $purchaseBill->items->map(function ($item) {
            $product = $item->product;
            return [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'pair_type' => $item->pair_type ?? 'single',
                'custom_size_value' => $item->custom_size_value,
                'quantity' => $item->quantity,
                'product' => $product ? [
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

        return view('purchase-bills.edit', compact('purchaseBill', 'defaultLocation', 'sourceLocations', 'canChooseSource', 'destinationLocations', 'existingItems'));
    }

    public function update(Request $request, PurchaseBill $purchaseBill)
    {
        $this->authorize('edit purchase bills');
        $this->guardLocationAccess($purchaseBill);

        if (!can_modify_past_date_record($purchaseBill->created_at)) {
            return response()->json(['status' => 'error', 'message' => 'You do not have permission to edit past date records.'], 403);
        }

        if ($purchaseBill->status != PurchaseBill::STATUS_PENDING) {
            return response()->json(['status' => 'error', 'message' => 'Only pending purchase bills can be edited.'], 422);
        }

        $user = auth()->user();
        if ($user->hasRole('super-admin') && $request->filled('from_location_id')) {
            $fromLocation = Location::where('id', $request->from_location_id)->where('status', 1)->first();
            if (!$fromLocation) {
                return response()->json(['status' => 'error', 'message' => ['from_location_id' => ['Selected source location is invalid.']]], 422);
            }
        } else {
            $fromLocation = $purchaseBill->fromLocation;
        }
        $request->merge(['from_location_id' => $fromLocation->id]);

        $validator = Validator::make($request->all(), [
            'from_location_id' => ['required', 'exists:locations,id'],
            'to_location_id' => ['required', 'exists:locations,id', 'different:from_location_id'],
            'payment_method' => ['required', 'string', 'in:cash,online'],
            'payment_status' => ['nullable', 'integer', 'in:1,2,3'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.pair_type' => ['nullable', 'string', 'in:single,pair'],
            'items.*.custom_size_value' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [], [
            'from_location_id' => 'source location',
            'to_location_id'   => 'destination location',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $stockError = $this->getStockError($request->items, (int) $fromLocation->id);
        if ($stockError) {
            return response()->json(['status' => 'error', 'message' => ['items' => [$stockError]]], 422);
        }

        DB::transaction(function () use ($request, $fromLocation, $purchaseBill) {
            $oldItemsSnapshot = $purchaseBill->items->map(function ($item) {
                return [
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                ];
            })->values()->all();

            $updateData = [
                'from_location_id' => $fromLocation->id,
                'to_location_id' => $request->to_location_id,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_status ?? PurchaseBill::PAYMENT_STATUS_PENDING,
                'remarks' => $request->remarks,
            ];
            $oldFieldsSnapshot = $purchaseBill->only(array_keys($updateData));

            PurchaseBill::withoutActivityLogging(fn () => $purchaseBill->update($updateData));

            $purchaseBill->items()->delete();

            $newItemsSnapshot = [];
            foreach ($this->normalizeItems($request->items) as $item) {
                $product = Product::find($item['product_id']);
                $customSizeVal = $this->resolveCustomSizeValue($product, $item);
                $variantObj = !empty($item['product_variant_id']) ? ProductVariant::find($item['product_variant_id']) : null;
                $itemPurchasePrice = $variantObj ? $variantObj->purchase_price : ($product ? $product->purchase_price : 0);
                $itemMrp = $variantObj ? $variantObj->mrp : ($product ? $product->mrp : null);

                PurchaseBillItem::create([
                    'purchase_bill_id'   => $purchaseBill->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'pair_type'          => $item['pair_type'] ?? 'single',
                    'custom_size_value'  => $customSizeVal,
                    'purchase_price'     => $itemPurchasePrice,
                    'mrp'                => $itemMrp,
                    'quantity'           => $item['quantity'],
                ]);

                $newItemsSnapshot[] = [
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity'           => $item['quantity'],
                ];
            }

            ActivityLogger::log(
                'Purchase Bill',
                'update',
                $purchaseBill,
                ['fields' => $oldFieldsSnapshot, 'items' => $oldItemsSnapshot],
                ['fields' => $updateData, 'items' => $newItemsSnapshot],
                'Purchase Bill #' . $purchaseBill->transfer_no . ' updated'
            );
        });

        return response()->json(['status' => 'success', 'message' => 'Purchase bill updated successfully.']);
    }

    public function show(PurchaseBill $purchaseBill)
    {
        $this->authorize('view purchase bills');
        $this->guardLocationAccess($purchaseBill);

        $purchaseBill->load([
            'fromLocation',
            'toLocation',
            'createdBy',
            'acceptedBy',
            'payments.createdBy',
            'items.product.primaryImage',
            'items.product.variants.attributeValue.attribute',
            'items.variant.attributeValue.attribute',
        ]);

        foreach ($purchaseBill->items as $item) {
            $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type, $item->custom_size_value);
            $quantity = (int) $item->quantity;
            $unitAmount = $this->purchasePriceForPurchaseBillItem($item);
            $unitMrp = $this->mrpForPurchaseBillItem($item, $multiplier);

            $item->calculated_unit_amount = $unitAmount;
            $item->calculated_line_amount = $unitAmount * $quantity;
            $item->calculated_unit_mrp = $unitMrp;
            $item->calculated_line_mrp = $unitMrp * $quantity;
            $item->calculated_multiplier = $multiplier;
        }

        [$totalAmount, $totalMrp] = $this->purchaseBillTotals($purchaseBill);

        return view('purchase-bills.show', [
            'transfer' => $purchaseBill,
            'totalAmount' => $totalAmount,
            'totalMrp' => $totalMrp,
        ]);
    }

    public function accept(PurchaseBill $purchaseBill)
    {
        $this->authorize('accept purchase bills');
        $this->guardLocationAccess($purchaseBill);

        if ($purchaseBill->status != PurchaseBill::STATUS_PENDING) {
            return response()->json(['status' => 'error', 'message' => 'Only pending purchase bills can be accepted.'], 422);
        }

        $purchaseBill->load(['items.product', 'items.variant']);
        $stockError = $this->getStockError($purchaseBill->items, (int) $purchaseBill->from_location_id);
        if ($stockError) {
            return response()->json(['status' => 'error', 'message' => $stockError], 422);
        }

        DB::transaction(function () use ($purchaseBill) {
            $totalAmount = 0.0;
            foreach ($purchaseBill->items as $item) {
                $totalAmount += $this->purchasePriceForPurchaseBillItem($item) * $item->quantity;
            }

            foreach ($purchaseBill->items as $item) {
                $source = Inventory::firstOrCreate(
                    [
                        'product_id'  => $item->product_id,
                        'location_id' => $purchaseBill->from_location_id,
                    ],
                    [
                        'quantity'   => 0,
                        'created_by' => auth()->id(),
                    ]
                );

                $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type, $item->custom_size_value);
                $stockQty = (int) round($item->quantity * $multiplier);

                $oldQty = $source->quantity;
                $source->decrement('quantity', $stockQty);
                ActivityLogger::log('Inventory', 'update', $source, ['quantity' => $oldQty], ['quantity' => $oldQty - $stockQty], 'Stock issued/moved out for purchase bill #' . $purchaseBill->transfer_no);

                $destination = Inventory::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'location_id' => $purchaseBill->to_location_id,
                    ],
                    [
                        'quantity' => 0,
                        'created_by' => auth()->id(),
                    ]
                );
                $destOldQty = $destination->quantity;
                $destination->increment('quantity', $stockQty);
                ActivityLogger::log('Inventory', 'update', $destination, ['quantity' => $destOldQty], ['quantity' => $destOldQty + $stockQty], 'Stock received/moved in for purchase bill #' . $purchaseBill->transfer_no);
            }

            if ($purchaseBill->payment_status == PurchaseBill::PAYMENT_STATUS_PAID) {
                $this->applyLocationBalanceTransfer($purchaseBill, $totalAmount);
            }

            PurchaseBill::withoutActivityLogging(fn () => $purchaseBill->update([
                'status' => PurchaseBill::STATUS_ACCEPTED,
                'accepted_by' => auth()->id(),
                'accepted_at' => now(),
            ]));
        });

        ActivityLogger::log(
            'Purchase Bill',
            'update',
            $purchaseBill,
            ['status' => PurchaseBill::STATUS_PENDING],
            ['status' => PurchaseBill::STATUS_ACCEPTED],
            'Purchase Bill #' . $purchaseBill->transfer_no . ' accepted'
        );

        return response()->json(['status' => 'success', 'message' => 'Purchase bill accepted successfully.']);
    }

    public function paymentHistory(PurchaseBill $purchaseBill)
    {
        $this->authorize('view purchase bills');

        $purchaseBill->load(['items.product', 'items.variant']);
        [$totalAmount] = $this->purchaseBillTotals($purchaseBill);
        $paidAmount = (float) ($purchaseBill->paid_amount ?? 0);
        $balanceDue = max(0.0, round($totalAmount - $paidAmount, 2));

        $payments = $purchaseBill->payments()->with('createdBy')->get()->map(function ($payment) {
            return [
                'amount' => format_price($payment->amount),
                'date'   => $payment->created_at->format('d M Y, h:i A'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_amount'    => format_price($totalAmount),
                'paid_amount'     => format_price($paidAmount),
                'balance_due'     => format_price($balanceDue),
                'balance_due_raw' => $balanceDue,
                'payments'        => $payments,
            ],
        ]);
    }

    public function updatePaymentStatus(Request $request, PurchaseBill $purchaseBill)
    {
        $this->authorize('edit purchase bills payment status');
        $this->guardLocationAccess($purchaseBill);

        if (!can_modify_past_date_record($purchaseBill->created_at)) {
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
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        if ($purchaseBill->status != PurchaseBill::STATUS_ACCEPTED) {
            return response()->json(['status' => 'error', 'message' => 'Only accepted purchase bills can have their payment status updated.'], 422);
        }

        $purchaseBill->load(['items.product', 'items.variant']);
        [$totalAmount] = $this->purchaseBillTotals($purchaseBill);
        $currentPaidAmount = (float) ($purchaseBill->paid_amount ?? 0);
        $balanceDue = round($totalAmount - $currentPaidAmount, 2);

        $newStatus = (int) $request->payment_status;
        $currentStatus = (int) ($purchaseBill->payment_status ?? PurchaseBill::PAYMENT_STATUS_PENDING);

        if ($newStatus === PurchaseBill::PAYMENT_STATUS_PARTIAL && round((float) $request->amount, 2) > $balanceDue) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paid amount cannot be greater than the remaining balance due (' . format_price($balanceDue) . ').',
            ], 422);
        }

        DB::transaction(function () use ($purchaseBill, $newStatus, $request, $totalAmount, $balanceDue, $currentPaidAmount) {
            if ($newStatus === PurchaseBill::PAYMENT_STATUS_PAID) {
                \App\Models\PurchaseBillPayment::create([
                    'purchase_bill_id' => $purchaseBill->id,
                    'amount'           => $balanceDue,
                    'created_by'       => auth()->id(),
                ]);

                $this->applyLocationBalanceTransfer($purchaseBill, $balanceDue, false);

                PurchaseBill::withoutActivityLogging(fn () => $purchaseBill->update([
                    'payment_status' => PurchaseBill::PAYMENT_STATUS_PAID,
                    'paid_amount'    => $totalAmount,
                ]));
            } elseif ($newStatus === PurchaseBill::PAYMENT_STATUS_PARTIAL) {
                $amount = (float) $request->amount;
                $newPaidAmount = round($currentPaidAmount + $amount, 2);
                $finalStatus = $newPaidAmount >= $totalAmount ? PurchaseBill::PAYMENT_STATUS_PAID : PurchaseBill::PAYMENT_STATUS_PARTIAL;

                \App\Models\PurchaseBillPayment::create([
                    'purchase_bill_id' => $purchaseBill->id,
                    'amount'           => $amount,
                    'created_by'       => auth()->id(),
                ]);

                $this->applyLocationBalanceTransfer($purchaseBill, $amount, false);

                PurchaseBill::withoutActivityLogging(fn () => $purchaseBill->update([
                    'payment_status' => $finalStatus,
                    'paid_amount'    => min($newPaidAmount, $totalAmount),
                ]));
            } else {
                $purchaseBill->payments()->delete();
                $this->applyLocationBalanceTransfer($purchaseBill, $currentPaidAmount, true);

                PurchaseBill::withoutActivityLogging(fn () => $purchaseBill->update([
                    'payment_status' => PurchaseBill::PAYMENT_STATUS_PENDING,
                    'paid_amount'    => 0,
                ]));
            }
        });

        $label = $newStatus === PurchaseBill::PAYMENT_STATUS_PAID ? 'Paid' : ($newStatus === PurchaseBill::PAYMENT_STATUS_PARTIAL ? 'Partially Paid' : 'Pending');

        ActivityLogger::log(
            'Purchase Bill',
            'update',
            $purchaseBill,
            ['payment_status' => $currentStatus, 'paid_amount' => $currentPaidAmount],
            ['payment_status' => (int) $purchaseBill->payment_status, 'paid_amount' => (float) $purchaseBill->paid_amount],
            'Purchase Bill #' . $purchaseBill->transfer_no . ' payment status updated to ' . $label
        );

        return response()->json(['status' => 'success', 'message' => 'Payment status updated to ' . $label . '.']);
    }

    public function reject(PurchaseBill $purchaseBill)
    {
        $this->authorize('reject purchase bills');
        $this->guardLocationAccess($purchaseBill);

        if ($purchaseBill->status != PurchaseBill::STATUS_PENDING) {
            return response()->json(['status' => 'error', 'message' => 'Only pending purchase bills can be rejected.'], 422);
        }

        PurchaseBill::withoutActivityLogging(fn () => $purchaseBill->update([
            'status' => PurchaseBill::STATUS_REJECTED,
            'accepted_by' => auth()->id(),
            'accepted_at' => now(),
        ]));

        ActivityLogger::log(
            'Purchase Bill',
            'update',
            $purchaseBill,
            ['status' => PurchaseBill::STATUS_PENDING],
            ['status' => PurchaseBill::STATUS_REJECTED],
            'Purchase Bill #' . $purchaseBill->transfer_no . ' rejected'
        );

        return response()->json(['status' => 'success', 'message' => 'Purchase bill rejected successfully.']);
    }

    private function normalizeItems(iterable $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int) (is_array($item) ? $item['product_id'] : $item->product_id);
            $variantId = is_array($item) ? ($item['product_variant_id'] ?? null) : $item->product_variant_id;
            $variantId = $variantId ? (int) $variantId : null;
            $quantity = (int) (is_array($item) ? $item['quantity'] : $item->quantity);
            $pairType = is_array($item) ? ($item['pair_type'] ?? 'single') : ($item->pair_type ?? 'single');
            $customSizeValue = is_array($item) ? ($item['custom_size_value'] ?? null) : ($item->custom_size_value ?? null);

            $key = $productId . ':' . ($variantId ?? 0) . ':' . $pairType . ':' . ($customSizeValue ?? '');

            if (!isset($normalized[$key])) {
                $normalized[$key] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'pair_type' => $pairType,
                    'custom_size_value' => $customSizeValue,
                    'quantity' => 0,
                ];
            }

            $normalized[$key]['quantity'] += $quantity;
        }

        return array_values($normalized);
    }

    private function getStockError(iterable $items, int $locationId): ?string
    {
        foreach ($this->normalizeItems($items) as $item) {
            $product = Product::with('variants.attributeValue.attribute')->find($item['product_id']);
            if (!$product) {
                return 'Selected product was not found.';
            }

            $label = $product->name;
            if ($item['product_variant_id']) {
                $variant = $product->variants->firstWhere('id', $item['product_variant_id']);
                if (!$variant) {
                    return 'Selected variant does not belong to product "' . $product->name . '".';
                }

                $stockData = $product->getVariantStock($locationId);
                $available = (int) ($stockData['variants'][$item['product_variant_id']] ?? 0);
                $label .= ' (' . ($variant->attributeValue->attribute->name ?? 'Variant') . ': ' . ($variant->attributeValue->value ?? $variant->id) . ')';
            } else {
                $available = (int) Inventory::where('product_id', $product->id)
                    ->where('location_id', $locationId)
                    ->value('quantity');
            }

            $multiplier = $this->stockMultiplierFor($product, $item['pair_type'], $item['custom_size_value']);
            $neededQty = (int) round($item['quantity'] * $multiplier);

            if ($available < $neededQty) {
                $cSize = $item['custom_size_value'] ? (float) $item['custom_size_value'] : null;
                $availFormatted = format_stock_quantity($product, $available, $cSize);
                $reqFormatted   = format_stock_quantity($product, $neededQty, $cSize);
                return 'Product "' . $label . '" only has ' . $availFormatted . ' in source stock; ' . $reqFormatted . ' requested.';
            }
        }

        return null;
    }

    private function guardLocationAccess(PurchaseBill $transfer, bool $destinationOnly = false): void
    {
        $user = auth()->user();
        if (!$user->location_id || $user->hasRole('super-admin')) {
            return;
        }

        $allowed = $destinationOnly
            ? (int) $transfer->to_location_id === (int) $user->location_id
            : in_array((int) $user->location_id, [(int) $transfer->from_location_id, (int) $transfer->to_location_id], true);

        if (!$allowed) {
            abort(403);
        }
    }

    private function defaultSourceLocation(): Location
    {
        $location = Location::where('is_default', true)->first() ?? Location::first();

        if (!$location) {
            throw new \RuntimeException('Please create a default location before creating purchase bills.');
        }

        return $location;
    }

    private function resolveSourceLocation($requestedId = null): Location
    {
        $user = auth()->user();
        
        if (!$user->hasRole('super-admin')) {
            if ($user->location_id) {
                $location = Location::where('id', $user->location_id)->where('status', 1)->first();

                if (!$location) {
                    throw new \RuntimeException('Your assigned location is unavailable. Please contact an administrator.');
                }

                return $location;
            }

            return $this->defaultSourceLocation();
        }

        if ($requestedId) {
            $location = Location::where('id', $requestedId)->where('status', 1)->first();
            if ($location) {
                return $location;
            }
        }

        return $this->defaultSourceLocation();
    }

    private function statusBadge(int $status): string
    {
        $colors = [
            PurchaseBill::STATUS_PENDING => 'bg-label-secondary',
            PurchaseBill::STATUS_ACCEPTED => 'bg-label-success',
            PurchaseBill::STATUS_REJECTED => 'bg-label-danger',
        ];
        $labels = [
            PurchaseBill::STATUS_PENDING => 'Pending',
            PurchaseBill::STATUS_ACCEPTED => 'Accepted',
            PurchaseBill::STATUS_REJECTED => 'Rejected',
        ];

        return '<span class="badge ' . ($colors[$status] ?? 'bg-label-secondary') . '">' . ($labels[$status] ?? 'Pending') . '</span>';
    }

    private function paymentStatusBadge(int $status): string
    {
        $colors = [
            PurchaseBill::PAYMENT_STATUS_PENDING => 'bg-label-warning',
            PurchaseBill::PAYMENT_STATUS_PAID    => 'bg-label-info',
            PurchaseBill::PAYMENT_STATUS_PARTIAL => 'bg-label-primary',
        ];
        $labels = [
            PurchaseBill::PAYMENT_STATUS_PENDING => 'Pending',
            PurchaseBill::PAYMENT_STATUS_PAID    => 'Paid',
            PurchaseBill::PAYMENT_STATUS_PARTIAL => 'Partially Paid',
        ];

        return '<span class="badge ' . ($colors[$status] ?? 'bg-label-warning') . '">' . ($labels[$status] ?? 'Pending') . '</span>';
    }

    private function applyLocationBalanceTransfer(PurchaseBill $purchaseBill, float $totalAmount, bool $reverse = false): void
    {
        if ($totalAmount <= 0) {
            return;
        }

        $balanceType = strtolower($purchaseBill->payment_method ?? 'cash') === 'online'
            ? \App\Models\LocationBalanceTransaction::BALANCE_TYPE_BANK
            : \App\Models\LocationBalanceTransaction::BALANCE_TYPE_CASH;

        $balanceCol = $balanceType === \App\Models\LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        $outNote = 'Purchase Bill Out #' . $purchaseBill->transfer_no;
        $inNote  = 'Purchase Bill In #' . $purchaseBill->transfer_no;

        if ($reverse) {
            // Revert balances for from and to locations
            $fromBalance = \App\Models\LocationBalance::where('location_id', $purchaseBill->from_location_id)->lockForUpdate()->first();
            if ($fromBalance) {
                $fromBalance->update([$balanceCol => (float) $fromBalance->{$balanceCol} - $totalAmount]);
            }
            $toBalance = \App\Models\LocationBalance::where('location_id', $purchaseBill->to_location_id)->lockForUpdate()->first();
            if ($toBalance) {
                $toBalance->update([$balanceCol => (float) $toBalance->{$balanceCol} + $totalAmount]);
            }

            // Remove existing transactions so no duplicate reversal lines are shown
            \App\Models\LocationBalanceTransaction::whereIn('notes', [$outNote, $inNote])
                ->orWhere('notes', 'LIKE', '%#' . $purchaseBill->transfer_no)
                ->delete();
            return;
        }

        $fromBalance = \App\Models\LocationBalance::where('location_id', $purchaseBill->from_location_id)->lockForUpdate()->firstOrFail();
        $newFromBalance = (float) $fromBalance->{$balanceCol} + $totalAmount;
        $fromBalance->update([$balanceCol => $newFromBalance]);

        $existingOut = \App\Models\LocationBalanceTransaction::where('notes', 'LIKE', '%' . $purchaseBill->transfer_no)->where('location_id', $purchaseBill->from_location_id)->first();
        if ($existingOut) {
            $existingOut->update([
                'location_id'   => $purchaseBill->from_location_id,
                'balance_type'  => $balanceType,
                'type'          => \App\Models\LocationBalanceTransaction::TYPE_CREDIT,
                'amount'        => $totalAmount,
                'balance_after' => $newFromBalance,
                'notes'         => $outNote,
            ]);
        } else {
            \App\Models\LocationBalanceTransaction::create([
                'location_id'   => $purchaseBill->from_location_id,
                'balance_type'  => $balanceType,
                'type'          => \App\Models\LocationBalanceTransaction::TYPE_CREDIT,
                'amount'        => $totalAmount,
                'balance_after' => $newFromBalance,
                'notes'         => $outNote,
                'created_by'    => auth()->id(),
            ]);
        }

        $toBalance = \App\Models\LocationBalance::where('location_id', $purchaseBill->to_location_id)->lockForUpdate()->firstOrFail();
        $newToBalance = (float) $toBalance->{$balanceCol} - $totalAmount;
        $toBalance->update([$balanceCol => $newToBalance]);

        $existingIn = \App\Models\LocationBalanceTransaction::where('notes', 'LIKE', '%' . $purchaseBill->transfer_no)->where('location_id', $purchaseBill->to_location_id)->first();
        if ($existingIn) {
            $existingIn->update([
                'location_id'   => $purchaseBill->to_location_id,
                'balance_type'  => $balanceType,
                'type'          => \App\Models\LocationBalanceTransaction::TYPE_DEBIT,
                'amount'        => $totalAmount,
                'balance_after' => $newToBalance,
                'notes'         => $inNote,
            ]);
        } else {
            \App\Models\LocationBalanceTransaction::create([
                'location_id'   => $purchaseBill->to_location_id,
                'balance_type'  => $balanceType,
                'type'          => \App\Models\LocationBalanceTransaction::TYPE_DEBIT,
                'amount'        => $totalAmount,
                'balance_after' => $newToBalance,
                'notes'         => $inNote,
                'created_by'    => auth()->id(),
            ]);
        }
    }

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

    private function stockMultiplierFor(Product $product, ?string $pairType, $customSizeValue = null): float
    {
        if ($customSizeValue !== null && $customSizeValue !== '' && (float)$customSizeValue > 0) {
            return (float) $customSizeValue;
        }

        if (!$product->pair_product) {
            return 1.0;
        }

        $customSizes = $product->custom_sizes;
        if (is_array($customSizes) && count($customSizes) > 0) {
            $sizes = collect($customSizes)->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            if ($sizes->count() > 0) {
                return (float) $sizes->max();
            }
        }

        return 2.0;
    }

    private function purchaseBillTotals(PurchaseBill $transfer): array
    {
        $totalAmount = 0.0;
        $totalMrp = 0.0;

        foreach ($transfer->items as $item) {
            $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type, $item->custom_size_value);
            $quantity = (int) $item->quantity;

            $totalAmount += $this->purchasePriceForPurchaseBillItem($item) * $quantity;

            $totalMrp += $this->mrpForPurchaseBillItem($item, $multiplier) * $quantity;
        }

        return [$totalAmount, $totalMrp];
    }

    private function purchasePriceForPurchaseBillItem(PurchaseBillItem $item): float
    {
        $product = $item->product;
        $basePrice = (float) (($item->purchase_price > 0) ? $item->purchase_price : ($item->variant->purchase_price ?? $product?->purchase_price ?? 0));

        if (!$product || !$product->pair_product) {
            return $basePrice;
        }

        $selectedSize = (float) $item->custom_size_value;
        if ($selectedSize <= 0) {
            return $basePrice;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        $maxSize = collect($sizes)
            ->pluck('size')
            ->map(fn ($size) => (float) $size)
            ->filter(fn ($size) => $size > 0)
            ->max();

        if (!$maxSize || $maxSize <= 0) {
            return $basePrice;
        }

        return (float) ($basePrice * ($selectedSize / (float) $maxSize));
    }

    private function mrpForPurchaseBillItem(PurchaseBillItem $item, float $multiplier): float
    {
        $product = $item->product;
        if (!$product) {
            return 0.0;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        if (!empty($sizes)) {
            $value = (float) $item->custom_size_value;
            $matched = null;

            if ($value > 0) {
                $matched = collect($sizes)->first(fn ($row) => abs((float) ($row['size'] ?? 0) - $value) < 0.001);
            }

            if (!$matched) {
                $matched = collect($sizes)->sortBy(fn ($row) => (float) ($row['size'] ?? 0))->last();
            }

            if ($matched && isset($matched['mrp']) && is_numeric($matched['mrp'])) {
                return (float) $matched['mrp'];
            }
        }

        return (float) ($product->mrp ?? 0);
    }

    public function getMappedProductsJson()
    {
        return response()->json($this->getMappedProductsForPurchaseBills());
    }

    private function getMappedProductsForPurchaseBills(): array
    {
        return Cache::store('file')->remember('all_mapped_products_bills', 1800, function () {
            $products = Product::with([
                'variants.attributeValue.attribute',
                'primaryImage',
            ])->where('status', 1)->orderBy('name')->get();

            return $products->map(function ($p) {
                $data = [
                    'id'             => $p->id,
                    'name'           => $p->name,
                    'barcode'        => $p->barcode,
                    'type'           => $p->type,
                    'pair_product'   => (bool) $p->pair_product,
                    'custom_sizes'   => $p->custom_sizes ?? [],
                    'purchase_price' => $p->purchase_price,
                    'image'          => $p->primary_image_url,
                ];
                if ($p->type === 'variable') {
                    $data['variants'] = $p->variants->filter(fn ($v) => $v->status == 1)->values()->map(function ($v) {
                        return [
                            'id'             => $v->id,
                            'purchase_price' => $v->purchase_price,
                            'custom_sizes'   => $v->custom_sizes ?? [],
                            'attr_name'      => $v->attributeValue->attribute->name ?? '',
                            'value_name'     => $v->attributeValue->value ?? '',
                        ];
                    })->all();
                }
                return $data;
            })->values()->all();
        });
    }
}
