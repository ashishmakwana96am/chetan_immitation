<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\OrderItem;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;

class ProductController extends Controller
{
    public function index()
    {
        $this->authorize('view products');
        $categories = Category::orderBy('name')->get();

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        return view('products.index', compact('categories', 'locations', 'isRestricted'));
    }

    public function data(Request $request)
    {
        $this->authorize('view products');

        $user        = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locationId = $request->location_id;
        if ($isRestricted) {
            $locationId = $user->location_id;
        }

        $baseQuery = Product::query()
            ->when($request->category_id, function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            })
            ->when($request->status !== null && $request->status !== '', function($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->input('search.value'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('product_code', 'like', "%{$search}%");
                });
            });

        $recordsTotal = Product::count();

        $computeStock = function ($product) use ($locationId) {
            if ($product->type === 'variable') {
                $stockData = $product->getVariantStock($locationId);
                if ($locationId) {
                    return $stockData ? array_sum($stockData['variants']) : 0;
                }
                $total = 0;
                foreach ($stockData as $locData) {
                    $total += array_sum($locData['variants']);
                }
                return $total;
            }
            return $product->inventories->sum('quantity');
        };

        $hasStockFilter = in_array($request->stock_status, ['in_stock', 'out_of_stock'], true);
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0) {
            $length = 25;
        }

        $orderColumnMap = [
            1 => 'id',
            3 => 'name',
            4 => 'barcode',
            5 => 'category',
            6 => 'stock',
            7 => 'purchase_price',
            8 => 'sale_price',
            9 => 'mrp',
            10 => 'status',
        ];

        $orderArr = $request->input('order', []);
        $sortKey = 'id';
        $sortDir = 'desc';
        if (!empty($orderArr) && isset($orderArr[0]['column'], $orderArr[0]['dir'])) {
            $colIdx = (int) $orderArr[0]['column'];
            $dir = strtolower($orderArr[0]['dir']) === 'asc' ? 'asc' : 'desc';
            if (isset($orderColumnMap[$colIdx])) {
                $sortKey = $orderColumnMap[$colIdx];
                $sortDir = $dir;
            }
        }

        if ($sortKey === 'category') {
            $baseQuery->leftJoin('categories as cat', 'products.category_id', '=', 'cat.id')
                      ->leftJoin('categories as subcat', 'products.sub_category_id', '=', 'subcat.id')
                      ->select('products.*')
                      ->orderByRaw("COALESCE(subcat.name, cat.name) {$sortDir}");
        } elseif (in_array($sortKey, ['id', 'name', 'barcode', 'purchase_price', 'sale_price', 'mrp', 'status'], true)) {
            $baseQuery->orderBy("products.{$sortKey}", $sortDir);
        }

        if ($sortKey === 'stock' || $hasStockFilter) {
            $lightProducts = (clone $baseQuery)
                ->with([
                    'inventories' => function($q) use ($locationId) {
                        $q->when($locationId, fn($sub) => $sub->where('location_id', $locationId));
                    }
                ])
                ->get();
            Product::preloadVariantStock($lightProducts);

            $filteredProducts = $lightProducts;
            if ($hasStockFilter) {
                $wantInStock = $request->stock_status === 'in_stock';
                $filteredProducts = $filteredProducts->filter(function ($product) use ($computeStock, $wantInStock) {
                    $stock = $computeStock($product);
                    return $wantInStock ? $stock > 0 : $stock <= 0;
                });
            }

            if ($sortKey === 'stock') {
                $filteredProducts = $filteredProducts->sortBy(function ($product) use ($computeStock) {
                    return $computeStock($product);
                }, SORT_REGULAR, $sortDir === 'desc');
            }

            $recordsFiltered = $filteredProducts->count();
            $pageIds = $filteredProducts->pluck('id')->values()->slice($start, $length)->values();

            $products = Product::with([
                'category',
                'subCategory',
                'primaryImage',
                'variants.attributeValue',
                'inventories' => function($q) use ($locationId) {
                    $q->when($locationId, fn($sub) => $sub->where('location_id', $locationId));
                }
            ])->whereIn('id', $pageIds)->get();

            $products = $pageIds->map(fn($id) => $products->firstWhere('id', $id))->filter()->values();
        } else {
            $recordsFiltered = (clone $baseQuery)->count();

            $products = (clone $baseQuery)
                ->with([
                    'category',
                    'subCategory',
                    'primaryImage',
                    'variants.attributeValue',
                    'inventories' => function($q) use ($locationId) {
                        $q->when($locationId, fn($sub) => $sub->where('location_id', $locationId));
                    }
                ])
                ->skip($start)
                ->take($length)
                ->get();
        }

        Product::preloadVariantStock($products);

        $canEdit   = auth()->user()->can('edit products');
        $canDelete = auth()->user()->can('delete products');
        $canClone  = auth()->user()->can('clone products');

        $data = $products->map(function ($product, $index) use ($canEdit, $canDelete, $canClone, $computeStock) {
            $nameHtml = $product->name;
            $variationsStr = $product->variants->map(function ($variant) {
                return $variant->attributeValue->value ?? '';
            })->filter()->unique()->implode(', ');

            $variantsList = $product->type === 'variable'
                ? $product->variants->filter(fn($v) => $v->attributeValue)
                    ->map(fn($v) => ['id' => $v->id, 'value' => $v->attributeValue->value ?? ''])
                    ->values()->toArray()
                : [];


            if ($product->is_variable) {
                $nameHtml .= ' <span class="badge bg-label-info ms-1" style="font-size:10px">Variable</span>';
            }

            if ($product->pair_product) {
                $nameHtml .= ' <span class="badge bg-label-warning ms-1" style="font-size:10px">Pair</span>';
            }

            $image = '<img src="' . $product->primary_image_url . '" width="45" height="45" class="rounded object-fit-cover product-thumbnail" alt="' . e($product->name) . '">';

            $status = $product->status == 1
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-danger">Inactive</span>';

            $stockSumPieces = $computeStock($product);
            $stock = $product->renderStockBadge($stockSumPieces);

            $barcodeVal = $product->barcode;
            $barcode = $barcodeVal
                ? '<div class="d-flex align-items-center gap-2">
                    <code>' . $barcodeVal . '</code>
                    <button onclick="viewBarcode(\'' . $barcodeVal . '\', ' . $product->id . ')" class="btn btn-sm btn-icon btn-label-secondary" title="View Barcode">
                        <i class="ti ti-barcode"></i>
                    </button>
                   </div>'
                : '<span class="text-muted">-</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.products.show', $product) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            if ($canEdit) {
                $actions .= '<a href="' . route('admin.products.edit', $product) . '" class="dropdown-item"><i class="ti ti-pencil me-2"></i>Edit</a>';
            }
            if ($canClone) {
                $actions .= '<a href="' . route('admin.products.create', ['clone_id' => $product->id]) . '" class="dropdown-item"><i class="ti ti-copy me-2"></i>Clone</a>';
            }
            if ($canDelete) {
                if ($canEdit || $canClone) {
                    $actions .= '<div class="dropdown-divider"></div>';
                }
                $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.products.destroy', $product) . '" data-row-id="product-row-' . $product->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
            }
            $actions .= '</div></div>';

            return [
                'id'             => $product->id,
                'index'          => $index + 1,
                'image'          => $image,
                'name'           => $nameHtml,
                'barcode'        => $barcode,
                'raw_barcode'    => $product->barcode,
                'product_code'   => $product->product_code,
                'pair_product'   => (bool) $product->pair_product,
                'pair_mode'      => $product->pair_mode,
                'custom_sizes'   => $product->custom_sizes,
                'variants_list'  => $variantsList,
                'category'       => !empty($product->subCategory->name) ? $product->subCategory->name : ($product->category->name ?? '-'),
                'variations'     => $variationsStr,
                'stock'          => $stock,
                'purchase_price' => format_price($product->purchase_price),
                'sale_price'     => format_price($product->display_sale_price),
                'mrp'            => format_price($product->display_mrp),
                'status'         => $status,
                'actions'        => $actions,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'status'          => 'success',
            'data'            => $data,
        ]);
    }

    /**
     * Stash a large barcode-print item list server-side and hand back a short
     * token, so the actual print request stays a small GET URL instead of a
     * multi-hundred-item query string (which some servers reject with a 414).
     */
    public function prepareBarcodePrint(\Illuminate\Http\Request $request)
    {
        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json(['status' => 'error', 'message' => 'No items to print.'], 422);
        }

        $token = Str::random(32);
        Cache::put('barcode_print:' . $token, $items, now()->addMinutes(30));

        return response()->json(['status' => 'success', 'token' => $token]);
    }

    public function printBarcodes(\Illuminate\Http\Request $request)
    {
        if ($request->filled('token')) {
            $itemsInput = Cache::get('barcode_print:' . $request->input('token'), []);
        } else {
            $itemsInput = $request->input('items', []);
        }

        $printItems = [];
        $totalQty = 0;
        
        $generator = new BarcodeGeneratorPNG();
        
        foreach ($itemsInput as $item) {
            $product = Product::with(['category', 'subCategory', 'variants.attributeValue'])->find($item['id'] ?? null);
            if ($product) {
                $barcodeVal = $product->barcode;
                $categoryDisplay = !empty($product->subCategory?->name)
                    ? $product->subCategory->name
                    : ($product->category?->name ?? '');

                $variations = $product->variants->map(fn($v) => $v->attributeValue->value ?? '')->filter()->unique()->implode(', ');

                // Resolve variant label from selected_variant_id
                $variantLabel = null;
                if (!empty($item['selected_variant_id'])) {
                    $selectedVariant = $product->variants->firstWhere('id', (int) $item['selected_variant_id']);
                    $variantLabel = $selectedVariant?->attributeValue?->value;
                }
                
                $salePriceVal = $product->sale_price;
                $mrpVal = $product->mrp;

                if ($product->pair_product && !empty($product->custom_sizes)) {
                    $selectedSizeVal = $item['selected_size'] ?? $item['custom_size'] ?? null;
                    $selectedSizeNum = null;
                    if ($selectedSizeVal) {
                        $selectedSizeNum = (float) filter_var($selectedSizeVal, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    }

                    $matchedSizeRow = null;
                    if ($selectedSizeNum) {
                        $matchedSizeRow = collect($product->custom_sizes)->first(fn($s) => abs((float)$s['size'] - $selectedSizeNum) < 0.001);
                    }

                    if (!$matchedSizeRow) {
                        $sizesList = collect($product->custom_sizes)->sortBy('size')->values();
                        $matchedSizeRow = $sizesList->first();
                    }

                    if ($matchedSizeRow && isset($matchedSizeRow['sale_price'])) {
                        $salePriceVal = $matchedSizeRow['sale_price'];
                    }
                    if ($matchedSizeRow && isset($matchedSizeRow['mrp'])) {
                        $mrpVal = $matchedSizeRow['mrp'];
                    }
                }

                $salePrice = number_format($salePriceVal, 0, '.', '');
                $mrp = number_format($mrpVal ?? $salePriceVal, 0, '.', '');
                $qty = (int)($item['qty'] ?? 1);
                $totalQty += $qty;
                
                $pngData = $generator->getBarcode($barcodeVal, $generator::TYPE_CODE_128, 2.5, 60);
                $barcodeBase64 = 'data:image/png;base64,' . base64_encode($pngData);

                $categoryLength = strlen($categoryDisplay);
                $categoryFontSize = match (true) {
                    $categoryLength > 18 => 6.0,
                    $categoryLength > 14 => 6.5,
                    $categoryLength > 10 => 7.5,
                    default => 8.5,
                };

                $isPair = (bool) $product->pair_product;
                $customSizeLabel = null;

                if ($product->pair_product && !empty($product->custom_sizes)) {
                    $selectedSizeVal = $item['selected_size'] ?? $item['custom_size'] ?? null;
                    if ($selectedSizeVal) {
                        $customSizeLabel = str_contains((string)$selectedSizeVal, 'pcs') ? $selectedSizeVal : $selectedSizeVal . ' pcs';
                    } else {
                        $sizesList = collect($product->custom_sizes)->sortByDesc('size')->values();
                        $firstSize = $sizesList->first();
                        if ($firstSize && isset($firstSize['size'])) {
                            $rawSize = $firstSize['size'];
                            $sizeStr = is_numeric($rawSize) ? rtrim(rtrim(number_format((float)$rawSize, 2), '0'), '.') : $rawSize;
                            $customSizeLabel = str_contains((string)$sizeStr, 'pcs') ? $sizeStr : $sizeStr . ' pcs';
                        }
                    }
                }
                if ($customSizeLabel) {
                    $isPair = false;
                }

                $printItems[] = [
                    'barcodeBase64'    => $barcodeBase64,
                    'barcodeText'      => $barcodeVal,
                    'productCode'      => $product->product_code !== null ? number_format($product->product_code, 0, '.', '') : '',
                    'isPair'           => $isPair,
                    'customSizeLabel'  => $customSizeLabel,
                    'category'         => $categoryDisplay,
                    'categoryFontSize' => $categoryFontSize,
                    'variations'       => $variations,
                    'variantLabel'     => $variantLabel,
                    'salePrice'        => $salePrice,
                    'mrp'              => $mrp,
                    'qty'              => $qty
                ];
            }
        }

        if ($totalQty === 0) {
            abort(404, 'No products to print.');
        }

        $labelWidth  = 34.02;  // 12mm
        $labelHeight = 232.44; // 82mm

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Barcodes',
                'pdfUrl' => route('admin.products.print-barcodes', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('products.print_barcodes', compact('printItems'))
            ->setPaper([0, 0, $labelWidth, $labelHeight], 'landscape');

        ActivityLogger::log('Products', 'export', null, null, null, 'Barcode labels printed (' . $totalQty . ' labels)');

        return $pdf->stream('barcodes.pdf');
    }

    public function show(Product $product)
    {
        $this->authorize('view products');

        $locationId   = $this->restrictedLocationId();
        $isRestricted = (bool) $locationId;

        $product->load([
            'category',
            'images',
            'createdBy',
            'variants.attributeValue',
            'inventories',
            'inventories.location'
        ]);

        $purchaseCount = $this->countDistinctGroups($this->purchaseHistoryQuery($product, $locationId), 'purchase_id');
        $transferCount = $this->countDistinctGroups($this->transferHistoryQuery($product, $locationId), 'purchase_bill_id');
        $saleCount     = $this->countDistinctGroups($this->saleHistoryQuery($product, $locationId), 'order_id');

        return view('products.show', compact(
            'product', 'locationId', 'isRestricted', 'purchaseCount', 'transferCount', 'saleCount'
        ));
    }

    public function purchaseHistoryData(Request $request, Product $product)
    {
        $this->authorize('view products');
        $locationId = $this->restrictedLocationId();

        $items = $this->purchaseHistoryQuery($product, $locationId)
            ->with(['invoice.supplier', 'invoice.location', 'variant.attributeValue'])
            ->get();

        $statusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-danger'];
        $statusLabels = [1 => 'Pending', 2 => 'Approved', 3 => 'Declined'];

        $data = $this->groupHistoryItems($items, 'purchase_id', $product)
            ->map(function ($row) use ($statusColors, $statusLabels) {
                $item    = $row->item;
                $invoice = $item->invoice;
                return [
                    'invoice_no' => $invoice
                        ? '<a href="' . route('admin.purchases.show', $item->purchase_id) . '" class="fw-semibold">' . e($invoice->invoice_no) . '</a>'
                        : '-',
                    'supplier'   => e($invoice->supplier->name ?? '-'),
                    'location'   => e($invoice->location->name ?? '-'),
                    'variant'    => e($item->variant->attributeValue->value ?? '-'),
                    'breakdown'  => $row->breakdown ?: '-',
                    'qty'        => number_format($row->total_qty),
                    'qty_raw'    => (float) $row->total_qty,
                    'amount'     => format_price($row->total_amount),
                    'amount_raw' => (float) $row->total_amount,
                    'status'     => $invoice
                        ? '<span class="badge ' . ($statusColors[$invoice->status] ?? 'bg-label-secondary') . ' stock-badge">' . ($statusLabels[$invoice->status] ?? 'Pending') . '</span>'
                        : '',
                    'date_group' => $invoice && $invoice->created_at ? $invoice->created_at->format('d M Y') : '-',
                    'date_sort'  => $invoice && $invoice->created_at ? $invoice->created_at->format('Ymd') : '00000000',
                ];
            })
            ->values();

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function transferHistoryData(Request $request, Product $product)
    {
        $this->authorize('view products');
        $locationId = $this->restrictedLocationId();

        $items = $this->transferHistoryQuery($product, $locationId)
            ->with(['transfer.fromLocation', 'transfer.toLocation', 'variant.attributeValue'])
            ->get();

        $statusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-danger'];
        $statusLabels = [1 => 'Pending', 2 => 'Accepted', 3 => 'Rejected'];

        $data = $this->groupHistoryItems($items, 'purchase_bill_id', $product)
            ->map(function ($row) use ($statusColors, $statusLabels) {
                $item     = $row->item;
                $transfer = $item->transfer;
                return [
                    'transfer_no' => $transfer
                        ? '<a href="' . route('admin.purchase-bills.show', $item->purchase_bill_id) . '" class="fw-semibold">' . e($transfer->transfer_no) . '</a>'
                        : '-',
                    'from_branch'  => e($transfer->fromLocation->name ?? '-'),
                    'to_branch'    => e($transfer->toLocation->name ?? '-'),
                    'variant'      => e($item->variant->attributeValue->value ?? '-'),
                    'breakdown'    => $row->breakdown ?: '-',
                    'qty'          => number_format($row->total_qty),
                    'qty_raw'      => (float) $row->total_qty,
                    'status'       => $transfer
                        ? '<span class="badge ' . ($statusColors[$transfer->status] ?? 'bg-label-secondary') . ' stock-badge">' . ($statusLabels[$transfer->status] ?? 'Pending') . '</span>'
                        : '',
                    'date_group'   => ($transfer && ($transfer->accepted_at ?? $transfer->created_at)) ? ($transfer->accepted_at ?? $transfer->created_at)->format('d M Y') : '-',
                    'date_sort'    => ($transfer && ($transfer->accepted_at ?? $transfer->created_at)) ? ($transfer->accepted_at ?? $transfer->created_at)->format('Ymd') : '00000000',
                ];
            })
            ->values();

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function saleHistoryData(Request $request, Product $product)
    {
        $this->authorize('view products');
        $locationId = $this->restrictedLocationId();

        $items = $this->saleHistoryQuery($product, $locationId)
            ->with(['order.customer', 'order.location', 'variant.attributeValue'])
            ->get();

        $statusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-info', 4 => 'bg-label-warning', 5 => 'bg-label-success', 6 => 'bg-label-danger'];
        $statusLabels = [1 => 'Pending', 2 => 'Approve', 3 => 'Shipped', 4 => 'Out for delivery', 5 => 'Delivered', 6 => 'Cancelled'];

        $data = $this->groupHistoryItems($items, 'order_id', $product)
            ->map(function ($row) use ($statusColors, $statusLabels) {
                $item  = $row->item;
                $order = $item->order;
                return [
                    'order_no' => $order
                        ? '<a href="' . route('admin.sales.show', $item->order_id) . '" class="fw-semibold">' . e($order->order_no) . '</a>'
                        : '-',
                    'customer'   => e($order->customer->name ?? 'Walk-in Customer'),
                    'location'   => e($order->location->name ?? '-'),
                    'variant'    => e($item->variant->attributeValue->value ?? '-'),
                    'breakdown'  => $row->breakdown ?: '-',
                    'qty'        => number_format($row->total_qty),
                    'qty_raw'    => (float) $row->total_qty,
                    'amount'     => format_price($row->total_amount),
                    'amount_raw' => (float) $row->total_amount,
                    'status'     => $order
                        ? '<span class="badge ' . ($statusColors[$order->status] ?? 'bg-label-secondary') . ' stock-badge">' . ($statusLabels[$order->status] ?? 'Pending') . '</span>'
                        : '',
                    'date_group' => $order && $order->created_at ? $order->created_at->format('d M Y') : '-',
                    'date_sort'  => $order && $order->created_at ? $order->created_at->format('Ymd') : '00000000',
                ];
            })
            ->values();

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    private function restrictedLocationId(): ?int
    {
        $user = auth()->user();
        return ($user->location_id && !$user->hasRole('super-admin')) ? $user->location_id : null;
    }

    private function purchaseHistoryQuery(Product $product, ?int $locationId)
    {
        return PurchaseItem::where('product_id', $product->id)
            ->whereHas('invoice', function ($q) use ($locationId) {
                $q->when($locationId, fn($sub) => $sub->where('location_id', $locationId));
            })
            ->latest();
    }

    private function transferHistoryQuery(Product $product, ?int $locationId)
    {
        return PurchaseBillItem::where('product_id', $product->id)
            ->whereHas('transfer', function ($q) use ($locationId) {
                $q->when($locationId, fn($sub) => $sub->where(function ($w) use ($locationId) {
                    $w->where('from_location_id', $locationId)->orWhere('to_location_id', $locationId);
                }));
            })
            ->latest();
    }

    private function saleHistoryQuery(Product $product, ?int $locationId)
    {
        return OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($q) use ($locationId) {
                $q->when($locationId, fn($sub) => $sub->where('location_id', $locationId));
            })
            ->latest();
    }

    private function countDistinctGroups($query, string $transactionKey): int
    {
        return $query->select($transactionKey, 'product_variant_id')
            ->get()
            ->unique(fn($item) => $item->{$transactionKey} . '-' . ($item->product_variant_id ?? 0))
            ->count();
    }

    private function groupHistoryItems($items, string $transactionKey, Product $product)
    {
        $isPairProduct = (bool) $product->pair_product;
        $fallbackSize = (float) (collect($product->custom_sizes ?? [])->pluck('size')->max() ?: 2);
        $gstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);

        return $items
            ->groupBy(fn($item) => $item->{$transactionKey} . '-' . ($item->product_variant_id ?? 0))
            ->map(function ($group) use ($isPairProduct, $fallbackSize) {
                $first = $group->first();

                $totalQty = $group->sum(function ($item) use ($isPairProduct, $fallbackSize) {
                    return $isPairProduct ? ($item->quantity * (float) ($item->custom_size_value ?: $fallbackSize)) : $item->quantity;
                });

                $totalAmount = $group->sum(function ($item) {
                    $parent = $item->invoice ?? $item->order ?? null;
                    if ($parent) {
                        $parentFinal = (float) ($parent->total_amount ?? $parent->final_amount ?? 0);
                        if ($parentFinal > 0) {
                            $parentItemsSum = (float) $parent->items->sum('total');
                            if ($parentItemsSum > 0) {
                                return ($item->total / $parentItemsSum) * $parentFinal;
                            }
                            return $parentFinal;
                        }
                    }
                    return $item->total;
                });

                $breakdown = null;
                if ($isPairProduct) {
                    $breakdown = $group->groupBy(fn($item) => (float) ($item->custom_size_value ?: $fallbackSize))
                        ->sortKeys()
                        ->map(function ($sizeGroup, $size) {
                            $sizeLabel = rtrim(rtrim(number_format((float) $size, 2), '0'), '.');
                            $packCount = $sizeGroup->sum('quantity');
                            return $packCount . ' Pair' . ($packCount > 1 ? 's' : '') . ' × ' . $sizeLabel . 'pcs';
                        })
                        ->implode(', ');
                }

                return (object) [
                    'item'         => $first,
                    'total_qty'    => $totalQty,
                    'total_amount' => $totalAmount,
                    'breakdown'    => $breakdown,
                ];
            })
            ->values();
    }

    public function create(Request $request)
    {
        $this->authorize('create products');
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $attributes = Attribute::with('values')->where('status', 1)->orderBy('name')->get();

        $clonedProduct = null;
        $subCategories = collect();
        if ($request->filled('clone_id')) {
            $this->authorize('clone products');
            $clonedProduct = Product::with(['images', 'variants.attributeValue.attribute'])->findOrFail($request->clone_id);
            $subCategories = SubCategory::where('category_id', $clonedProduct->category_id)
                ->where('status', 1)
                ->orderBy('name')
                ->get();
        }

        return view('products.create', compact('categories', 'clonedProduct', 'subCategories', 'attributes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create products');

        $isCloning = $request->filled('cloned_from_id');

        $rules = [
            'name'                     => ['required', 'string', 'max:200'],
            'category_id'              => ['required', 'exists:categories,id'],
            'sub_category_id'          => ['nullable', 'exists:sub_categories,id'],
            'barcode'                  => ['required', 'string', 'max:100', 'unique:products,barcode'],
            'description'              => ['nullable', 'string'],
            'additional_information'   => ['nullable', 'string'],
            'product_highlights'        => ['nullable', 'string'],
            'type'                     => ['required', 'in:normal,variable'],
            'sale'                     => ['nullable', 'boolean'],
            'pair_product'             => ['nullable', 'boolean'],
            'bypass_min_price'         => ['nullable', 'boolean'],
            'hide_from_website'        => ['nullable', 'boolean'],
            'primary_image_base64'     => ['nullable', 'string'],
            'additional_images_base64' => ['nullable', 'array'],
            'additional_images_base64.*' => ['nullable', 'string'],
        ];

        $rules['product_code'] = ['required', 'numeric', 'min:0.01'];
        $rules['purchase_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['sale_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['mrp_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['purchase_price'] = ['required', 'numeric', 'min:0'];
        $rules['sale_price'] = ['required', 'numeric', 'min:0'];
        $rules['mrp'] = ['required', 'numeric', 'min:0'];
        $pairMode = 'custom_size';

        if ($request->has('pair_product')) {
            $rules['custom_sizes_json'] = [
                'required',
                'json',
                function ($attribute, $value, $fail) {
                    $decoded = json_decode($value, true);
                    if (!is_array($decoded) || empty($decoded)) {
                        $fail('Please add at least one pair size with pricing.');
                        return;
                    }
                    foreach ($decoded as $row) {
                        $sizeText = isset($row['size']) ? ($row['size'] . ' pcs') : 'pair size';
                        if (isset($row['size']) && ((float)$row['size'] > 4 || (float)$row['size'] <= 0)) {
                            $fail("Pair size ({$sizeText}) cannot be greater than 4 pcs.");
                            return;
                        }
                        if (!isset($row['sale_price']) || $row['sale_price'] === null || $row['sale_price'] === '' || !is_numeric($row['sale_price']) || (float)$row['sale_price'] <= 0) {
                            $fail("Sale Price ({$sizeText}) is required.");
                            return;
                        }
                        if (!isset($row['mrp']) || $row['mrp'] === null || $row['mrp'] === '' || !is_numeric($row['mrp']) || (float)$row['mrp'] <= 0) {
                            $fail("MRP ({$sizeText}) is required.");
                            return;
                        }
                    }
                },
            ];
        }

        if ($request->type === 'variable') {
            $rules['variants_json'] = [
                'required',
                'json',
                function ($attribute, $value, $fail) use ($request) {
                    $decoded = json_decode($value, true);
                    if (!is_array($decoded) || empty($decoded)) {
                        $fail('Please add at least one attribute & variant.');
                        return;
                    }
                    if ($request->has('pair_product')) {
                        $this->validateVariantCustomSizes($decoded, $fail);
                    }
                }
            ];
        }

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $pairMode) {
            $isSuperAdmin = auth()->user()->hasRole('super-admin');

            $productData = [
                'name'            => $request->name,
                'slug'            => generate_slug(Product::class, $request->name),
                'category_id'     => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'barcode'         => $request->barcode,
                'product_code'    => $request->product_code,
                'purchase_multiplier' => $request->purchase_multiplier,
                'sale_multiplier' => $request->sale_multiplier,
                'mrp_multiplier'  => $request->mrp_multiplier,
                'description'     => $request->description,
                'additional_information' => $request->additional_information,
                'product_highlights' => $request->product_highlights,
                'type'            => $request->type,
                'status'          => $request->has('status') ? 1 : 2,
                'sale'            => $request->has('sale') ? 1 : 0,
                'pair_product'    => $request->has('pair_product') ? 1 : 0,
                'bypass_min_price' => $isSuperAdmin && $request->has('bypass_min_price') ? 1 : 0,
                'hide_from_website' => $request->has('hide_from_website') ? 1 : 0,
                'created_by'      => auth()->id(),
                'sort_order'      => ((int) Product::max('sort_order')) + 1,
            ];

            $productData['purchase_price'] = $request->purchase_price;

            if ($request->has('pair_product')) {
                $customSizesArr = json_decode($request->custom_sizes_json, true) ?? [];
                if (is_array($customSizesArr)) {
                    usort($customSizesArr, fn ($a, $b) => (float) ($a['size'] ?? 0) <=> (float) ($b['size'] ?? 0));
                }
                $productData['custom_sizes'] = $customSizesArr;
                $productData['sale_price']   = $request->sale_price;
                $productData['mrp']          = $request->mrp;
            } else {
                $productData['custom_sizes'] = null;
                $productData['sale_price']   = $request->sale_price;
                $productData['mrp']          = $request->mrp;
            }

            $product = Product::create($productData);

            // Primary image
            if ($request->filled('primary_image_base64')) {
                $imagePath = $this->saveBase64Image($request->primary_image_base64);
                if ($imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'is_primary' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            } elseif ($request->filled('cloned_from_id') && !$request->boolean('remove_cloned_primary')) {
                $originalPrimary = ProductImage::where('product_id', $request->cloned_from_id)
                    ->where('is_primary', true)
                    ->first();
                if ($originalPrimary) {
                    $newImagePath = $this->copyProductImageFile($originalPrimary->image_path);
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $newImagePath ?: $originalPrimary->image_path,
                        'is_primary' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Additional images
            // 1. Copy kept cloned ones
            if ($request->filled('cloned_from_id') && $request->filled('existing_cloned_images')) {
                $originalAdditionals = ProductImage::where('product_id', $request->cloned_from_id)
                    ->where('is_primary', false)
                    ->whereIn('id', $request->existing_cloned_images)
                    ->get();
                foreach ($originalAdditionals as $originalImg) {
                    $newImagePath = $this->copyProductImageFile($originalImg->image_path);
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $newImagePath ?: $originalImg->image_path,
                        'is_primary' => false,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // 2. Save newly uploaded ones
            if ($request->filled('additional_images_base64')) {
                foreach ($request->additional_images_base64 as $base64) {
                    $imagePath = $this->saveBase64Image($base64);
                    if ($imagePath) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $imagePath,
                            'is_primary' => false,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            // Create variants for variable products
            if ($request->type === 'variable' && $request->filled('variants_json')) {
                $variants = json_decode($request->variants_json, true);
                foreach ($variants as $item) {
                    $variantSizes = $request->has('pair_product') ? ($item['custom_sizes'] ?? []) : [];
                    ProductVariant::create([
                        'product_id'         => $product->id,
                        'attribute_value_id' => $item['attribute_value_id'],
                        'purchase_price'     => $item['purchase_price'] ?? 0,
                        'sale_price'         => $item['sale_price'] ?? 0,
                        'status'             => ($item['status'] ?? 1) == 1 ? 1 : 2,
                        'custom_sizes'       => !empty($variantSizes) ? $variantSizes : null,
                    ]);
                }
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Product created successfully.',
        ]);
    }

    public function edit(Product $product)
    {
        $this->authorize('edit products');
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $subCategories = SubCategory::where('category_id', $product->category_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        $attributes = Attribute::with('values')->where('status', 1)->orderBy('name')->get();
        $product->load('images', 'variants.attributeValue');
        return view('products.edit', compact('product', 'categories', 'subCategories', 'attributes'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('edit products');

        $rules = [
            'name'                     => ['required', 'string', 'max:200'],
            'category_id'              => ['required', 'exists:categories,id'],
            'sub_category_id'          => ['nullable', 'exists:sub_categories,id'],
            'barcode'                  => ['required', 'string', 'max:100', 'unique:products,barcode,' . $product->id],
            'description'              => ['nullable', 'string'],
            'additional_information'   => ['nullable', 'string'],
            'product_highlights'        => ['nullable', 'string'],
            'type'                     => ['required', 'in:normal,variable'],
            'sale'                     => ['nullable', 'boolean'],
            'pair_product'             => ['nullable', 'boolean'],
            'bypass_min_price'         => ['nullable', 'boolean'],
            'hide_from_website'        => ['nullable', 'boolean'],
            'primary_image_base64'     => ['nullable', 'string'],
            'additional_images_base64' => ['nullable', 'array'],
            'additional_images_base64.*' => ['nullable', 'string'],
        ];

        $rules['product_code'] = ['required', 'numeric', 'min:0.01'];
        $rules['purchase_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['sale_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['mrp_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['purchase_price'] = ['required', 'numeric', 'min:0'];
        $rules['sale_price'] = ['required', 'numeric', 'min:0'];
        $rules['mrp'] = ['required', 'numeric', 'min:0'];
        $pairMode = 'custom_size';

        if ($request->has('pair_product')) {
            $rules['custom_sizes_json'] = [
                'required',
                'json',
                function ($attribute, $value, $fail) {
                    $decoded = json_decode($value, true);
                    if (!is_array($decoded) || empty($decoded)) {
                        $fail('Please add at least one pair size with pricing.');
                        return;
                    }
                    foreach ($decoded as $row) {
                        $sizeText = isset($row['size']) ? ($row['size'] . ' pcs') : 'pair size';
                        if (isset($row['size']) && ((float)$row['size'] > 4 || (float)$row['size'] <= 0)) {
                            $fail("Pair size ({$sizeText}) cannot be greater than 4 pcs.");
                            return;
                        }
                        if (!isset($row['sale_price']) || $row['sale_price'] === null || $row['sale_price'] === '' || !is_numeric($row['sale_price']) || (float)$row['sale_price'] <= 0) {
                            $fail("Sale Price ({$sizeText}) is required.");
                            return;
                        }
                        if (!isset($row['mrp']) || $row['mrp'] === null || $row['mrp'] === '' || !is_numeric($row['mrp']) || (float)$row['mrp'] <= 0) {
                            $fail("MRP ({$sizeText}) is required.");
                            return;
                        }
                    }
                },
            ];
        }

        if ($request->type === 'variable') {
            $rules['variants_json'] = [
                'required',
                'json',
                function ($attribute, $value, $fail) use ($request) {
                    $decoded = json_decode($value, true);
                    if (!is_array($decoded) || empty($decoded)) {
                        $fail('Please add at least one attribute & variant.');
                        return;
                    }
                    if ($request->has('pair_product')) {
                        $this->validateVariantCustomSizes($decoded, $fail);
                    }
                }
            ];
        }

        $messages = [];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $wasNormal   = ($product->type === 'normal');
        $normalStock = $wasNormal ? (int)$product->totalAvailableStock() : 0;

        if ($wasNormal && $normalStock > 0 && $request->type === 'variable') {
            $stockMigrationInput = $request->input('stock_migration', []);
            $totalAllocated      = array_sum(array_map('intval', $stockMigrationInput));

            if ($totalAllocated !== $normalStock) {
                return response()->json([
                    'status'  => 'error',
                    'message' => [
                        'stock_migration' => [
                            "Please allocate all {$normalStock} Pcs of existing stock across variations before updating. (Currently allocated: {$totalAllocated} Pcs)."
                        ]
                    ],
                ], 422);
            }
        }

        $redistributableStock = 0;
        if (!$wasNormal && $request->type === 'variable' && $request->filled('variants_json')) {
            $newAttributeValueIds = collect(json_decode($request->variants_json, true) ?? [])
                ->pluck('attribute_value_id')
                ->map(fn ($v) => (int) $v)
                ->toArray();
            $deletingVariantIds = $product->variants()
                ->whereNotIn('attribute_value_id', $newAttributeValueIds)
                ->pluck('id')
                ->toArray();

            if (!empty($deletingVariantIds)) {
                foreach ($product->getVariantStock() as $locData) {
                    foreach ($deletingVariantIds as $vId) {
                        $redistributableStock += (int) ($locData['variants'][$vId] ?? 0);
                    }
                }
            }

            if ($redistributableStock > 0) {
                $stockMigrationInput = $request->input('stock_migration', []);
                $totalAllocated = array_sum(array_map('intval', $stockMigrationInput));

                if ($totalAllocated !== $redistributableStock) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => [
                            'stock_migration' => [
                                "The selected attributes changed — please allocate all {$redistributableStock} Pcs of existing variant stock across the new variations before updating. (Currently allocated: {$totalAllocated} Pcs)."
                            ]
                        ],
                    ], 422);
                }
            }
        }

        DB::transaction(function () use ($request, $product, $wasNormal, $pairMode) {
            $isSuperAdmin = auth()->user()->hasRole('super-admin');

            $productData = [
                'name'            => $request->name,
                'category_id'     => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'barcode'         => $request->barcode,
                'product_code'    => $request->product_code,
                'purchase_multiplier' => $request->purchase_multiplier,
                'sale_multiplier' => $request->sale_multiplier,
                'mrp_multiplier'  => $request->mrp_multiplier,
                'description'     => $request->description,
                'additional_information' => $request->additional_information,
                'product_highlights' => $request->product_highlights,
                'type'            => $request->type,
                'status'          => $request->has('status') ? 1 : 2,
                'sale'            => $request->has('sale') ? 1 : 0,
                'pair_product'    => $request->has('pair_product') ? 1 : 0,
                'hide_from_website' => $request->has('hide_from_website') ? 1 : 0,
            ];

            if ($isSuperAdmin) {
                $productData['bypass_min_price'] = $request->has('bypass_min_price') ? 1 : 0;
            }

            $productData['purchase_price'] = $request->purchase_price;

            if ($request->has('pair_product')) {
                $customSizesArr = json_decode($request->custom_sizes_json, true) ?? [];
                if (is_array($customSizesArr)) {
                    usort($customSizesArr, fn ($a, $b) => (float) ($a['size'] ?? 0) <=> (float) ($b['size'] ?? 0));
                }
                $productData['custom_sizes'] = $customSizesArr;
                $productData['sale_price']   = $request->sale_price;
                $productData['mrp']          = $request->mrp;
            } else {
                $productData['custom_sizes'] = null;
                $productData['sale_price']   = $request->sale_price;
                $productData['mrp']          = $request->mrp;
            }

            $product->update($productData);

            if ($request->filled('primary_image_base64')) {
                $existing = $product->images()->where('is_primary', true)->first();
                if ($existing) {
                    $existingFile = public_path('uploads/' . $existing->image_path);
                    if (file_exists($existingFile)) {
                        @unlink($existingFile);
                    }
                    $existing->delete();
                }
                $imagePath = $this->saveBase64Image($request->primary_image_base64);
                if ($imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'is_primary' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            } elseif ($request->input('remove_primary_image') == '1' || $request->input('remove_primary_image') === 1) {
                $existing = $product->images()->where('is_primary', true)->first();
                if ($existing) {
                    $existingFile = public_path('uploads/' . $existing->image_path);
                    if (file_exists($existingFile)) {
                        @unlink($existingFile);
                    }
                    $existing->delete();
                }
            }

            if ($request->filled('additional_images_base64')) {
                foreach ($request->additional_images_base64 as $base64) {
                    $imagePath = $this->saveBase64Image($base64);
                    if ($imagePath) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $imagePath,
                            'is_primary' => false,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            $deletedAdditional = $request->input('deleted_additional_images');
            if (!empty($deletedAdditional) && is_array($deletedAdditional)) {
                foreach ($deletedAdditional as $imageId) {
                    $image = ProductImage::find($imageId);
                    if ($image && $image->product_id === $product->id) {
                        $existingFile = public_path('uploads/' . $image->image_path);
                        if (file_exists($existingFile)) {
                            @unlink($existingFile);
                        }
                        $image->delete();
                    }
                }
            }

            if (!$product->images()->where('is_primary', true)->exists()) {
                $firstImg = $product->images()->first();
                if ($firstImg) {
                    $firstImg->update(['is_primary' => true]);
                }
            }

            if ($request->type === 'variable' && $request->filled('variants_json')) {
                
                $duplicates = $product->variants()
                    ->select('attribute_value_id', DB::raw('MIN(id) as keep_id'))
                    ->groupBy('attribute_value_id')
                    ->get();
                $keepIds = $duplicates->pluck('keep_id')->toArray();
                $product->variants()->whereNotIn('id', $keepIds)->delete();

                $variants = json_decode($request->variants_json, true);
                $existingVariants = $product->variants()->get()->keyBy('attribute_value_id');
                $keptAttributeValueIds = [];

                foreach ($variants as $item) {
                    $attributeValueId = $item['attribute_value_id'];
                    $keptAttributeValueIds[] = $attributeValueId;

                    $variantSizes = $request->has('pair_product') ? ($item['custom_sizes'] ?? []) : [];
                    $variantData = [
                        'purchase_price' => $item['purchase_price'] ?? 0,
                        'sale_price'     => $item['sale_price'] ?? 0,
                        'status'         => ($item['status'] ?? 1) == 1 ? 1 : 2,
                        'custom_sizes'   => !empty($variantSizes) ? $variantSizes : null,
                    ];

                    if ($existingVariant = $existingVariants->get($attributeValueId)) {
                        $existingVariant->update($variantData);
                    } else {
                        ProductVariant::create($variantData + [
                            'product_id'         => $product->id,
                            'attribute_value_id' => $attributeValueId,
                        ]);
                    }
                }

                $deletedVariantIds = $product->variants()
                    ->whereNotIn('attribute_value_id', $keptAttributeValueIds)
                    ->pluck('id')
                    ->toArray();

                $stockMigrationInput = array_filter($request->input('stock_migration', []), fn($q) => (int)$q > 0);

                if (!$wasNormal && !empty($deletedVariantIds) && !empty($stockMigrationInput)) {
                    $requestedAllocations = [];
                    foreach ($stockMigrationInput as $attrValId => $qty) {
                        $qtyNeeded = (int) $qty;
                        if ($qtyNeeded > 0) {
                            $variant = $product->variants()->where('attribute_value_id', $attrValId)->first();
                            if ($variant) {
                                $requestedAllocations[$variant->id] = $qtyNeeded;
                            }
                        }
                    }
                    if (!empty($requestedAllocations)) {
                        $this->reassignVariantStock($product, $deletedVariantIds, $requestedAllocations);
                    }
                }

                if (!empty($deletedVariantIds)) {
                    \App\Models\PurchaseItem::whereIn('product_variant_id', $deletedVariantIds)->update(['product_variant_id' => null]);
                    \App\Models\OrderItem::whereIn('product_variant_id', $deletedVariantIds)->update(['product_variant_id' => null]);
                    \App\Models\PurchaseBillItem::whereIn('product_variant_id', $deletedVariantIds)->update(['product_variant_id' => null]);
                }

                $product->variants()->whereNotIn('attribute_value_id', $keptAttributeValueIds)->delete();

                if ($wasNormal && !empty($stockMigrationInput)) {
                    $requestedAllocations = [];
                    foreach ($stockMigrationInput as $attrValId => $qty) {
                        $qtyNeeded = (int) $qty;
                        if ($qtyNeeded > 0) {
                            $variant = $product->variants()->where('attribute_value_id', $attrValId)->first();
                            if ($variant) {
                                $requestedAllocations[$variant->id] = $qtyNeeded;
                            }
                        }
                    }

                    if (!empty($requestedAllocations)) {
                        $unassignedPurchaseItems = \App\Models\PurchaseItem::with('allocations')
                            ->where('product_id', $product->id)
                            ->whereNull('product_variant_id')
                            ->get();

                        foreach ($unassignedPurchaseItems as $pItem) {
                            $itemQty = (int)$pItem->quantity;
                            if ($itemQty <= 0) continue;

                            foreach ($requestedAllocations as $variantId => &$neededQty) {
                                if ($neededQty <= 0) continue;

                                $assignQty = min($itemQty, $neededQty);
                                if ($assignQty <= 0) continue;

                                if ($assignQty == $itemQty) {
                                    $pItem->update(['product_variant_id' => $variantId]);
                                } else {
                                    $newItem = \App\Models\PurchaseItem::create([
                                        'purchase_id'        => $pItem->purchase_id,
                                        'product_id'         => $product->id,
                                        'product_variant_id' => $variantId,
                                        'quantity'           => $assignQty,
                                        'custom_size_value'  => $pItem->custom_size_value,
                                        'purchase_price'     => $pItem->purchase_price,
                                        'total'              => $assignQty * $pItem->purchase_price,
                                    ]);

                                    foreach ($pItem->allocations as $alloc) {
                                        $allocRatioQty = min($alloc->quantity, $assignQty);
                                        if ($allocRatioQty > 0) {
                                            \App\Models\PurchaseAllocation::create([
                                                'purchase_item_id' => $newItem->id,
                                                'location_id'      => $alloc->location_id,
                                                'quantity'         => $allocRatioQty,
                                            ]);
                                            $alloc->decrement('quantity', $allocRatioQty);
                                        }
                                    }
                                    $pItem->decrement('quantity', $assignQty);
                                    $pItem->update(['total' => $pItem->quantity * $pItem->purchase_price]);
                                }

                                $neededQty -= $assignQty;
                                $itemQty -= $assignQty;

                                if ($itemQty <= 0) break;
                            }
                        }
                        unset($neededQty);

                        $remainingRequested = array_filter($requestedAllocations, fn($q) => $q > 0);
                        if (!empty($remainingRequested)) {
                            $inventories = \App\Models\Inventory::where('product_id', $product->id)->where('quantity', '>', 0)->get();
                            foreach ($inventories as $inv) {
                                $hasAllocations = \App\Models\PurchaseAllocation::where('location_id', $inv->location_id)
                                    ->whereHas('purchaseItem', function ($q) use ($product) {
                                        $q->where('product_id', $product->id);
                                    })->exists();

                                if (!$hasAllocations) {
                                    $supplierId = \App\Models\Supplier::first()?->id;
                                    if (!$supplierId) {
                                        $supplier   = \App\Models\Supplier::create(['name' => 'Default Supplier', 'status' => 1]);
                                        $supplierId = $supplier->id;
                                    }

                                    $dummyPurchase = \App\Models\Purchase::create([
                                        'invoice_no'    => 'MIG-' . $product->id . '-' . time(),
                                        'supplier_id'   => $supplierId,
                                        'purchase_date' => now(),
                                        'status'        => 2, // Approved
                                        'notes'         => 'Stock migration from Normal product to Variants',
                                        'created_by'    => auth()->id(),
                                    ]);

                                    $invQtyAvailable = (int)$inv->quantity;
                                    foreach ($remainingRequested as $variantId => &$reqQty) {
                                        if ($reqQty <= 0 || $invQtyAvailable <= 0) continue;

                                        $migQty = min($invQtyAvailable, $reqQty);

                                        $pItem = \App\Models\PurchaseItem::create([
                                            'purchase_id'        => $dummyPurchase->id,
                                            'product_id'         => $product->id,
                                            'product_variant_id' => $variantId,
                                            'quantity'           => $migQty,
                                            'custom_size_value'  => 1,
                                            'purchase_price'     => $product->purchase_price,
                                            'total'              => $migQty * $product->purchase_price,
                                        ]);

                                        \App\Models\PurchaseAllocation::create([
                                            'purchase_item_id' => $pItem->id,
                                            'location_id'      => $inv->location_id,
                                            'quantity'         => $migQty,
                                        ]);

                                        $reqQty -= $migQty;
                                        $invQtyAvailable -= $migQty;
                                    }
                                    unset($reqQty);
                                }
                            }
                        }
                    }
                }
            } elseif ($request->type === 'normal') {
                $product->variants()->delete();

                \App\Models\PurchaseItem::where('product_id', $product->id)->update(['product_variant_id' => null]);
                \App\Models\OrderItem::where('product_id', $product->id)->update(['product_variant_id' => null]);
                \App\Models\PurchaseBillItem::where('product_id', $product->id)->update(['product_variant_id' => null]);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Product updated successfully.',
        ]);
    }

    public function destroyImage(ProductImage $image)
    {
        $this->authorize('edit products');

        $wasPrimary = $image->is_primary;
        $productId  = $image->product_id;

        $existingFile = public_path('uploads/' . $image->image_path);
        if (file_exists($existingFile)) {
            @unlink($existingFile);
        }
        $image->delete();

        if ($wasPrimary) {
            ProductImage::where('product_id', $productId)->first()?->update(['is_primary' => true]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Image deleted successfully.',
        ]);
    }

    public function setPrimaryImage(ProductImage $image)
    {
        $this->authorize('edit products');

        ProductImage::where('product_id', $image->product_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Primary image updated.',
        ]);
    }

    public function toggleStatus(Product $product)
    {
        $this->authorize('edit products');

        $product->update([
            'status' => $product->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Product status updated successfully.',
        ]);
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete products');

        DB::transaction(function () use ($product) {
            $inventories = Inventory::where('product_id', $product->id)->where('quantity', '>', 0)->get();
            foreach ($inventories as $inventory) {
                $oldQty = $inventory->quantity;
                $inventory->update(['quantity' => 0]);
                ActivityLogger::log('Inventory', 'update', $inventory, ['quantity' => $oldQty], ['quantity' => 0], 'Stock cleared for deleted product ' . $product->name);
            }

            $product->delete();
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function getSubCategories(Request $request)
    {
        $this->authorize('view products');
        $categoryId = $request->query('category_id');
        $subCategories = SubCategory::where('category_id', $categoryId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subCategories);
    }

    public function search(Request $request)
    {
        $this->authorize('view products');

        $query = Product::where('status', 1)
            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->q . '%')
                        ->orWhere('barcode', 'like', '%' . $request->q . '%');
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'barcode']);

        return response()->json($query->map(function ($product) {
            return [
                'id' => $product->id,
                'text' => $product->barcode ? $product->name . ' (' . $product->barcode . ')' : $product->name,
                'name' => $product->name,
            ];
        }));
    }

    private function reassignVariantStock(Product $product, array $sourceVariantIds, array $requestedAllocations): void
    {
        $hasNullSource = in_array(null, $sourceVariantIds, true);
        $nonNullSourceIds = array_values(array_filter($sourceVariantIds, fn($v) => $v !== null));
        $matchSource = function ($query) use ($hasNullSource, $nonNullSourceIds) {
            $query->where(function ($q) use ($hasNullSource, $nonNullSourceIds) {
                if ($hasNullSource) {
                    $q->whereNull('product_variant_id');
                }
                if (!empty($nonNullSourceIds)) {
                    $hasNullSource
                        ? $q->orWhereIn('product_variant_id', $nonNullSourceIds)
                        : $q->whereIn('product_variant_id', $nonNullSourceIds);
                }
            });
        };

        $totalRequested = array_sum($requestedAllocations);
        if ($totalRequested <= 0) {
            return;
        }

        $ratios = [];
        foreach ($requestedAllocations as $variantId => $qty) {
            $ratios[$variantId] = $qty / $totalRequested;
        }

        $purchaseItems = \App\Models\PurchaseItem::with('allocations')
            ->where('product_id', $product->id)
            ->where($matchSource)
            ->get();

        foreach ($purchaseItems as $pItem) {
            $itemQty = (int) $pItem->quantity;
            if ($itemQty <= 0) {
                continue;
            }

            $allocations = $pItem->allocations;
            $variantBuckets = [];
            foreach ($allocations as $alloc) {
                $allocSplit = $this->splitQuantityByRatio((int) $alloc->quantity, $ratios);
                foreach ($allocSplit as $variantId => $qty) {
                    if ($qty <= 0) {
                        continue;
                    }
                    $variantBuckets[$variantId]['qty'] = ($variantBuckets[$variantId]['qty'] ?? 0) + $qty;
                    $variantBuckets[$variantId]['locations'][$alloc->location_id]
                        = ($variantBuckets[$variantId]['locations'][$alloc->location_id] ?? 0) + $qty;
                }
            }
            if (empty($variantBuckets)) {
                continue;
            }

            $pItem->allocations()->delete();

            $variantIds = array_keys($variantBuckets);
            $primaryVariantId = array_shift($variantIds);
            $primary = $variantBuckets[$primaryVariantId];

            $pItem->update([
                'product_variant_id' => $primaryVariantId,
                'quantity'           => $primary['qty'],
                'total'              => $primary['qty'] * $pItem->purchase_price,
            ]);
            foreach ($primary['locations'] as $locId => $qty) {
                \App\Models\PurchaseAllocation::create([
                    'purchase_item_id' => $pItem->id,
                    'location_id'      => $locId,
                    'quantity'         => $qty,
                ]);
            }

            foreach ($variantIds as $variantId) {
                $bucket = $variantBuckets[$variantId];
                $newItem = \App\Models\PurchaseItem::create([
                    'purchase_id'        => $pItem->purchase_id,
                    'product_id'         => $product->id,
                    'product_variant_id' => $variantId,
                    'quantity'           => $bucket['qty'],
                    'custom_size_value'  => $pItem->custom_size_value,
                    'purchase_price'     => $pItem->purchase_price,
                    'total'              => $bucket['qty'] * $pItem->purchase_price,
                ]);
                foreach ($bucket['locations'] as $locId => $qty) {
                    \App\Models\PurchaseAllocation::create([
                        'purchase_item_id' => $newItem->id,
                        'location_id'      => $locId,
                        'quantity'         => $qty,
                    ]);
                }
            }
        }

        $transferItems = \App\Models\PurchaseBillItem::where('product_id', $product->id)
            ->where($matchSource)
            ->get();

        foreach ($transferItems as $tItem) {
            $itemQty = (int) $tItem->quantity;
            if ($itemQty <= 0) {
                continue;
            }

            $split = $this->splitQuantityByRatio($itemQty, $ratios);
            $lastVariantId = array_key_last($split);

            foreach ($split as $variantId => $qty) {
                if ($qty <= 0) {
                    continue;
                }
                if ($variantId === $lastVariantId) {
                    $tItem->update(['product_variant_id' => $variantId, 'quantity' => $qty]);
                    continue;
                }
                \App\Models\PurchaseBillItem::create([
                    'purchase_bill_id'   => $tItem->purchase_bill_id,
                    'product_id'         => $product->id,
                    'product_variant_id' => $variantId,
                    'pair_type'          => $tItem->pair_type,
                    'custom_size_value'  => $tItem->custom_size_value,
                    'quantity'           => $qty,
                ]);
            }
        }

        $orderItems = \App\Models\OrderItem::where('product_id', $product->id)
            ->where($matchSource)
            ->orderByDesc('quantity')
            ->get();

        $remaining = $requestedAllocations;
        foreach ($orderItems as $item) {
            $itemQty = (int) $item->quantity;
            if ($itemQty <= 0) {
                continue;
            }
            foreach ($remaining as $variantId => &$neededQty) {
                if ($neededQty >= $itemQty) {
                    $item->update(['product_variant_id' => $variantId]);
                    $neededQty -= $itemQty;
                    break;
                }
            }
            unset($neededQty);
        }
    }

    private function splitQuantityByRatio(int $totalQty, array $ratios): array
    {
        $floors = [];
        $remainders = [];
        $floorSum = 0;
        foreach ($ratios as $variantId => $ratio) {
            $exact = $totalQty * $ratio;
            $floor = (int) floor($exact);
            $floors[$variantId] = $floor;
            $remainders[$variantId] = $exact - $floor;
            $floorSum += $floor;
        }

        $leftover = $totalQty - $floorSum;
        arsort($remainders);
        foreach (array_keys($remainders) as $variantId) {
            if ($leftover <= 0) {
                break;
            }
            $floors[$variantId]++;
            $leftover--;
        }

        return array_filter($floors, fn ($q) => $q > 0);
    }

    private function validateVariantCustomSizes(array $variants, \Closure $fail): void
    {
        foreach ($variants as $variant) {
            $sizes = $variant['custom_sizes'] ?? [];
            if (!is_array($sizes) || empty($sizes)) {
                continue;
            }
            foreach ($sizes as $row) {
                $sizeText = isset($row['size']) ? ($row['size'] . ' pcs') : 'pair size';
                if (isset($row['size']) && ((float) $row['size'] > 4 || (float) $row['size'] <= 0)) {
                    $fail("Pair size ({$sizeText}) cannot be greater than 4 pcs.");
                    return;
                }
                if (!isset($row['sale_price']) || $row['sale_price'] === null || $row['sale_price'] === '' || !is_numeric($row['sale_price']) || (float) $row['sale_price'] <= 0) {
                    $fail("Sale Price ({$sizeText}) is required.");
                    return;
                }
                if (!isset($row['mrp']) || $row['mrp'] === null || $row['mrp'] === '' || !is_numeric($row['mrp']) || (float) $row['mrp'] <= 0) {
                    $fail("MRP ({$sizeText}) is required.");
                    return;
                }
            }
        }
    }

    private function saveBase64Image($base64Data, $subDir = 'products')
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $dataString = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]);
            
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                return null;
            }
            
            $decodedData = base64_decode($dataString);
            if ($decodedData === false) {
                return null;
            }
            
            if (strlen($decodedData) > 52428800) {
                return null;
            }
            
            $dir = public_path('uploads/' . $subDir);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $filename = time() . '_' . uniqid() . '.' . $type;
            file_put_contents($dir . '/' . $filename, $decodedData);
            return $subDir . '/' . $filename;
        }
        return null;
    }

    private function copyProductImageFile($originalPath)
    {
        if (empty($originalPath)) {
            return null;
        }

        $sourceFile = public_path('uploads/' . $originalPath);
        if (file_exists($sourceFile)) {
            $pathInfo = pathinfo($originalPath);
            $newFilename = time() . '_' . uniqid() . '.' . ($pathInfo['extension'] ?? 'jpg');
            $subDir = $pathInfo['dirname'] ?? 'products';
            $destDir = public_path('uploads/' . $subDir);
            
            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }

            if (copy($sourceFile, $destDir . '/' . $newFilename)) {
                return $subDir . '/' . $newFilename;
            }
        }
        return null;
    }

    public function generateBarcodeImage($id)
    {
        $product = Product::withTrashed()->find($id);
        if (!$product) {
            return response('Product Not Found', 404);
        }

        $barcodeText = $product->barcode;
        if (empty($barcodeText)) {
            return response('No Barcode Found', 404);
        }

        $generator = new BarcodeGeneratorPNG();
        $pngData = $generator->getBarcode($barcodeText, $generator::TYPE_CODE_128, 5, 120);

        return response($pngData, 200)->header('Content-Type', 'image/png');
    }

}
