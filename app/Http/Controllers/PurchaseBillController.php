<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Services\ActivityLogger;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
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
            ->orderByDesc('id')
            ->get();

        $canAccept = auth()->user()->can('accept purchase bills');
        $canReject = auth()->user()->can('reject purchase bills');

        $data = $transfers->map(function ($transfer, $index) use ($canAccept, $canReject) {
            $statusBadge = $this->statusBadge($transfer->status);

            [$totalAmount, $totalMrp] = $this->purchaseBillTotals($transfer);

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.purchase-bills.show', $transfer) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            if ($transfer->status == PurchaseBill::STATUS_PENDING) {
                if ($canAccept) {
                    $actions .= '<button class="dropdown-item text-success purchase-bill-action" data-url="' . route('admin.purchase-bills.accept', $transfer) . '" data-method="PATCH" data-title="Accept Purchase Bill" data-text="Stock will move from source to destination location."><i class="ti ti-check me-2"></i>Accept</button>';
                }
                if ($canReject) {
                    $actions .= '<button class="dropdown-item text-danger purchase-bill-action" data-url="' . route('admin.purchase-bills.reject', $transfer) . '" data-method="PATCH" data-title="Reject Purchase Bill" data-text="No inventory stock will be changed."><i class="ti ti-x me-2"></i>Reject</button>';
                }
            }
            $actions .= '</div></div>';

            return [
                'index' => $index + 1,
                'transfer_no' => '<code>' . e($transfer->transfer_no) . '</code>',
                'from_location' => e($transfer->fromLocation->name ?? '-'),
                'to_location' => e($transfer->toLocation->name ?? '-'),
                'items_count' => $transfer->items_count,
                'total_amount' => currency_symbol() . ' ' . number_format($totalAmount, 2),
                'total_amount_raw' => $totalAmount,
                'total_mrp' => currency_symbol() . ' ' . number_format($totalMrp, 2),
                'total_mrp_raw' => $totalMrp,
                'status' => $statusBadge,
                'created_by' => e($transfer->createdBy->name ?? '-'),
                'date_group' => $transfer->created_at->format('d M Y'),
                'date_sort' => $transfer->created_at->format('Ymd'),
                'actions' => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
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
            ->orderByDesc('id')
            ->get();

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
        $products = Product::with(['variants.attributeValue.attribute', 'primaryImage'])
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        $transferNo = generate_invoice_no('ST', PurchaseBill::class, 'transfer_no');

        return view('purchase-bills.create', compact('defaultLocation', 'sourceLocations', 'canChooseSource', 'destinationLocations', 'products', 'transferNo'));
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
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.pair_type' => ['nullable', 'string', 'in:single,pair'],
            'items.*.custom_size_value' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
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
                'remarks' => $request->remarks,
                'created_by' => auth()->id(),
            ]);

            foreach ($this->normalizeItems($request->items) as $item) {
                $product = Product::find($item['product_id']);
                $customSizeVal = $this->resolveCustomSizeValue($product, $item);

                PurchaseBillItem::create([
                    'purchase_bill_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'pair_type' => $item['pair_type'] ?? 'single',
                    'custom_size_value' => $customSizeVal,
                    'quantity' => $item['quantity'],
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Purchase bill created successfully.']);
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
                $source = Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $purchaseBill->from_location_id)
                    ->first();

                $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type, $item->custom_size_value);
                $stockQty = (int) round($item->quantity * $multiplier);

                if ($source) {
                    $oldQty = $source->quantity;
                    $source->decrement('quantity', $stockQty);
                    ActivityLogger::log('Inventory', 'update', $source, ['quantity' => $oldQty], ['quantity' => $oldQty - $stockQty], 'Stock issued/moved out for purchase bill #' . $purchaseBill->transfer_no);
                }

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

            if ($totalAmount > 0) {
                $onlineMethods = ['online', 'bank_transfer', 'bank transfer', 'razorpay', 'upi'];
                $balanceType = in_array(strtolower($purchaseBill->payment_method ?? 'cash'), $onlineMethods, true)
                    ? \App\Models\LocationBalanceTransaction::BALANCE_TYPE_BANK
                    : \App\Models\LocationBalanceTransaction::BALANCE_TYPE_CASH;

                $balanceCol = $balanceType === \App\Models\LocationBalanceTransaction::BALANCE_TYPE_BANK
                    ? 'bank_balance'
                    : 'cash_balance';

                $fromBalance = \App\Models\LocationBalance::where('location_id', $purchaseBill->from_location_id)->lockForUpdate()->firstOrFail();
                $newFromBalance = (float) $fromBalance->{$balanceCol} + $totalAmount;
                $fromBalance->update([$balanceCol => $newFromBalance]);

                \App\Models\LocationBalanceTransaction::create([
                    'location_id'   => $purchaseBill->from_location_id,
                    'balance_type'  => $balanceType,
                    'type'          => \App\Models\LocationBalanceTransaction::TYPE_CREDIT,
                    'amount'        => $totalAmount,
                    'balance_after' => $newFromBalance,
                    'notes'         => 'Purchase Bill Out #' . $purchaseBill->transfer_no,
                    'created_by'    => auth()->id(),
                ]);

                $toBalance = \App\Models\LocationBalance::where('location_id', $purchaseBill->to_location_id)->lockForUpdate()->firstOrFail();
                $newToBalance = (float) $toBalance->{$balanceCol} - $totalAmount;
                $toBalance->update([$balanceCol => $newToBalance]);

                \App\Models\LocationBalanceTransaction::create([
                    'location_id'   => $purchaseBill->to_location_id,
                    'balance_type'  => $balanceType,
                    'type'          => \App\Models\LocationBalanceTransaction::TYPE_DEBIT,
                    'amount'        => $totalAmount,
                    'balance_after' => $newToBalance,
                    'notes'         => 'Purchase Bill In #' . $purchaseBill->transfer_no,
                    'created_by'    => auth()->id(),
                ]);
            }

            $purchaseBill->update([
                'status' => PurchaseBill::STATUS_ACCEPTED,
                'accepted_by' => auth()->id(),
                'accepted_at' => now(),
            ]);
        });

        return response()->json(['status' => 'success', 'message' => 'Purchase bill accepted successfully.']);
    }

    public function reject(PurchaseBill $purchaseBill)
    {
        $this->authorize('reject purchase bills');
        $this->guardLocationAccess($purchaseBill);

        if ($purchaseBill->status != PurchaseBill::STATUS_PENDING) {
            return response()->json(['status' => 'error', 'message' => 'Only pending purchase bills can be rejected.'], 422);
        }

        $purchaseBill->update([
            'status' => PurchaseBill::STATUS_REJECTED,
            'accepted_by' => auth()->id(),
            'accepted_at' => now(),
        ]);

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
                $requestedText = ($item['pair_type'] === 'pair') ? ($item['quantity'] . ' Pairs') : ($item['quantity'] . ' Pcs');
                if ($product->pair_product && $item['custom_size_value']) {
                    $requestedText = $item['quantity'] . ' Pairs (' . rtrim(rtrim(number_format((float)$item['custom_size_value'], 2), '0'), '.') . ' pcs)';
                }
                return 'Product "' . $label . '" only has ' . $available . ' units in source stock; ' . $requestedText . ' requested.';
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
        $basePrice = (float) ($item->variant->purchase_price ?? $product?->purchase_price ?? 0);

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

        return $basePrice * ($selectedSize / (float) $maxSize);
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
}
