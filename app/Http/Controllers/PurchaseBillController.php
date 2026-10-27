<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use App\Services\ActivityLogger;
use App\Services\PurchaseBatchService;
use App\Services\PurchaseStockService;
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
        } elseif ($sortKey === 'transfer_no') {
            $query->orderByRaw("LENGTH(purchase_bills.transfer_no) {$sortDir}")
                  ->orderBy("purchase_bills.transfer_no", $sortDir);
        } elseif ($sortKey === 'created_by') {
            $query->leftJoin('users as u', 'purchase_bills.created_by', '=', 'u.id')
                  ->select('purchase_bills.*')
                  ->orderBy('u.name', $sortDir);
        } elseif ($sortKey === 'items_count') {
            $query->orderByRaw('(SELECT COALESCE(SUM(quantity), 0) FROM purchase_bill_items WHERE purchase_bill_items.purchase_bill_id = purchase_bills.id) ' . $sortDir);
        } elseif ($sortKey === 'total_amount') {
            $query->orderByRaw('(SELECT COALESCE(SUM(pbi.quantity * COALESCE(pbi.purchase_price, 0)), 0) FROM purchase_bill_items pbi WHERE pbi.purchase_bill_id = purchase_bills.id) ' . $sortDir);
        } elseif ($sortKey === 'total_mrp') {
            $query->orderByRaw('(SELECT COALESCE(SUM(pbi.quantity * COALESCE(pbi.mrp, 0)), 0) FROM purchase_bill_items pbi WHERE pbi.purchase_bill_id = purchase_bills.id) ' . $sortDir);
        } else {
            $query->orderBy("purchase_bills.{$sortKey}", $sortDir);
        }
        $query->orderBy('purchase_bills.id', 'desc');

        if (in_array($sortKey, ['total_mrp', 'total_amount', 'items_count'])) {
            $transfers = (clone $query)
                ->with(['fromLocation', 'toLocation', 'createdBy', 'items.product', 'items.variant'])
                ->get();
        } else {
            $transfers = (clone $query)
                ->with(['fromLocation', 'toLocation', 'createdBy', 'items.product', 'items.variant'])
                ->skip($start)
                ->take($length)
                ->get();
        }

        $allFilteredBills = (clone $query)->with(['items.product', 'items.variant'])->get();
        $grandTotalAmount = 0.0;
        $grandTotalMrp    = 0.0;

        foreach ($allFilteredBills as $tbill) {
            [$tAmount, $tMrp] = $this->purchaseBillTotals($tbill);
            $grandTotalAmount += $tAmount;
            $grandTotalMrp    += $tMrp;
        }

        $canAccept = auth()->user()->can('accept purchase bills');
        $canReject = auth()->user()->can('reject purchase bills');
        $canEditPaymentStatus = auth()->user()->can('edit purchase bills payment status');
        $canEdit = auth()->user()->can('edit purchase bills');
        $canDelete = auth()->user()->hasRole('super-admin')
            || auth()->user()->can('delete purchase bills')
            || auth()->user()->can('edit purchase bills');

        $user = auth()->user();

        $data = $transfers->map(function ($transfer, $index) use ($user, $canAccept, $canReject, $canEditPaymentStatus, $canEdit, $canDelete) {
            $canEditRecord = $canEdit && can_modify_past_date_record($transfer->created_at);
            $canDeleteRecord = $canDelete && can_modify_past_date_record($transfer->created_at);
            $isDestinationUserOrAdmin = !$user->location_id || $user->hasRole('super-admin') || (int) $transfer->to_location_id === (int) $user->location_id;
            $canAcceptRecord = $canAccept && $isDestinationUserOrAdmin;
            $canRejectRecord = $canReject && $isDestinationUserOrAdmin;
            $canPaymentStatusRecord = $canEditPaymentStatus;

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
                if ($canDeleteRecord) {
                    $actions .= '<button class="dropdown-item text-danger purchase-bill-action" data-url="' . route('admin.purchase-bills.destroy', $transfer) . '" data-method="DELETE" data-title="Delete Purchase Bill" data-text="Are you sure you want to delete this pending purchase bill?"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
            } elseif ($transfer->status == PurchaseBill::STATUS_ACCEPTED) {
                $canModifyAccepted = PurchaseStockService::canEditOrDeletePurchaseBill($transfer);
                if ($canModifyAccepted) {
                    if ($canEditRecord) {
                        $actions .= '<a href="' . route('admin.purchase-bills.edit', $transfer) . '" class="dropdown-item"><i class="ti ti-pencil me-2"></i>Edit</a>';
                    }
                    if ($canDeleteRecord) {
                        $actions .= '<button class="dropdown-item text-danger purchase-bill-action" data-url="' . route('admin.purchase-bills.destroy', $transfer) . '" data-method="DELETE" data-title="Delete Purchase Bill" data-text="Transferred stock will be returned back to source branch. Are you sure you want to delete this accepted purchase bill?"><i class="ti ti-trash me-2"></i>Delete</button>';
                    }
                }
            }
            if ($canPaymentStatusRecord && $transfer->status == PurchaseBill::STATUS_ACCEPTED && (int) ($transfer->payment_status ?? PurchaseBill::PAYMENT_STATUS_PENDING) !== PurchaseBill::PAYMENT_STATUS_PAID) {
                $actions .= '<button class="dropdown-item change-purchase-bill-payment-status-btn" data-url="' . route('admin.purchase-bills.update-payment-status', $transfer) . '" data-history-url="' . route('admin.purchase-bills.payment-history', $transfer) . '" data-current="' . ((int) ($transfer->payment_status ?? PurchaseBill::PAYMENT_STATUS_PENDING)) . '"><i class="ti ti-credit-card me-2"></i>Update Payment Status</button>';
            }
            $actions .= '</div></div>';

            return [
                'index'            => 0,
                'transfer_no'      => '<code>' . e($transfer->transfer_no) . '</code>',
                'from_location'    => e($transfer->fromLocation->name ?? '-'),
                'to_location'      => e($transfer->toLocation->name ?? '-'),
                'items_count'      => (int) $transfer->items->sum('quantity'),
                'total_amount'     => format_price($totalAmount),
                'total_amount_raw' => (float) round($totalAmount, 2),
                'total_mrp'        => format_price($totalMrp),
                'total_mrp_raw'    => (float) round($totalMrp, 2),
                'status'           => $statusBadge,
                'payment_status'   => $paymentStatusBadge,
                'created_by'       => e($transfer->createdBy->name ?? '-'),
                'date_group'       => $transfer->created_at->format('d M Y'),
                'date_sort'        => $transfer->created_at->format('YmdHis'),
                'actions'          => $actions,
            ];
        });

        if ($sortKey === 'total_mrp') {
            $data = $sortDir === 'asc' 
                ? $data->sortBy('total_mrp_raw')->values() 
                : $data->sortByDesc('total_mrp_raw')->values();
        } elseif ($sortKey === 'total_amount') {
            $data = $sortDir === 'asc' 
                ? $data->sortBy('total_amount_raw')->values() 
                : $data->sortByDesc('total_amount_raw')->values();
        } elseif ($sortKey === 'items_count') {
            $data = $sortDir === 'asc' 
                ? $data->sortBy('items_count')->values() 
                : $data->sortByDesc('items_count')->values();
        }

        if (in_array($sortKey, ['total_mrp', 'total_amount', 'items_count'])) {
            $data = $data->slice($start, $length)->values();
        }

        $data = $data->map(function ($item, $idx) use ($start) {
            $item['index'] = $start + $idx + 1;
            return $item;
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
                $itemMrp = $variantObj ? $variantObj->mrp : ($product ? $product->mrp : null);

                $unitPrice = (isset($item['purchase_price']) && is_numeric($item['purchase_price']) && (float)$item['purchase_price'] > 0)
                    ? (float) $item['purchase_price']
                    : ($variantObj ? (float)$variantObj->purchase_price : ($product ? (float)$product->purchase_price : 0));

                if ($unitPrice <= 0) {
                    $sourceBatchPrice = DB::table('purchase_batch_stocks')
                        ->where('location_id', $defaultLocation->id)
                        ->where('product_id', $item['product_id'])
                        ->when(!empty($item['product_variant_id']), fn($q) => $q->where('product_variant_id', $item['product_variant_id']), fn($q) => $q->whereNull('product_variant_id'))
                        ->where('quantity', '>', 0)
                        ->where('purchase_price', '>', 0)
                        ->orderBy('id', 'desc')
                        ->value('purchase_price');

                    if ($sourceBatchPrice !== null && (float)$sourceBatchPrice > 0) {
                        $unitPrice = (float) $sourceBatchPrice;
                    } else {
                        $unitPrice = PurchaseBatchService::resolveFallbackPurchasePrice((int)$item['product_id'], !empty($item['product_variant_id']) ? (int)$item['product_variant_id'] : null);
                    }
                }

                PurchaseBillItem::create([
                    'purchase_bill_id'   => $transfer->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'pair_type'          => $item['pair_type'] ?? 'single',
                    'custom_size_value'  => $customSizeVal,
                    'purchase_price'     => $unitPrice,
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

        if ($purchaseBill->status == PurchaseBill::STATUS_ACCEPTED) {
            if (!PurchaseStockService::canEditOrDeletePurchaseBill($purchaseBill)) {
                return redirect()->route('admin.purchase-bills.show', $purchaseBill)->with('error', 'This accepted purchase bill cannot be edited because transferred items have already been sold in the destination branch.');
            }
        } elseif ($purchaseBill->status != PurchaseBill::STATUS_PENDING) {
            return redirect()->route('admin.purchase-bills.show', $purchaseBill)->with('error', 'Only pending or unsold accepted purchase bills can be edited.');
        }

        $user = auth()->user();
        $isAccepted = ($purchaseBill->status == PurchaseBill::STATUS_ACCEPTED);
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
                'purchase_price' => (float) $item->purchase_price,
                'purchase_item_id' => $item->purchase_item_id ?? null,
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

        return view('purchase-bills.edit', compact('purchaseBill', 'defaultLocation', 'sourceLocations', 'canChooseSource', 'destinationLocations', 'existingItems', 'isAccepted'));
    }

    public function update(Request $request, PurchaseBill $purchaseBill)
    {
        $this->authorize('edit purchase bills');
        $this->guardLocationAccess($purchaseBill);

        if (!can_modify_past_date_record($purchaseBill->created_at)) {
            return response()->json(['status' => 'error', 'message' => 'You do not have permission to edit past date records.'], 403);
        }

        $isAccepted = ($purchaseBill->status == PurchaseBill::STATUS_ACCEPTED);
        if ($isAccepted) {
            if (!PurchaseStockService::canEditOrDeletePurchaseBill($purchaseBill)) {
                return response()->json(['status' => 'error', 'message' => 'This accepted purchase bill cannot be edited because transferred items have already been sold in the destination branch.'], 422);
            }
        } elseif ($purchaseBill->status != PurchaseBill::STATUS_PENDING) {
            return response()->json(['status' => 'error', 'message' => 'Only pending or unsold accepted purchase bills can be edited.'], 422);
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

        if ($isAccepted) {
            $deltaStockError = $this->getAcceptedStockDeltaError($purchaseBill, (int) $fromLocation->id, (int) $request->to_location_id, $request->items);
            if ($deltaStockError) {
                return response()->json(['status' => 'error', 'message' => ['items' => [$deltaStockError]]], 422);
            }
        } else {
            $stockError = $this->getStockError($request->items, (int) $fromLocation->id);
            if ($stockError) {
                return response()->json(['status' => 'error', 'message' => ['items' => [$stockError]]], 422);
            }
        }

        DB::transaction(function () use ($request, $fromLocation, $purchaseBill, $isAccepted) {
            $oldItems = $purchaseBill->items()->get();
            $oldItemsSnapshot = $oldItems->map(function ($item) {
                return [
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                    'purchase_price'     => $item->purchase_price,
                ];
            })->values()->all();

            $normalizedNewItems = $this->normalizeItems($request->items);

            $oldFromLocId = (int) $purchaseBill->from_location_id;
            $oldToLocId   = (int) $purchaseBill->to_location_id;
            $newFromLocId = (int) $fromLocation->id;
            $newToLocId   = (int) $request->to_location_id;
            $branchChanged = ($oldFromLocId !== $newFromLocId || $oldToLocId !== $newToLocId);

            $itemsChanged = false;
            if ($oldItems->count() !== count($normalizedNewItems)) {
                $itemsChanged = true;
            } else {
                $oldNorm = $oldItems->map(fn($item) => [
                    'product_id'         => (int) $item->product_id,
                    'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                    'pair_type'          => $item->pair_type ?? 'single',
                    'custom_size_value'  => $item->custom_size_value !== null ? (string) $item->custom_size_value : null,
                    'quantity'           => (int) $item->quantity,
                    'purchase_price'     => round((float) ($item->purchase_price ?? 0), 2),
                ])->sortBy(['product_id', 'product_variant_id', 'pair_type', 'custom_size_value', 'quantity', 'purchase_price'])->values()->all();

                $newNorm = collect($normalizedNewItems)->map(function ($item) use ($fromLocation) {
                    $product = Product::find($item['product_id']);
                    $customSizeVal = $this->resolveCustomSizeValue($product, $item);
                    $variantObj = !empty($item['product_variant_id']) ? ProductVariant::find($item['product_variant_id']) : null;

                    $unitPrice = (isset($item['purchase_price']) && is_numeric($item['purchase_price']) && (float)$item['purchase_price'] > 0)
                        ? (float) $item['purchase_price']
                        : ($variantObj ? (float)$variantObj->purchase_price : ($product ? (float)$product->purchase_price : 0));

                    if ($unitPrice <= 0) {
                        $sourceBatchPrice = DB::table('purchase_batch_stocks')
                            ->where('location_id', $fromLocation->id)
                            ->where('product_id', $item['product_id'])
                            ->when(!empty($item['product_variant_id']), fn($q) => $q->where('product_variant_id', $item['product_variant_id']), fn($q) => $q->whereNull('product_variant_id'))
                            ->where('quantity', '>', 0)
                            ->where('purchase_price', '>', 0)
                            ->orderBy('id', 'desc')
                            ->value('purchase_price');

                        if ($sourceBatchPrice !== null && (float)$sourceBatchPrice > 0) {
                            $unitPrice = (float) $sourceBatchPrice;
                        } else {
                            $unitPrice = PurchaseBatchService::resolveFallbackPurchasePrice((int)$item['product_id'], !empty($item['product_variant_id']) ? (int)$item['product_variant_id'] : null);
                        }
                    }

                    return [
                        'product_id'         => (int) $item['product_id'],
                        'product_variant_id' => !empty($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                        'pair_type'          => $item['pair_type'] ?? 'single',
                        'custom_size_value'  => $customSizeVal !== null ? (string) $customSizeVal : null,
                        'quantity'           => (int) $item['quantity'],
                        'purchase_price'     => round((float) $unitPrice, 2),
                    ];
                })->sortBy(['product_id', 'product_variant_id', 'pair_type', 'custom_size_value', 'quantity', 'purchase_price'])->values()->all();

                if ($oldNorm != $newNorm) {
                    $itemsChanged = true;
                }
            }

            if ($isAccepted) {
                if ($branchChanged) {
                    $oldFromLocName = $purchaseBill->fromLocation?->name ?? 'Source Branch';
                    $oldToLocName   = $purchaseBill->toLocation?->name ?? 'Destination Branch';
                    $newFromLocName = $fromLocation->name ?? 'New Source Branch';
                    $newToLoc = Location::find($newToLocId);
                    $newToLocName   = $newToLoc?->name ?? 'New Destination Branch';

                    foreach ($oldItems as $oi) {
                        $prod = $oi->product ?? Product::find($oi->product_id);
                        $mult = $this->stockMultiplierFor($prod, $oi->pair_type, $oi->custom_size_value);
                        $qty = (int) round($oi->quantity * $mult);
                        $pId = (int) $oi->product_id;
                        $vId = $oi->product_variant_id ? (int) $oi->product_variant_id : null;
                        $price = (float) $oi->purchase_price;

                        $destInv = Inventory::where('product_id', $pId)->where('location_id', $oldToLocId)->first();
                        if ($destInv) {
                            $oldDestQty = $destInv->quantity;
                            $destInv->decrement('quantity', $qty);
                            ActivityLogger::log('Inventory', 'update', $destInv, ['quantity' => $oldDestQty], ['quantity' => $oldDestQty - $qty], 'Stock returned from ' . $oldToLocName . ' on purchase bill #' . $purchaseBill->transfer_no . ' edit');
                        }

                        $srcInv = Inventory::firstOrCreate(
                            ['product_id' => $pId, 'location_id' => $oldFromLocId],
                            ['quantity' => 0, 'created_by' => auth()->id()]
                        );
                        $oldSrcQty = $srcInv->quantity;
                        $srcInv->increment('quantity', $qty);
                        ActivityLogger::log('Inventory', 'update', $srcInv, ['quantity' => $oldSrcQty], ['quantity' => $oldSrcQty + $qty], 'Stock restored to ' . $oldFromLocName . ' on purchase bill #' . $purchaseBill->transfer_no . ' edit');

                        PurchaseBatchService::deductBatchStock($oldToLocId, $pId, $vId, $price, (float) $qty);
                        PurchaseBatchService::addBatchStock($oldFromLocId, $pId, $vId, null, $price, (float) $qty);
                        PurchaseBatchService::syncProductBatchStocks($oldToLocId, $pId, $vId);
                        PurchaseBatchService::syncProductBatchStocks($oldFromLocId, $pId, $vId);
                    }

                    if ((float) ($purchaseBill->paid_amount ?? 0) > 0) {
                        $this->applyLocationBalanceTransfer($purchaseBill, (float) $purchaseBill->paid_amount, true);
                    }

                    foreach ($normalizedNewItems as $ni) {
                        $prod = Product::find($ni['product_id']);
                        $customSizeVal = $this->resolveCustomSizeValue($prod, $ni);
                        $mult = $this->stockMultiplierFor($prod, $ni['pair_type'] ?? null, $customSizeVal);
                        $qty = (int) round($ni['quantity'] * $mult);
                        $pId = (int) $ni['product_id'];
                        $vId = !empty($ni['product_variant_id']) ? (int) $ni['product_variant_id'] : null;

                        $variantObj = $vId ? ProductVariant::find($vId) : null;
                        $unitPrice = (isset($ni['purchase_price']) && is_numeric($ni['purchase_price']) && (float)$ni['purchase_price'] > 0)
                            ? (float) $ni['purchase_price']
                            : ($variantObj ? (float)$variantObj->purchase_price : ($prod ? (float)$prod->purchase_price : 0));
                        if ($unitPrice <= 0) {
                            $sourceBatchPrice = DB::table('purchase_batch_stocks')
                                ->where('location_id', $newFromLocId)
                                ->where('product_id', $pId)
                                ->when($vId, fn($q) => $q->where('product_variant_id', $vId), fn($q) => $q->whereNull('product_variant_id'))
                                ->where('quantity', '>', 0)
                                ->where('purchase_price', '>', 0)
                                ->orderBy('id', 'desc')
                                ->value('purchase_price');
                            if ($sourceBatchPrice !== null && (float)$sourceBatchPrice > 0) {
                                $unitPrice = (float) $sourceBatchPrice;
                            } else {
                                $unitPrice = PurchaseBatchService::resolveFallbackPurchasePrice($pId, $vId);
                            }
                        }

                        $srcInv = Inventory::firstOrCreate(
                            ['product_id' => $pId, 'location_id' => $newFromLocId],
                            ['quantity' => 0, 'created_by' => auth()->id()]
                        );
                        $oldSrcQty = $srcInv->quantity;
                        $srcInv->decrement('quantity', $qty);
                        ActivityLogger::log('Inventory', 'update', $srcInv, ['quantity' => $oldSrcQty], ['quantity' => $oldSrcQty - $qty], 'Stock moved out from ' . $newFromLocName . ' to ' . $newToLocName . ' on purchase bill #' . $purchaseBill->transfer_no . ' edit');

                        $destInv = Inventory::firstOrCreate(
                            ['product_id' => $pId, 'location_id' => $newToLocId],
                            ['quantity' => 0, 'created_by' => auth()->id()]
                        );
                        $oldDestQty = $destInv->quantity;
                        $destInv->increment('quantity', $qty);
                        ActivityLogger::log('Inventory', 'update', $destInv, ['quantity' => $oldDestQty], ['quantity' => $oldDestQty + $qty], 'Stock moved in to ' . $newToLocName . ' from ' . $newFromLocName . ' on purchase bill #' . $purchaseBill->transfer_no . ' edit');

                        PurchaseBatchService::deductBatchStock($newFromLocId, $pId, $vId, (float)$unitPrice, (float)$qty);
                        PurchaseBatchService::addBatchStock($newToLocId, $pId, $vId, null, (float)$unitPrice, (float)$qty);
                        PurchaseBatchService::syncProductBatchStocks($newFromLocId, $pId, $vId);
                        PurchaseBatchService::syncProductBatchStocks($newToLocId, $pId, $vId);
                    }
                } elseif ($itemsChanged) {
                    $fromLocId = $oldFromLocId;
                    $toLocId   = $oldToLocId;
                    $fromLocName = $purchaseBill->fromLocation?->name ?? 'Source Branch';
                    $toLocName   = $purchaseBill->toLocation?->name ?? 'Destination Branch';

                    $oldMap = [];
                    $oldPriceMap = [];
                    foreach ($oldItems as $oi) {
                        $prod = $oi->product ?? Product::find($oi->product_id);
                        $mult = $this->stockMultiplierFor($prod, $oi->pair_type, $oi->custom_size_value);
                        $k = $oi->product_id . '_' . ($oi->product_variant_id ?: 0);
                        $oldMap[$k] = ($oldMap[$k] ?? 0) + (int) round($oi->quantity * $mult);
                        $oldPriceMap[$k] = (float) $oi->purchase_price;
                    }

                    $newMap = [];
                    $newPriceMap = [];
                    $prodObjs = [];
                    foreach ($normalizedNewItems as $ni) {
                        $prod = Product::find($ni['product_id']);
                        $customSizeVal = $this->resolveCustomSizeValue($prod, $ni);
                        $mult = $this->stockMultiplierFor($prod, $ni['pair_type'] ?? null, $customSizeVal);
                        $k = $ni['product_id'] . '_' . ($ni['product_variant_id'] ?: 0);
                        $newMap[$k] = ($newMap[$k] ?? 0) + (int) round($ni['quantity'] * $mult);
                        $prodObjs[$k] = ['product' => $prod, 'variant_id' => $ni['product_variant_id'] ?: null];

                        $variantObj = !empty($ni['product_variant_id']) ? ProductVariant::find($ni['product_variant_id']) : null;
                        $unitPrice = (isset($ni['purchase_price']) && is_numeric($ni['purchase_price']) && (float)$ni['purchase_price'] > 0)
                            ? (float) $ni['purchase_price']
                            : ($variantObj ? (float)$variantObj->purchase_price : ($prod ? (float)$prod->purchase_price : 0));
                        if ($unitPrice <= 0) {
                            $unitPrice = PurchaseBatchService::resolveFallbackPurchasePrice((int)$ni['product_id'], !empty($ni['product_variant_id']) ? (int)$ni['product_variant_id'] : null);
                        }
                        $newPriceMap[$k] = $unitPrice;
                    }

                    $allKeys = array_unique(array_merge(array_keys($oldMap), array_keys($newMap)));
                    foreach ($allKeys as $k) {
                        $oldQty = $oldMap[$k] ?? 0;
                        $newQty = $newMap[$k] ?? 0;
                        $delta = $newQty - $oldQty;
                        if ($delta == 0) continue;

                        [$pId, $vId] = explode('_', $k);
                        $variantId = $vId > 0 ? (int)$vId : null;
                        $prod = $prodObjs[$k]['product'] ?? Product::find($pId);
                        $price = $newPriceMap[$k] ?? ($oldPriceMap[$k] ?? 0);
                        $prodLabel = $prod ? ($prod->name . ($prod->barcode ? ' (' . $prod->barcode . ')' : '')) : "Product #{$pId}";

                        if ($delta > 0) {
                            $srcInv = Inventory::firstOrCreate(
                                ['product_id' => $pId, 'location_id' => $fromLocId],
                                ['quantity' => 0, 'created_by' => auth()->id()]
                            );
                            $oldSrcQty = $srcInv->quantity;
                            $srcInv->decrement('quantity', $delta);
                            ActivityLogger::log('Inventory', 'update', $srcInv, ['quantity' => $oldSrcQty], ['quantity' => $oldSrcQty - $delta], 'Stock moved out from ' . $fromLocName . ' to ' . $toLocName . ' on purchase bill #' . $purchaseBill->transfer_no . ' edit [' . $prodLabel . ']');

                            $destInv = Inventory::firstOrCreate(
                                ['product_id' => $pId, 'location_id' => $toLocId],
                                ['quantity' => 0, 'created_by' => auth()->id()]
                            );
                            $oldDestQty = $destInv->quantity;
                            $destInv->increment('quantity', $delta);
                            ActivityLogger::log('Inventory', 'update', $destInv, ['quantity' => $oldDestQty], ['quantity' => $oldDestQty + $delta], 'Stock moved in to ' . $toLocName . ' from ' . $fromLocName . ' on purchase bill #' . $purchaseBill->transfer_no . ' edit [' . $prodLabel . ']');

                            PurchaseBatchService::deductBatchStock($fromLocId, (int)$pId, $variantId, (float)$price, (float)$delta);
                            PurchaseBatchService::addBatchStock($toLocId, (int)$pId, $variantId, null, (float)$price, (float)$delta);
                        } else {
                            $absDelta = abs($delta);
                            $destInv = Inventory::where('product_id', $pId)->where('location_id', $toLocId)->first();
                            $oldDestQty = $destInv->quantity;
                            $destInv->decrement('quantity', $absDelta);
                            ActivityLogger::log('Inventory', 'update', $destInv, ['quantity' => $oldDestQty], ['quantity' => $oldDestQty - $absDelta], 'Stock returned from ' . $toLocName . ' to ' . $fromLocName . ' on purchase bill #' . $purchaseBill->transfer_no . ' edit [' . $prodLabel . ']');

                            $srcInv = Inventory::firstOrCreate(
                                ['product_id' => $pId, 'location_id' => $fromLocId],
                                ['quantity' => 0, 'created_by' => auth()->id()]
                            );
                            $oldSrcQty = $srcInv->quantity;
                            $srcInv->increment('quantity', $absDelta);
                            ActivityLogger::log('Inventory', 'update', $srcInv, ['quantity' => $oldSrcQty], ['quantity' => $oldSrcQty + $absDelta], 'Stock restored to ' . $fromLocName . ' from ' . $toLocName . ' on purchase bill #' . $purchaseBill->transfer_no . ' edit [' . $prodLabel . ']');

                            PurchaseBatchService::deductBatchStock($toLocId, (int)$pId, $variantId, (float)$price, (float)$absDelta);
                            PurchaseBatchService::addBatchStock($fromLocId, (int)$pId, $variantId, null, (float)$price, (float)$absDelta);
                        }

                        PurchaseBatchService::syncProductBatchStocks($fromLocId, (int)$pId, $variantId);
                        PurchaseBatchService::syncProductBatchStocks($toLocId, (int)$pId, $variantId);
                    }
                }
            }

            $updateData = [
                'from_location_id' => $fromLocation->id,
                'to_location_id' => $request->to_location_id,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_status ?? PurchaseBill::PAYMENT_STATUS_PENDING,
                'remarks' => $request->remarks,
            ];
            $oldFieldsSnapshot = $purchaseBill->only(array_keys($updateData));

            $fieldsDiff = array_udiff_assoc($updateData, $oldFieldsSnapshot, function ($a, $b) {
                if (is_numeric($a) && is_numeric($b)) {
                    return abs((float)$a - (float)$b) < 0.001 ? 0 : 1;
                }
                return (string)$a === (string)$b ? 0 : 1;
            });

            if (!empty($fieldsDiff)) {
                PurchaseBill::withoutActivityLogging(fn () => $purchaseBill->update($updateData));
            }

            $newItemsSnapshot = $oldItemsSnapshot;
            if ($itemsChanged || $branchChanged) {
                $purchaseBill->items()->delete();

                $newItemsSnapshot = [];
                foreach ($normalizedNewItems as $item) {
                    $product = Product::find($item['product_id']);
                    $customSizeVal = $this->resolveCustomSizeValue($product, $item);
                    $variantObj = !empty($item['product_variant_id']) ? ProductVariant::find($item['product_variant_id']) : null;
                    $itemMrp = $variantObj ? $variantObj->mrp : ($product ? $product->mrp : null);

                    $unitPrice = (isset($item['purchase_price']) && is_numeric($item['purchase_price']) && (float)$item['purchase_price'] > 0)
                        ? (float) $item['purchase_price']
                        : ($variantObj ? (float)$variantObj->purchase_price : ($product ? (float)$product->purchase_price : 0));

                    if ($unitPrice <= 0) {
                        $sourceBatchPrice = DB::table('purchase_batch_stocks')
                            ->where('location_id', $fromLocation->id)
                            ->where('product_id', $item['product_id'])
                            ->when(!empty($item['product_variant_id']), fn($q) => $q->where('product_variant_id', $item['product_variant_id']), fn($q) => $q->whereNull('product_variant_id'))
                            ->where('quantity', '>', 0)
                            ->where('purchase_price', '>', 0)
                            ->orderBy('id', 'desc')
                            ->value('purchase_price');

                        if ($sourceBatchPrice !== null && (float)$sourceBatchPrice > 0) {
                            $unitPrice = (float) $sourceBatchPrice;
                        } else {
                            $unitPrice = PurchaseBatchService::resolveFallbackPurchasePrice((int)$item['product_id'], !empty($item['product_variant_id']) ? (int)$item['product_variant_id'] : null);
                        }
                    }

                    PurchaseBillItem::create([
                        'purchase_bill_id'   => $purchaseBill->id,
                        'product_id'         => $item['product_id'],
                        'product_variant_id' => $item['product_variant_id'],
                        'pair_type'          => $item['pair_type'] ?? 'single',
                        'custom_size_value'  => $customSizeVal,
                        'purchase_price'     => $unitPrice,
                        'mrp'                => $itemMrp,
                        'quantity'           => $item['quantity'],
                    ]);

                    $newItemsSnapshot[] = [
                        'product_id'         => $item['product_id'],
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity'           => $item['quantity'],
                        'purchase_price'     => $unitPrice,
                    ];
                }
            }

            if ($isAccepted) {
                $purchaseBill->load(['items.product', 'items.variant']);
                [$newTotalAmount] = $this->purchaseBillTotals($purchaseBill);
                $oldPaidAmount = (float) ($purchaseBill->paid_amount ?? 0);

                if ($branchChanged) {
                    if ($purchaseBill->payment_status == PurchaseBill::PAYMENT_STATUS_PAID) {
                        $this->applyLocationBalanceTransfer($purchaseBill, $newTotalAmount);
                        $purchaseBill->update(['paid_amount' => $newTotalAmount]);
                    } elseif ($purchaseBill->payment_status == PurchaseBill::PAYMENT_STATUS_PARTIAL) {
                        $paid = min($oldPaidAmount, $newTotalAmount);
                        if ($paid > 0) {
                            $this->applyLocationBalanceTransfer($purchaseBill, $paid);
                        }
                        $purchaseBill->update([
                            'paid_amount'    => $paid,
                            'payment_status' => ($paid >= $newTotalAmount && $newTotalAmount > 0) ? PurchaseBill::PAYMENT_STATUS_PAID : PurchaseBill::PAYMENT_STATUS_PARTIAL,
                        ]);
                    }
                } else {
                    if ($purchaseBill->payment_status == PurchaseBill::PAYMENT_STATUS_PAID) {
                        $diffAmount = round($newTotalAmount - $oldPaidAmount, 2);
                        if ($diffAmount > 0) {
                            $this->applyLocationBalanceTransfer($purchaseBill, $diffAmount);
                        } elseif ($diffAmount < 0) {
                            $this->applyLocationBalanceTransfer($purchaseBill, abs($diffAmount), true);
                        }
                        $purchaseBill->update(['paid_amount' => $newTotalAmount]);
                    } elseif ($purchaseBill->payment_status == PurchaseBill::PAYMENT_STATUS_PARTIAL) {
                        if ($oldPaidAmount > $newTotalAmount) {
                            $excess = round($oldPaidAmount - $newTotalAmount, 2);
                            $this->applyLocationBalanceTransfer($purchaseBill, $excess, true);
                            $purchaseBill->update([
                                'paid_amount'    => $newTotalAmount,
                                'payment_status' => PurchaseBill::PAYMENT_STATUS_PAID,
                            ]);
                        }
                    }
                }
            }

            if ($itemsChanged || !empty($fieldsDiff) || $branchChanged) {
                ActivityLogger::log(
                    'Purchase Bill',
                    'update',
                    $purchaseBill,
                    ['fields' => $oldFieldsSnapshot, 'items' => $oldItemsSnapshot],
                    ['fields' => $updateData, 'items' => $newItemsSnapshot],
                    'Purchase Bill #' . $purchaseBill->transfer_no . ' updated'
                );
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Purchase bill updated successfully.']);
    }

    public function destroy(PurchaseBill $purchaseBill)
    {
        $user = auth()->user();
        if (!$user->can('delete purchase bills') && !$user->can('edit purchase bills') && !$user->hasRole('super-admin')) {
            abort(403);
        }

        $this->guardLocationAccess($purchaseBill);

        if (!can_modify_past_date_record($purchaseBill->created_at)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You do not have permission to delete past date records.'
            ], 403);
        }

        if ($purchaseBill->status == PurchaseBill::STATUS_ACCEPTED) {
            if (!PurchaseStockService::canEditOrDeletePurchaseBill($purchaseBill)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This accepted purchase bill cannot be deleted because items have already been sold in the destination branch.'
                ], 422);
            }
        }

        DB::transaction(function () use ($purchaseBill) {
            $purchaseBill->load(['items.product', 'items.variant', 'fromLocation', 'toLocation']);

            if ($purchaseBill->status == PurchaseBill::STATUS_ACCEPTED) {
                $fromLocId = (int) $purchaseBill->from_location_id;
                $toLocId   = (int) $purchaseBill->to_location_id;
                $fromLocName = $purchaseBill->fromLocation?->name ?? 'Source Branch';
                $toLocName   = $purchaseBill->toLocation?->name ?? 'Destination Branch';

                foreach ($purchaseBill->items as $item) {
                    $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type, $item->custom_size_value);
                    $stockQty = (int) round($item->quantity * $multiplier);
                    $destInv = Inventory::where('product_id', $item->product_id)->where('location_id', $toLocId)->first();
                    $destQty = $destInv ? (int) $destInv->quantity : 0;
                    if ($destQty < $stockQty) {
                        $prodName = $item->product?->name ?? "Product #{$item->product_id}";
                        throw new \Exception("Cannot delete purchase bill: destination branch only has {$destQty} in stock for '{$prodName}', but {$stockQty} required to restore.");
                    }
                }

                foreach ($purchaseBill->items as $item) {
                    $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type, $item->custom_size_value);
                    $stockQty = (int) round($item->quantity * $multiplier);
                    $unitPrice = (float) $item->purchase_price;
                    $prodLabel = $item->product ? ($item->product->name . ($item->product->barcode ? ' (' . $item->product->barcode . ')' : '')) : "Product #{$item->product_id}";

                    $destInv = Inventory::where('product_id', $item->product_id)->where('location_id', $toLocId)->first();
                    $oldDestQty = $destInv->quantity;
                    $destInv->decrement('quantity', $stockQty);
                    ActivityLogger::log('Inventory', 'update', $destInv, ['quantity' => $oldDestQty], ['quantity' => $oldDestQty - $stockQty], 'Stock reversed out from ' . $toLocName . ' on deletion of purchase bill #' . $purchaseBill->transfer_no . ' [' . $prodLabel . ']');

                    $srcInv = Inventory::firstOrCreate(
                        ['product_id' => $item->product_id, 'location_id' => $fromLocId],
                        ['quantity' => 0, 'created_by' => auth()->id()]
                    );
                    $oldSrcQty = $srcInv->quantity;
                    $srcInv->increment('quantity', $stockQty);
                    ActivityLogger::log('Inventory', 'update', $srcInv, ['quantity' => $oldSrcQty], ['quantity' => $oldSrcQty + $stockQty], 'Stock restored to ' . $fromLocName . ' on deletion of purchase bill #' . $purchaseBill->transfer_no . ' [' . $prodLabel . ']');

                    PurchaseBatchService::deductBatchStock($toLocId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, $unitPrice, (float)$stockQty);
                    PurchaseBatchService::addBatchStock($fromLocId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, $item->id, $unitPrice, (float)$stockQty);

                    PurchaseBatchService::syncProductBatchStocks($toLocId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null);
                    PurchaseBatchService::syncProductBatchStocks($fromLocId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null);
                }

                $paidAmount = (float) ($purchaseBill->paid_amount ?? 0);
                if ($paidAmount > 0) {
                    $this->applyLocationBalanceTransfer($purchaseBill, $paidAmount, true);
                }
                $purchaseBill->payments()->delete();
            }

            $purchaseBill->items()->delete();
            $purchaseBill->delete();

            ActivityLogger::log(
                'Purchase Bill',
                'delete',
                $purchaseBill,
                ['status' => $purchaseBill->status, 'transfer_no' => $purchaseBill->transfer_no],
                [],
                'Purchase Bill #' . $purchaseBill->transfer_no . ' deleted'
            );
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase bill deleted successfully.'
        ]);
    }

    private function getAcceptedStockDeltaError(PurchaseBill $purchaseBill, int $newFromLocId, int $newToLocId, iterable $newItems): ?string
    {
        $oldItems = $purchaseBill->items()->get();
        $oldFromLocId = (int) $purchaseBill->from_location_id;
        $oldToLocId   = (int) $purchaseBill->to_location_id;
        $toLocationChanged = ($oldToLocId !== $newToLocId);

        $oldMap = [];
        $oldProdObjs = [];
        foreach ($oldItems as $oi) {
            $prod = $oi->product ?? Product::with('variants.attributeValue.attribute')->find($oi->product_id);
            $mult = $this->stockMultiplierFor($prod, $oi->pair_type, $oi->custom_size_value);
            $k = $oi->product_id . '_' . ($oi->product_variant_id ?: 0);
            $oldMap[$k] = ($oldMap[$k] ?? 0) + (int) round($oi->quantity * $mult);
            $oldProdObjs[$k] = [
                'product' => $prod,
                'variant_id' => $oi->product_variant_id ?: null,
                'custom_size_value' => $oi->custom_size_value ? (float) $oi->custom_size_value : null,
            ];
        }

        $newMap = [];
        $newProdObjs = [];
        foreach ($this->normalizeItems($newItems) as $ni) {
            $prod = Product::with('variants.attributeValue.attribute')->find($ni['product_id']);
            if (!$prod) return 'Selected product not found.';
            $customSizeVal = $this->resolveCustomSizeValue($prod, $ni);
            $mult = $this->stockMultiplierFor($prod, $ni['pair_type'] ?? null, $customSizeVal);
            $k = $ni['product_id'] . '_' . ($ni['product_variant_id'] ?: 0);
            $newMap[$k] = ($newMap[$k] ?? 0) + (int) round($ni['quantity'] * $mult);
            $newProdObjs[$k] = [
                'product' => $prod,
                'variant_id' => $ni['product_variant_id'] ?: null,
                'custom_size_value' => $customSizeVal ? (float) $customSizeVal : null,
            ];
        }

        if ($toLocationChanged) {
            foreach ($oldMap as $k => $oldQty) {
                [$pId, $vId] = explode('_', $k);
                $prod = $oldProdObjs[$k]['product'] ?? Product::with('variants.attributeValue.attribute')->find($pId);
                $cSize = $oldProdObjs[$k]['custom_size_value'] ?? null;
                $variantId = $vId > 0 ? (int)$vId : null;
                $label = $prod?->name ?? "Product #{$pId}";
                if ($variantId) {
                    $stockData = $prod->getVariantStock($oldToLocId);
                    $avail = (int) ($stockData['variants'][$variantId] ?? 0);
                    $vObj = $prod->variants->firstWhere('id', $variantId);
                    if ($vObj) {
                        $label .= ' (' . ($vObj->attributeValue->attribute->name ?? 'Variant') . ': ' . ($vObj->attributeValue->value ?? $vObj->id) . ')';
                    }
                } else {
                    $avail = (int) Inventory::where('product_id', $pId)->where('location_id', $oldToLocId)->value('quantity');
                }
                if ($avail < $oldQty) {
                    $oldToLoc = Location::find($oldToLocId);
                    $destName = $oldToLoc ? $oldToLoc->name : 'Destination Branch';
                    $availStr = format_stock_quantity($prod, $avail, $cSize);
                    $reqStr   = format_stock_quantity($prod, $oldQty, $cSize);
                    return "Destination branch '{$destName}' only has {$availStr} of '{$label}' in stock, but {$reqStr} need to be returned to change branch.";
                }
            }
        } else {
            foreach ($oldMap as $k => $oldQty) {
                $newQty = $newMap[$k] ?? 0;
                $delta = $newQty - $oldQty;
                if ($delta < 0) {
                    $absDelta = abs($delta);
                    [$pId, $vId] = explode('_', $k);
                    $prod = $oldProdObjs[$k]['product'] ?? Product::with('variants.attributeValue.attribute')->find($pId);
                    $cSize = $oldProdObjs[$k]['custom_size_value'] ?? null;
                    $variantId = $vId > 0 ? (int)$vId : null;
                    $label = $prod?->name ?? "Product #{$pId}";
                    if ($variantId) {
                        $stockData = $prod->getVariantStock($oldToLocId);
                        $avail = (int) ($stockData['variants'][$variantId] ?? 0);
                        $vObj = $prod->variants->firstWhere('id', $variantId);
                        if ($vObj) {
                            $label .= ' (' . ($vObj->attributeValue->attribute->name ?? 'Variant') . ': ' . ($vObj->attributeValue->value ?? $vObj->id) . ')';
                        }
                    } else {
                        $avail = (int) Inventory::where('product_id', $pId)->where('location_id', $oldToLocId)->value('quantity');
                    }
                    if ($avail < $absDelta) {
                        $availStr   = format_stock_quantity($prod, $avail, $cSize);
                        $returnStr  = format_stock_quantity($prod, $absDelta, $cSize);
                        return "Cannot reduce quantity of '{$label}': destination branch only has {$availStr} in stock, but {$returnStr} need to be returned to source.";
                    }
                }
            }
        }

        foreach ($newMap as $k => $newQty) {
            [$pId, $vId] = explode('_', $k);
            $prod = $newProdObjs[$k]['product'] ?? Product::with('variants.attributeValue.attribute')->find($pId);
            $cSize = $newProdObjs[$k]['custom_size_value'] ?? null;
            $variantId = $vId > 0 ? (int)$vId : null;
            $label = $prod?->name ?? "Product #{$pId}";
            if ($variantId) {
                $stockData = $prod->getVariantStock($newFromLocId);
                $currentStock = (int) ($stockData['variants'][$variantId] ?? 0);
                $vObj = $prod->variants->firstWhere('id', $variantId);
                if ($vObj) {
                    $label .= ' (' . ($vObj->attributeValue->attribute->name ?? 'Variant') . ': ' . ($vObj->attributeValue->value ?? $vObj->id) . ')';
                }
            } else {
                $currentStock = (int) Inventory::where('product_id', $pId)->where('location_id', $newFromLocId)->value('quantity');
            }

            $oldQty = $oldMap[$k] ?? 0;
            if ($newFromLocId === $oldFromLocId) {
                if (!$toLocationChanged) {
                    $delta = $newQty - $oldQty;
                    if ($delta > 0 && $currentStock < $delta) {
                        $deltaStr = format_stock_quantity($prod, $delta, $cSize);
                        $currStr  = format_stock_quantity($prod, $currentStock, $cSize);
                        return "Product '{$label}' needs {$deltaStr} more from source branch, but only {$currStr} available.";
                    }
                } else {
                    $effectiveAvail = $currentStock + $oldQty;
                    if ($newQty > $effectiveAvail) {
                        $newFromLoc = Location::find($newFromLocId);
                        $srcName = $newFromLoc ? $newFromLoc->name : 'Source Branch';
                        $effStr  = format_stock_quantity($prod, $effectiveAvail, $cSize);
                        $reqStr  = format_stock_quantity($prod, $newQty, $cSize);
                        return "Product '{$label}' does not have enough stock in source branch ({$srcName}). Available: {$effStr}, Required: {$reqStr}.";
                    }
                }
            } else {
                if ($newQty > $currentStock) {
                    $newFromLoc = Location::find($newFromLocId);
                    $srcName = $newFromLoc ? $newFromLoc->name : 'Source Branch';
                    $currStr = format_stock_quantity($prod, $currentStock, $cSize);
                    $reqStr  = format_stock_quantity($prod, $newQty, $cSize);
                    return "Product '{$label}' does not have enough stock in source branch ({$srcName}). Available: {$currStr}, Required: {$reqStr}.";
                }
            }
        }

        return null;
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
        $canModifyAccepted = PurchaseStockService::canEditOrDeletePurchaseBill($purchaseBill);

        return view('purchase-bills.show', [
            'transfer' => $purchaseBill,
            'totalAmount' => $totalAmount,
            'totalMrp' => $totalMrp,
            'canModifyAccepted' => $canModifyAccepted,
        ]);
    }

    public function accept(PurchaseBill $purchaseBill)
    {
        $this->authorize('accept purchase bills');
        $this->guardLocationAccess($purchaseBill, true);

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

            $fromLocName = $purchaseBill->fromLocation?->name ?? 'Source Branch';
            $toLocName   = $purchaseBill->toLocation?->name ?? 'Destination Branch';

            PurchaseBill::withoutActivityLogging(fn () => $purchaseBill->update([
                'status' => PurchaseBill::STATUS_ACCEPTED,
                'accepted_by' => auth()->id(),
                'accepted_at' => now(),
            ]));

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
                $prodLabel = $item->product ? ($item->product->name . ($item->product->barcode ? ' (' . $item->product->barcode . ')' : '')) : "Product #{$item->product_id}";

                $oldQty = $source->quantity;
                $source->decrement('quantity', $stockQty);
                ActivityLogger::log('Inventory', 'update', $source, ['quantity' => $oldQty], ['quantity' => $oldQty - $stockQty], 'Stock moved out from ' . $fromLocName . ' to ' . $toLocName . ' for purchase bill #' . $purchaseBill->transfer_no . ' [' . $prodLabel . ']');

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
                ActivityLogger::log('Inventory', 'update', $destination, ['quantity' => $destOldQty], ['quantity' => $destOldQty + $stockQty], 'Stock moved in to ' . $toLocName . ' from ' . $fromLocName . ' for purchase bill #' . $purchaseBill->transfer_no . ' [' . $prodLabel . ']');

                // Update purchase_batch_stocks: Deduct from source branch, Add to destination branch on ACCEPT
                $sourceBatchPrice = DB::table('purchase_batch_stocks')
                    ->where('location_id', $purchaseBill->from_location_id)
                    ->where('product_id', $item->product_id)
                    ->when($item->product_variant_id, fn($q) => $q->where('product_variant_id', $item->product_variant_id), fn($q) => $q->whereNull('product_variant_id'))
                    ->where('quantity', '>', 0)
                    ->where('purchase_price', '>', 0)
                    ->orderBy('id', 'desc')
                    ->value('purchase_price');

                $unitPrice = ((float)$item->purchase_price > 0)
                    ? (float) $item->purchase_price
                    : (($sourceBatchPrice !== null && (float)$sourceBatchPrice > 0) ? (float)$sourceBatchPrice : $this->purchasePriceForPurchaseBillItem($item));

                if ($unitPrice <= 0) {
                    $unitPrice = PurchaseBatchService::resolveFallbackPurchasePrice((int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null);
                }

                if ((float)$item->purchase_price <= 0 && $unitPrice > 0) {
                    $item->update(['purchase_price' => $unitPrice]);
                }

                PurchaseBatchService::deductBatchStock((int)$purchaseBill->from_location_id, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, (float)$unitPrice, (float)$stockQty);
                PurchaseBatchService::addBatchStock((int)$purchaseBill->to_location_id, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, $item->id, (float)$unitPrice, (float)$stockQty);

                PurchaseBatchService::syncProductBatchStocks((int)$purchaseBill->to_location_id, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null);
                PurchaseBatchService::syncProductBatchStocks((int)$purchaseBill->from_location_id, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null);
            }

            if ($purchaseBill->payment_status == PurchaseBill::PAYMENT_STATUS_PAID) {
                $this->applyLocationBalanceTransfer($purchaseBill, $totalAmount);
            }
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
        $this->guardLocationAccess($purchaseBill, true);

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
            $purchasePrice = is_array($item) ? ($item['purchase_price'] ?? null) : ($item->purchase_price ?? null);
            $purchaseItemId = is_array($item) ? ($item['purchase_item_id'] ?? null) : ($item->purchase_item_id ?? null);

            $priceStr = ($purchasePrice !== null && $purchasePrice !== '') ? (string)(float)$purchasePrice : '';
            $key = $productId . ':' . ($variantId ?? 0) . ':' . $pairType . ':' . ($customSizeValue ?? '') . ':' . $priceStr . ':' . ($purchaseItemId ?? '');

            if (!isset($normalized[$key])) {
                $normalized[$key] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'pair_type' => $pairType,
                    'custom_size_value' => $customSizeValue,
                    'purchase_price' => $purchasePrice,
                    'purchase_item_id' => $purchaseItemId,
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
            ? LocationBalanceTransaction::BALANCE_TYPE_BANK
            : LocationBalanceTransaction::BALANCE_TYPE_CASH;

        $balanceCol = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        $outNote = 'Purchase Bill Out #' . $purchaseBill->transfer_no;
        $inNote  = 'Purchase Bill In #' . $purchaseBill->transfer_no;

        if ($reverse) {
            $fromBalance = LocationBalance::where('location_id', $purchaseBill->from_location_id)->lockForUpdate()->first();
            if ($fromBalance) {
                $fromBalance->update([$balanceCol => (float) $fromBalance->{$balanceCol} - $totalAmount]);
            }
            $toBalance = LocationBalance::where('location_id', $purchaseBill->to_location_id)->lockForUpdate()->first();
            if ($toBalance) {
                $toBalance->update([$balanceCol => (float) $toBalance->{$balanceCol} + $totalAmount]);
            }

            LocationBalanceTransaction::whereIn('notes', [$outNote, $inNote])
                ->orWhere('notes', 'LIKE', '%#' . $purchaseBill->transfer_no)
                ->delete();
            return;
        }

        $fromBalance = LocationBalance::where('location_id', $purchaseBill->from_location_id)->lockForUpdate()->firstOrFail();
        $newFromBalance = (float) $fromBalance->{$balanceCol} + $totalAmount;
        $fromBalance->update([$balanceCol => $newFromBalance]);

        $existingOut = LocationBalanceTransaction::where('notes', 'LIKE', '%' . $purchaseBill->transfer_no)->where('location_id', $purchaseBill->from_location_id)->first();
        if ($existingOut) {
            $existingOut->update([
                'location_id'   => $purchaseBill->from_location_id,
                'balance_type'  => $balanceType,
                'type'          => LocationBalanceTransaction::TYPE_CREDIT,
                'amount'        => $totalAmount,
                'balance_after' => $newFromBalance,
                'notes'         => $outNote,
            ]);
        } else {
            LocationBalanceTransaction::create([
                'location_id'   => $purchaseBill->from_location_id,
                'balance_type'  => $balanceType,
                'type'          => LocationBalanceTransaction::TYPE_CREDIT,
                'amount'        => $totalAmount,
                'balance_after' => $newFromBalance,
                'notes'         => $outNote,
                'created_by'    => auth()->id(),
            ]);
        }

        $toBalance = LocationBalance::where('location_id', $purchaseBill->to_location_id)->lockForUpdate()->firstOrFail();
        $newToBalance = (float) $toBalance->{$balanceCol} - $totalAmount;
        $toBalance->update([$balanceCol => $newToBalance]);

        $existingIn = LocationBalanceTransaction::where('notes', 'LIKE', '%' . $purchaseBill->transfer_no)->where('location_id', $purchaseBill->to_location_id)->first();
        if ($existingIn) {
            $existingIn->update([
                'location_id'   => $purchaseBill->to_location_id,
                'balance_type'  => $balanceType,
                'type'          => LocationBalanceTransaction::TYPE_DEBIT,
                'amount'        => $totalAmount,
                'balance_after' => $newToBalance,
                'notes'         => $inNote,
            ]);
        } else {
            LocationBalanceTransaction::create([
                'location_id'   => $purchaseBill->to_location_id,
                'balance_type'  => $balanceType,
                'type'          => LocationBalanceTransaction::TYPE_DEBIT,
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

        $selectedSize = (float) ($item->custom_size_value ?? 0);

        $variant = (!empty($item->product_variant_id) && $product && isset($product->variants))
            ? $product->variants->firstWhere('id', $item->product_variant_id)
            : ($item->variant ?? null);

        $sizes = ($variant && !empty($variant->custom_sizes))
            ? $variant->custom_sizes
            : ($product->custom_sizes ?? []);

        $sizesCollection = collect($sizes)->pluck('size')->map(fn ($size) => (float) $size)->filter(fn ($size) => $size > 0);
        $maxSize = $sizesCollection->count() > 0 ? (float) $sizesCollection->max() : ($product ? (float) $product->getPairPackSize() : 2.0);

        if ($maxSize <= 0) {
            $maxSize = 2.0;
        }

        if ($selectedSize <= 0) {
            if (($item->pair_type ?? '') === 'single') {
                $selectedSize = 1.0;
            } else {
                return $basePrice;
            }
        }

        return (float) ($basePrice * ($selectedSize / $maxSize));
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
        Cache::store('file')->forget('all_mapped_products_bills');
        return Cache::store('file')->remember('all_mapped_products_bills', 1800, function () {
            $products = Product::with([
                'variants.attributeValue.attribute',
                'primaryImage',
                'inventories',
            ])->where('status', 1)->orderBy('name')->get();

            Product::preloadVariantStock($products);

            $allProducts = $products->map(function ($p) {
                $resolvedProductPrice = (float) ($p->purchase_price ?? 0);
                if ($resolvedProductPrice <= 0) {
                    $resolvedProductPrice = PurchaseBatchService::resolveFallbackPurchasePrice($p->id, null);
                }
                $data = [
                    'id'             => $p->id,
                    'name'           => $p->name,
                    'barcode'        => $p->barcode,
                    'type'           => $p->type,
                    'pair_product'   => (bool) $p->pair_product,
                    'custom_sizes'   => $p->custom_sizes ?? [],
                    'purchase_price' => $resolvedProductPrice > 0 ? $resolvedProductPrice : $p->purchase_price,
                    'image'          => $p->primary_image_url,
                ];
                if ($p->type === 'variable') {
                    $data['variants'] = $p->variants->filter(fn ($v) => $v->status == 1)->values()->map(function ($v) use ($p) {
                        $resolvedVarPrice = (float) ($v->purchase_price ?? 0);
                        if ($resolvedVarPrice <= 0) {
                            $resolvedVarPrice = PurchaseBatchService::resolveFallbackPurchasePrice($p->id, $v->id);
                        }
                        return [
                            'id'             => $v->id,
                            'purchase_price' => $resolvedVarPrice > 0 ? $resolvedVarPrice : $v->purchase_price,
                            'custom_sizes'   => $v->custom_sizes ?? [],
                            'attr_name'      => $v->attributeValue->attribute->name ?? '',
                            'value_name'     => $v->attributeValue->value ?? '',
                        ];
                    })->all();
                }

                $stockByLocation = [];
                if ($p->type === 'variable') {
                    $variantStock = $p->getVariantStock();
                    foreach ($variantStock as $locId => $locData) {
                        $vData = $locData['variants'] ?? [];
                        $allVarZero = true;
                        foreach ($vData as $vId => $vStk) {
                            if ($vStk > 0) { $allVarZero = false; break; }
                        }
                        if ($allVarZero) {
                            $invQty = (int) ($p->inventories->firstWhere('location_id', $locId)->quantity ?? 0);
                            $fallbackQty = max($invQty, (int) ($locData['parent'] ?? 0));
                            if ($fallbackQty > 0) {
                                foreach ($vData as $vId => $vStk) {
                                    $vData[$vId] = $fallbackQty;
                                }
                            }
                        }
                        $stockByLocation[$locId] = [
                            'parent'   => $locData['parent'],
                            'variants' => $vData,
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

            return $allProducts;
        });
    }
}
