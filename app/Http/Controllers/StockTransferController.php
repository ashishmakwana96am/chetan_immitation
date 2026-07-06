<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockTransferController extends Controller
{
    public function index()
    {
        $this->authorize('view stock transfers');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        return view('stock-transfers.index', compact('locations', 'isRestricted'));
    }

    public function data(Request $request)
    {
        $this->authorize('view stock transfers');

        $user = auth()->user();

        $transfers = StockTransfer::with(['fromLocation', 'toLocation', 'createdBy'])
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

        $canAccept = auth()->user()->can('accept stock transfers');
        $canReject = auth()->user()->can('reject stock transfers');

        $data = $transfers->map(function ($transfer, $index) use ($canAccept, $canReject) {
            $statusBadge = $this->statusBadge($transfer->status);

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.stock-transfers.show', $transfer) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            if ($transfer->status == StockTransfer::STATUS_PENDING) {
                if ($canAccept) {
                    $actions .= '<button class="dropdown-item text-success stock-transfer-action" data-url="' . route('admin.stock-transfers.accept', $transfer) . '" data-method="PATCH" data-title="Accept Stock Transfer" data-text="Stock will move from source to destination location."><i class="ti ti-check me-2"></i>Accept</button>';
                }
                if ($canReject) {
                    $actions .= '<button class="dropdown-item text-danger stock-transfer-action" data-url="' . route('admin.stock-transfers.reject', $transfer) . '" data-method="PATCH" data-title="Reject Stock Transfer" data-text="No inventory stock will be changed."><i class="ti ti-x me-2"></i>Reject</button>';
                }
            }
            $actions .= '</div></div>';

            return [
                'index' => $index + 1,
                'transfer_no' => '<code>' . e($transfer->transfer_no) . '</code>',
                'from_location' => e($transfer->fromLocation->name ?? '-'),
                'to_location' => e($transfer->toLocation->name ?? '-'),
                'items_count' => $transfer->items_count,
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

    public function create()
    {
        $this->authorize('create stock transfers');

        try {
            $defaultLocation = $this->defaultSourceLocation();
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.stock-transfers.index')->with('error', $e->getMessage());
        }
        $destinationLocations = Location::where('status', 1)->where('id', '!=', $defaultLocation->id)->orderBy('name')->get();
        $products = Product::with(['variants.attributeValue.attribute', 'primaryImage'])
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        $transferNo = generate_invoice_no('STF', StockTransfer::class, 'transfer_no');

        return view('stock-transfers.create', compact('defaultLocation', 'destinationLocations', 'products', 'transferNo'));
    }

    public function store(Request $request)
    {
        $this->authorize('create stock transfers');

        try {
            $defaultLocation = $this->defaultSourceLocation();
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
        $request->merge(['from_location_id' => $defaultLocation->id]);

        $validator = Validator::make($request->all(), [
            'from_location_id' => ['required', 'exists:locations,id'],
            'to_location_id' => ['required', 'exists:locations,id', 'different:from_location_id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
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
            $transfer = StockTransfer::create([
                'transfer_no' => generate_invoice_no('STF', StockTransfer::class, 'transfer_no'),
                'from_location_id' => $defaultLocation->id,
                'to_location_id' => $request->to_location_id,
                'status' => StockTransfer::STATUS_PENDING,
                'remarks' => $request->remarks,
                'created_by' => auth()->id(),
            ]);

            foreach ($this->normalizeItems($request->items) as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Stock transfer request created successfully.']);
    }

    public function show(StockTransfer $stockTransfer)
    {
        $this->authorize('view stock transfers');
        $this->guardLocationAccess($stockTransfer);

        $stockTransfer->load([
            'fromLocation',
            'toLocation',
            'createdBy',
            'acceptedBy',
            'items.product.primaryImage',
            'items.product.variants.attributeValue.attribute',
            'items.variant.attributeValue.attribute',
        ]);

        return view('stock-transfers.show', ['transfer' => $stockTransfer]);
    }

    public function accept(StockTransfer $stockTransfer)
    {
        $this->authorize('accept stock transfers');
        $this->guardLocationAccess($stockTransfer, true);

        if ($stockTransfer->status != StockTransfer::STATUS_PENDING) {
            return response()->json(['status' => 'error', 'message' => 'Only pending transfers can be accepted.'], 422);
        }

        $stockTransfer->load('items');
        $stockError = $this->getStockError($stockTransfer->items, (int) $stockTransfer->from_location_id);
        if ($stockError) {
            return response()->json(['status' => 'error', 'message' => $stockError], 422);
        }

        DB::transaction(function () use ($stockTransfer) {
            foreach ($stockTransfer->items as $item) {
                $source = Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $stockTransfer->from_location_id)
                    ->first();

                if ($source) {
                    $oldQty = $source->quantity;
                    $source->decrement('quantity', $item->quantity);
                    ActivityLogger::log('Inventory', 'update', $source, ['quantity' => $oldQty], ['quantity' => $oldQty - $item->quantity], 'Stock transferred out for transfer #' . $stockTransfer->transfer_no);
                }

                $destination = Inventory::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'location_id' => $stockTransfer->to_location_id,
                    ],
                    [
                        'quantity' => 0,
                        'created_by' => auth()->id(),
                    ]
                );
                $destOldQty = $destination->quantity;
                $destination->increment('quantity', $item->quantity);
                ActivityLogger::log('Inventory', 'update', $destination, ['quantity' => $destOldQty], ['quantity' => $destOldQty + $item->quantity], 'Stock transferred in for transfer #' . $stockTransfer->transfer_no);
            }

            $stockTransfer->update([
                'status' => StockTransfer::STATUS_ACCEPTED,
                'accepted_by' => auth()->id(),
                'accepted_at' => now(),
            ]);
        });

        return response()->json(['status' => 'success', 'message' => 'Stock transfer accepted successfully.']);
    }

    public function reject(StockTransfer $stockTransfer)
    {
        $this->authorize('reject stock transfers');
        $this->guardLocationAccess($stockTransfer, true);

        if ($stockTransfer->status != StockTransfer::STATUS_PENDING) {
            return response()->json(['status' => 'error', 'message' => 'Only pending transfers can be rejected.'], 422);
        }

        $stockTransfer->update([
            'status' => StockTransfer::STATUS_REJECTED,
            'accepted_by' => auth()->id(),
            'accepted_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Stock transfer rejected successfully.']);
    }

    private function normalizeItems(iterable $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int) (is_array($item) ? $item['product_id'] : $item->product_id);
            $variantId = is_array($item) ? ($item['product_variant_id'] ?? null) : $item->product_variant_id;
            $variantId = $variantId ? (int) $variantId : null;
            $quantity = (int) (is_array($item) ? $item['quantity'] : $item->quantity);
            $key = $productId . ':' . ($variantId ?? 0);

            if (!isset($normalized[$key])) {
                $normalized[$key] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
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

            if ($available < $item['quantity']) {
                return 'Product "' . $label . '" only has ' . $available . ' units in source stock; ' . $item['quantity'] . ' requested.';
            }
        }

        return null;
    }

    private function guardLocationAccess(StockTransfer $transfer, bool $destinationOnly = false): void
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
            throw new \RuntimeException('Please create a default location before creating stock transfers.');
        }

        return $location;
    }

    private function statusBadge(int $status): string
    {
        $colors = [
            StockTransfer::STATUS_PENDING => 'bg-label-secondary',
            StockTransfer::STATUS_ACCEPTED => 'bg-label-success',
            StockTransfer::STATUS_REJECTED => 'bg-label-danger',
        ];
        $labels = [
            StockTransfer::STATUS_PENDING => 'Pending',
            StockTransfer::STATUS_ACCEPTED => 'Accepted',
            StockTransfer::STATUS_REJECTED => 'Rejected',
        ];

        return '<span class="badge ' . ($colors[$status] ?? 'bg-label-secondary') . '">' . ($labels[$status] ?? 'Pending') . '</span>';
    }
}
