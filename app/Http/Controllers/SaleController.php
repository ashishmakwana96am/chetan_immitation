<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\OrderCancellationRequest;
use App\Models\State;
use App\Models\CustomerAddress;
use App\Models\OrderPayment;
use App\Models\Setting;
use App\Mail\OrderStatusMail;

class SaleController extends Controller
{
    public function index()
    {
        $this->authorize('view sales');
        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $isSuperAdmin = $user->hasRole('super-admin');
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::orderBy('name')->get();
        }
        return view('sales.index', compact('locations', 'isRestricted', 'isSuperAdmin'));
    }

    public function data(Request $request)
    {
        $this->authorize('view sales');

        $user      = auth()->user();
        $orders    = Order::with(['customer', 'location', 'user', 'items.product', 'cancellationRequest'])
            ->where('order_type', 'sale')
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('location_id', $user->location_id))
            ->when($request->location_id, function($q) use ($request) {
                $q->where('location_id', $request->location_id);
            })
            ->when($request->status, function($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->payment_status, function($q) use ($request) {
                $q->where('payment_status', $request->payment_status);
            })
            ->when($request->source, function($q) use ($request) {
                $q->where('source', $request->source);
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
        $canEdit                   = auth()->user()->can('edit sales');
        $canEditSalesStatus        = auth()->user()->can('edit sales status');
        $canEditSalesPaymentStatus = auth()->user()->can('edit sales payment status');
        $canDownloadSales          = auth()->user()->can('download sales');

        $onlineOrders = $orders->where('source', 'ONLINE');
        $productIds   = $onlineOrders->flatMap(fn ($o) => $o->items->pluck('product_id'))->unique()->values();
        $inventoryByProduct = Inventory::whereIn('product_id', $productIds)
            ->with('location')
            ->get()
            ->groupBy('product_id');

        $statusColors = [
            1 => 'bg-label-secondary',
            2 => 'bg-label-success',
            3 => 'bg-label-info',
            4 => 'bg-label-warning',
            5 => 'bg-label-success',
            6 => 'bg-label-danger',
        ];
        $statusLabels = [
            1 => 'Pending',
            2 => 'Approve',
            3 => 'Shipped',
            4 => 'Out for delivery',
            5 => 'Delivered',
            6 => 'Cancelled',
        ];

        $paymentColors = [
            1 => 'bg-label-warning',
            2 => 'bg-label-info',
        ];
        $paymentLabels = [
            1 => 'Pending',
            2 => 'Paid',
        ];

        $data = $orders->map(function ($order, $index) use ($canEdit, $canEditSalesStatus, $canEditSalesPaymentStatus, $canDownloadSales, $statusColors, $statusLabels, $paymentColors, $paymentLabels, $inventoryByProduct) {
            $cancellationRequested = false;
            $cancellationWarningHtml = '';
            if ($order->cancellationRequest && $order->cancellationRequest->status === 'pending') {
                $cancellationRequested = true;
                $cancellationWarningHtml = ' <i class="ti ti-alert-triangle text-danger fs-5 align-middle cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="Cancellation Requested: ' . e($order->cancellationRequest->cancellation_reason) . '"></i>';
            }

            $stockWarningHtml = '';
            $isFallbackOrder = !$order->is_default;

            if ($isFallbackOrder && (int)$order->status === Order::STATUS_PENDING) {
                $issueBlocks = [];
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        $product = $item->product;
                        $stockData = $product ? $product->getVariantStock() : [];
                        $qtyAtLocation = (int) ($stockData[$order->location_id]['variants'][$item->product_variant_id] ?? 0);
                        $otherLocations = collect($stockData)
                            ->filter(fn ($d, $locId) => (int) $locId !== (int) $order->location_id)
                            ->map(fn ($d) => ['name' => $d['location_name'], 'qty' => (int) ($d['variants'][$item->product_variant_id] ?? 0)])
                            ->filter(fn ($d) => $d['qty'] > 0);
                    } else {
                        $invRows = $inventoryByProduct->get($item->product_id, collect());
                        $qtyAtLocation = (int) ($invRows->firstWhere('location_id', $order->location_id)->quantity ?? 0);
                        $otherLocations = $invRows->where('location_id', '!=', $order->location_id)
                            ->filter(fn ($inv) => $inv->quantity > 0)
                            ->map(fn ($inv) => ['name' => $inv->location->name ?? 'Unknown', 'qty' => (int) $inv->quantity]);
                    }

                    if ($qtyAtLocation < $item->quantity) {
                        $otherRows = $otherLocations
                            ->map(function ($d) {
                                return "<div class='sw-branch'><span>" . e($d['name']) . "</span><span class='sw-qty'>" . $d['qty'] . "</span></div>";
                            })
                            ->implode('');

                        $availabilitySection = $otherRows !== ''
                            ? "<div class='sw-subtitle'>Available in other branches</div>" . $otherRows
                            : "<div class='sw-empty'>Not available in any other branch</div>";

                        $issueBlocks[] = "<div class='sw-item'>"
                            . "<div class='sw-title'><i class='ti ti-box'></i>" . e($item->product->name ?? 'Product') . "</div>"
                            . "<div class='sw-status'><i class='ti ti-alert-circle'></i>Not available at this location</div>"
                            . $availabilitySection
                            . "</div>";
                    }
                }

                if (!empty($issueBlocks)) {
                    $tooltipHtml = implode('', $issueBlocks);
                    $stockWarningHtml = ' <i class="ti ti-alert-triangle text-warning fs-5 align-middle cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="stock-warning-tooltip" title="' . e($tooltipHtml) . '"></i>';
                }
            }

            $status        = '<span class="badge ' . ($statusColors[$order->status] ?? 'bg-label-secondary') . '">' . ($statusLabels[$order->status] ?? ucfirst($order->status)) . '</span>';
            if ($order->status == 6 && !empty($order->cancellation_reason)) {
                $status .= ' <i class="fas fa-info-circle text-danger fs-5 ms-1 align-middle cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="' . e($order->cancellation_reason) . '"></i>';
            }
            $paymentStatus = '<span class="badge ' . ($paymentColors[$order->payment_status] ?? 'bg-label-secondary') . '">' . ($paymentLabels[$order->payment_status] ?? ucfirst($order->payment_status)) . '</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.sales.show', $order) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            if ($canDownloadSales) {
                if (($order->source ?? 'POS') === 'ONLINE') {
                    $actions .= '<a href="' . route('admin.sales.pdf', $order) . '" class="dropdown-item" target="_blank"><i class="ti ti-file-text me-2"></i>Invoice</a>';
                    $actions .= '<a href="' . route('admin.sales.label', [$order, 'auto_print' => 1]) . '" class="dropdown-item" target="_blank"><i class="ti ti-printer me-2"></i>Print Label</a>';
                } else {
                    $actions .= '<a href="' . route('admin.sales.thermal', [$order, 'auto_print' => 1]) . '" class="dropdown-item" target="_blank"><i class="ti ti-printer me-2"></i>Invoice</a>';
                    if ($order->is_gst) {
                        $actions .= '<a href="' . route('admin.sales.tax-invoice', [$order, 'auto_print' => 1]) . '" class="dropdown-item" target="_blank"><i class="ti ti-file-text me-2"></i>Tax Invoice</a>';
                    }
                }
            }
            $isEditable = ($order->source ?? 'POS') === 'POS'
                && in_array((int) $order->status, [Order::STATUS_PENDING, Order::STATUS_APPROVE], true)
                && !$cancellationRequested;
            if ($canEdit && $isEditable) {
                $actions .= '<a href="' . route('admin.sales.edit', $order) . '" class="dropdown-item"><i class="ti ti-pencil me-2"></i>Edit</a>';
            }

            $showStatusOption = true;
            $showPaymentOption = true;
            $sourceVal = $order->source ?? 'POS';

            if ($sourceVal === 'POS') {
                if ($order->status == 2) { // 2 = Approve
                    $showStatusOption = false;
                }
                if ($order->payment_status == 2) { // 2 = Paid
                    $showPaymentOption = false;
                }
            } elseif ($sourceVal === 'ONLINE') {
                if (in_array($order->status, [5, 6])) { // 5 = Delivered, 6 = Decline
                    $showStatusOption = false;
                }
                if ($order->payment_status == 2) { // 2 = Paid
                    $showPaymentOption = false;
                }
            }

            if ($canEditSalesStatus && $showStatusOption) {
                $actions .= '<button class="dropdown-item change-sale-status-btn" data-url="' . route('admin.sales.status', $order) . '" data-current="' . $order->status . '" data-source="' . ($order->source ?? 'POS') . '" data-shipped-url="' . e($order->shipped_client_url ?? '') . '" data-tracking-id="' . e($order->tracking_id ?? '') . '" data-cancel-reason="' . e($order->cancellation_reason ?? '') . '"><i class="ti ti-adjustments-horizontal me-2"></i>Update Status</button>';
            }
            if ($canEditSalesPaymentStatus && $showPaymentOption) {
                $actions .= '<button class="dropdown-item change-payment-status-btn" data-url="' . route('admin.sales.status', $order) . '" data-current="' . $order->payment_status . '"><i class="ti ti-credit-card me-2"></i>Update Payment Status</button>';
            }
            $actions .= '</div></div>';
            $sourceVal = $order->source ?? 'POS';
            $sourceBadge = $sourceVal === 'ONLINE'
                ? '<span class="badge bg-label-success">ONLINE</span>'
                : '<span class="badge bg-label-info">POS</span>';

            return [
                'cancellation_requested' => $cancellationRequested,
                'cancellation_warning'   => $cancellationWarningHtml,
                'index'          => $index + 1,
                'is_default'     => (bool) $order->is_default,
                'stock_warning'  => $stockWarningHtml,
                'order_no'       => '<code>' . $order->order_no . '</code>' . ($order->is_gst ? ' <span class="badge bg-label-success ms-1 fs-tiny" style="font-size: 0.65rem;">GST</span>' : ''),
                'customer'       => $order->customer->name ?? '<span class="text-muted">Walk-in</span>',
                'location'       => $order->location->name ?? '-',
                'source'         => $sourceBadge,
                'final_amount'   => format_price($order->final_amount),
                'status'         => $status,
                'payment_status' => $paymentStatus,
                'payment_method' => $order->payment_method === 'cod' ? 'COD' : ucwords(str_replace('_', ' ', $order->payment_method)),
                'date_group'     => $order->created_at->format('d M Y'),
                'date_sort'      => $order->created_at->format('Ymd'),
                'actions'        => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function create()
    {
        $this->authorize('create sales');
        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $customers   = Customer::where('status', 1)->orderBy('name')->get();
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }
        $products    = Product::with(['variants.attributeValue.attribute', 'primaryImage', 'inventories'])->where('status', 1)->orderBy('name')->get();
        $orderNo     = generate_invoice_no('SA', Order::class, 'order_no');
        $variantStockMap = Product::getVariantStockBatch($products->where('type', 'variable'));
        $allProducts = $products->map(function ($p) use ($variantStockMap) {
            $data = [
                'id'              => $p->id,
                'name'            => $p->name,
                'price'           => $p->sale_price,
                'barcode'         => $p->barcode,
                'label'           => $p->barcode ? ($p->name . ' (' . $p->barcode . ')') : $p->name,
                'type'            => $p->type,
                'image'           => $p->primaryImage ? $p->primaryImage->image_url : null,
                'pair_product'    => (bool) $p->pair_product,
                'pair_mode'       => $p->pair_mode,
                'custom_sizes'    => $p->custom_sizes ?? [],
                'single_price'    => $p->sale_price,
                'purchase_price'  => $p->purchase_price,
                'bypass_min_price' => (bool) $p->bypass_min_price,
            ];
            if ($p->type === 'variable') {
                $data['variants'] = $p->variants->filter(function($v) {
                    return $v->status == 1;
                })->values()->map(function($v) {
                    return [
                        'id' => $v->id,
                        'attribute_value_id' => $v->attribute_value_id,
                        'purchase_price' => $v->purchase_price,
                        'sale_price' => $v->sale_price,
                        'custom_sizes' => $v->custom_sizes ?? [],
                        'attr_name' => $v->attributeValue->attribute->name ?? '',
                        'value_name' => $v->attributeValue->value ?? '',
                    ];
                })->all();
            }

            // Calculate stock by location
            $stockByLocation = [];
            if ($p->type === 'variable') {
                $variantStock = $variantStockMap[$p->id] ?? [];
                foreach ($variantStock as $locId => $locData) {
                    $stockByLocation[$locId] = [
                        'parent' => $locData['parent'],
                        'variants' => $locData['variants']
                    ];
                }
            } else {
                foreach ($p->inventories as $inv) {
                    $stockByLocation[$inv->location_id] = $inv->quantity;
                }
            }
            $data['stock_by_location'] = $stockByLocation;

            return $data;
        })->values();
        $defaultLocationId = $isRestricted ? $user->location_id : null;
        return view('sales.create', compact('customers', 'locations', 'products', 'orderNo', 'allProducts', 'isRestricted', 'defaultLocationId'));
    }

    public function store(Request $request)
    {
        $this->authorize('create sales');

        $authUser = auth()->user();
        $isRestricted = $authUser->location_id && !$authUser->hasRole('super-admin');
        if ($isRestricted && (int)$request->location_id !== (int)$authUser->location_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You can only create sales for your assigned location.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'location_id'            => ['required', 'exists:locations,id'],
            'customer_id'            => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value !== '0' && !\DB::table('customers')->where('id', $value)->exists()) {
                        $fail('The selected customer is invalid.');
                    }
                }
            ],
            'payment_method'         => ['required', 'string'],
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_id'         => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.pair_type'          => ['nullable', 'string', 'in:single,pair'],
            'items.*.custom_size_value'  => ['nullable', 'numeric', 'min:0.01'],
            'items.*.quantity'           => ['required', 'integer', 'min:1'],
            'items.*.price'              => ['required', 'numeric', 'min:0.01'],
            'items.*.discount_type'      => ['nullable', 'string', 'in:flat,percentage'],
            'items.*.discount_value'     => ['nullable', 'numeric', 'min:0'],
            'discount_type'              => ['nullable', 'string', 'in:flat,percentage,MANUAL,COUPON'],
            'discount_value'             => ['nullable', 'numeric', 'min:0'],
            'order_discount_type'        => ['nullable', 'string', 'in:flat,percentage'],
            'order_discount_value'       => ['nullable', 'numeric', 'min:0'],
            'status'                     => ['nullable', 'integer', 'in:1,2,6'],
            'payment_status'             => ['nullable', 'integer', 'in:1,2'],
            'source'                     => ['nullable', 'string', 'in:POS,ONLINE'],
            'coupon_id'                  => ['nullable', 'exists:coupons,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->customer_id === '0' || $request->customer_id === '') {
            $request->merge(['customer_id' => null]);
        }

        $isApprove = ($request->status ?? 2) == 2;

        $customSizeError = $this->getCustomSizeError($request->items);
        if ($customSizeError) {
            return response()->json([
                'status'  => 'error',
                'message' => ['items' => [$customSizeError]],
            ], 422);
        }

        $minPriceError = $this->getMinPriceError(
            $request->items,
            $request->order_discount_type ?? 'flat',
            (float) ($request->order_discount_value ?? 0)
        );
        if ($minPriceError) {
            return response()->json([
                'status'  => 'error',
                'message' => ['items' => [$minPriceError]],
            ], 422);
        }

        if ($isApprove) {
            $stockError = $this->getStockError($request->items, (int) $request->location_id);
            if ($stockError) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['items' => [$stockError]],
                ], 422);
            }
        }

        $order = null;

        DB::transaction(function () use ($request, $isApprove, &$order) {
            $totalAmount = 0.0;
            $itemsData = [];

            foreach ($request->items as $itemData) {
                $qty = (int)$itemData['quantity'];
                $price = (float)$itemData['price'];
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
                $totalAmount += $itemTotal;

                $itemsData[] = [
                    'product_id'         => $itemData['product_id'],
                    'product_variant_id' => $itemData['product_variant_id'] ?? null,
                    'pair_type'          => $itemData['pair_type'] ?? 'single',
                    'custom_size_value'  => (isset($itemData['custom_size_value']) && $itemData['custom_size_value'] !== '') ? (float) $itemData['custom_size_value'] : null,
                    'quantity'           => $qty,
                    'price'              => $price,
                    'discount_type'      => $discType,
                    'discount_value'     => $discVal,
                    'discount_amount'    => $discAmount,
                    'total'              => $itemTotal,
                ];
            }

            $discVal = (float)($request->order_discount_value ?? 0);
            $discType = $discVal > 0 ? ($request->order_discount_type ?? 'flat') : null;

            $orderDiscountAmount = 0.0;
            if ($discVal > 0) {
                if ($discType === 'flat') {
                    $orderDiscountAmount = $discVal;
                } else if ($discType === 'percentage') {
                    $orderDiscountAmount = $totalAmount * ($discVal / 100);
                }
            }

            if ($orderDiscountAmount > $totalAmount) {
                $orderDiscountAmount = $totalAmount;
            }

            $finalAmount = $totalAmount - $orderDiscountAmount;

            $source = $request->input('source', 'POS');
            $isGst = $request->boolean('is_gst');
            if ($source === 'ONLINE') {
                $isGst = false;
            }
            $taxAmount = 0.0;
            $orderPrefix = 'SA';

            if ($isGst) {
                $orderPrefix = 'GS';
                $gstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);
                $taxAmount = $finalAmount * ($gstRate / 100);
            }

            $grandTotal = round($finalAmount + $taxAmount);

            $order = Order::create([
                'customer_id'          => $request->customer_id,
                'location_id'          => $request->location_id,
                'user_id'              => auth()->id(),
                'order_no'             => generate_invoice_no($orderPrefix, Order::class, 'order_no'),
                'order_type'           => 'sale',
                'status'               => $request->status ?? 2,
                'payment_status'       => $request->payment_status ?? 2,
                'payment_method'       => $request->payment_method,
                'is_gst'               => $isGst,
                'tax_amount'           => $taxAmount,
                'final_amount'         => $grandTotal,
                'source'               => $source,
                'order_discount_type'  => $discType,
                'order_discount_value' => $discVal,
                'discount_type'        => in_array($request->discount_type, ['MANUAL', 'COUPON']) ? $request->discount_type : 'MANUAL',
                'coupon_id'            => $request->input('coupon_id', null),
            ]);

            foreach ($itemsData as $item) {
                OrderItem::create([
                    'order_id'           => $order->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'pair_type'          => $item['pair_type'],
                    'custom_size_value'  => $item['custom_size_value'],
                    'quantity'           => $item['quantity'],
                    'price'              => $item['price'],
                    'discount_type'      => $item['discount_type'],
                    'discount_value'     => $item['discount_value'],
                    'discount_amount'    => $item['discount_amount'],
                    'total'              => $item['total'],
                ]);

                if ($isApprove) {
                    $stockDeduct = (int) round($item['quantity'] * $this->stockMultiplierFor((int) $item['product_id'], $item['pair_type'], $item['custom_size_value']));
                    $this->logInventoryChange((int) $item['product_id'], (int) $request->location_id, -$stockDeduct, 'Stock deducted for new sale #' . $order->order_no);
                }
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Sale created successfully.', 'id' => $order->id]);
    }

    public function show(Order $sale)
    {
        $this->authorize('view sales');

        // Prevent location user from viewing other locations' sales
        if (auth()->user()->location_id && !auth()->user()->hasRole('super-admin') && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        $sale->load(['customer', 'location', 'user', 'coupon', 'customerAddress', 'items.product.variants.attributeValue.attribute', 'items.product.primaryImage', 'payment', 'cancellationRequest']);
        return view('sales.show', ['order' => $sale]);
    }

    public function pdf(Order $sale)
    {
        $this->authorize('view sales');

        if (auth()->user()->location_id && !auth()->user()->hasRole('super-admin') && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        if (request()->boolean('auto_print') && !request()->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Sale Invoice ' . $sale->order_no,
                'pdfUrl' => route('admin.sales.pdf', [$sale, 'auto_print' => 1, 'stream' => 1]),
            ]);
        }

        $sale->load(['customer', 'location', 'user', 'coupon', 'customerAddress', 'items.product.variants.attributeValue.attribute', 'payment', 'cancellationRequest']);

        $pdf = Pdf::loadView('sales.pdf', ['order' => $sale])
            ->setPaper('a4', 'portrait');

        ActivityLogger::log('Sales', 'export', $sale, null, null, 'Invoice PDF exported for sale #' . $sale->order_no);

        if (request()->boolean('stream')) {
            return $pdf->stream('sale-' . $sale->order_no . '.pdf');
        }

        return $pdf->download('sale-' . $sale->order_no . '.pdf');
    }

    public function thermal(Order $sale)
    {
        $this->authorize('view sales');

        if (auth()->user()->location_id && !auth()->user()->hasRole('super-admin') && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        if (request()->boolean('auto_print') && !request()->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Thermal Receipt ' . $sale->order_no,
                'pdfUrl' => route('admin.sales.thermal', [$sale, 'stream' => 1]),
            ]);
        }

        $sale->load(['customer', 'location', 'customerAddress', 'items.product.subCategory', 'items.product.category']);

        $height = $this->measureThermalHeight($sale);

        $pdf = Pdf::loadView('sales.thermal', ['order' => $sale, 'pdfHeight' => $height])
            ->setPaper([0, 0, 216, $height], 'portrait');

        return $pdf->stream('thermal-receipt-' . $sale->order_no . '.pdf');
    }

    public function taxInvoice(Order $sale)
    {
        $this->authorize('view sales');

        if (auth()->user()->location_id && !auth()->user()->hasRole('super-admin') && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        if (($sale->source ?? 'POS') !== 'POS' || ! $sale->is_gst) {
            abort(404);
        }

        if (request()->boolean('auto_print') && !request()->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Tax Invoice ' . $sale->order_no,
                'pdfUrl' => route('admin.sales.tax-invoice', [$sale, 'stream' => 1]),
            ]);
        }

        $sale->load(['customer', 'location', 'user', 'customerAddress', 'items.product']);

        $pdf = Pdf::loadView('sales.tax-invoice', ['order' => $sale])
            ->setPaper('a4', 'portrait');

        ActivityLogger::log('Sales', 'export', $sale, null, null, 'Tax Invoice PDF exported for sale #' . $sale->order_no);

        return $pdf->stream('tax-invoice-' . $sale->order_no . '.pdf');
    }

    private function measureThermalHeight(Order $sale): int
    {
        $itemCount = $sale->items->count();
        $low = 150;
        $high = 400 + ($itemCount * 40);

        while ($this->thermalPageCount($sale, $high) > 1) {
            $high += 200;
        }

        while ($high - $low > 1) {
            $mid = intdiv($low + $high, 2);
            if ($this->thermalPageCount($sale, $mid) > 1) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return $high + 4;
    }

    private function thermalPageCount(Order $sale, int $height): int
    {
        $pdf = Pdf::loadView('sales.thermal', ['order' => $sale, 'pdfHeight' => $height])
            ->setPaper([0, 0, 216, $height], 'portrait');
        $pdf->render();

        return $pdf->getDomPDF()->getCanvas()->get_page_count();
    }

    private function measureViewHeight(string $view, array $data, int $itemCount): int
    {
        $low = 150;
        $high = 400 + ($itemCount * 40);

        $pageCount = function (int $height) use ($view, $data) {
            $pdf = Pdf::loadView($view, array_merge($data, ['pdfHeight' => $height]))
                ->setPaper([0, 0, 216, $height], 'portrait');
            $pdf->render();

            return $pdf->getDomPDF()->getCanvas()->get_page_count();
        };

        while ($pageCount($high) > 1) {
            $high += 200;
        }

        while ($high - $low > 1) {
            $mid = intdiv($low + $high, 2);
            if ($pageCount($mid) > 1) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return $high + 4;
    }

    public function label(Order $sale)
    {
        $this->authorize('view sales');

        if (auth()->user()->location_id && !auth()->user()->hasRole('super-admin') && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        if (($sale->source ?? 'POS') !== 'ONLINE') {
            abort(403, 'Shipping label print is only available for online orders.');
        }

        if (request()->boolean('auto_print') && !request()->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Shipping Label ' . $sale->order_no,
                'pdfUrl' => route('admin.sales.label', [$sale, 'auto_print' => 1, 'stream' => 1]),
            ]);
        }

        $sale->load(['customer', 'location', 'user', 'coupon', 'customerAddress', 'items.product.variants.attributeValue.attribute', 'payment']);

        $height = $this->measureViewHeight('sales.label', ['order' => $sale], $sale->items->count());

        $pdf = Pdf::loadView('sales.label', ['order' => $sale, 'pdfHeight' => $height])
            ->setPaper([0, 0, 216, $height], 'portrait');

        ActivityLogger::log('Sales', 'export', $sale, null, null, 'Shipping label printed for sale #' . $sale->order_no);

        return $pdf->stream('shipping-label-' . $sale->order_no . '.pdf');
    }

    private function autoPrintPdf($pdf, string $filename)
    {
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        if (request()->boolean('auto_print')) {
            $dompdf->getCanvas()->javascript('this.print({bUI: true, bSilent: false, bShrinkToFit: true});');
        }

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function edit(Order $sale)
    {
        $this->authorize('edit sales');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        if ($isRestricted && $sale->location_id !== $user->location_id) {
            abort(403);
        }

        if (($sale->source ?? 'POS') === 'ONLINE') {
            return redirect()->route('admin.sales.show', $sale)
                ->with('error', 'Online orders cannot be edited from sales.');
        }

        if (!in_array((int) $sale->status, [Order::STATUS_PENDING, Order::STATUS_APPROVE], true)) {
            return redirect()->route('admin.sales.show', $sale)
                ->with('error', 'Only pending or approved sales can be edited.');
        }

        if ($sale->cancellationRequest && $sale->cancellationRequest->status === 'pending') {
            return redirect()->route('admin.sales.show', $sale)
                ->with('error', 'This sale has a pending cancellation request and cannot be edited.');
        }

        $customers   = Customer::where('status', 1)->orderBy('name')->get();
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }
        $products    = Product::with(['variants.attributeValue.attribute', 'primaryImage', 'inventories'])->where('status', 1)->orderBy('name')->get();
        $sale->load(['items.product.variants.attributeValue.attribute']);
        $defaultLocationId = $isRestricted ? $user->location_id : null;

        $variantStockMap = Product::getVariantStockBatch($products->where('type', 'variable'));
        $allProducts = $products->map(function ($p) use ($variantStockMap) {
            $data = [
                'id'              => $p->id,
                'name'            => $p->name,
                'price'           => $p->sale_price,
                'barcode'         => $p->barcode,
                'label'           => $p->barcode ? ($p->name . ' (' . $p->barcode . ')') : $p->name,
                'type'            => $p->type,
                'image'           => $p->primaryImage ? $p->primaryImage->image_url : null,
                'pair_product'    => (bool) $p->pair_product,
                'pair_mode'       => $p->pair_mode,
                'custom_sizes'    => $p->custom_sizes ?? [],
                'single_price'    => $p->sale_price,
                'purchase_price'  => $p->purchase_price,
                'bypass_min_price' => (bool) $p->bypass_min_price,
            ];
            if ($p->type === 'variable') {
                $data['variants'] = $p->variants->filter(function($v) {
                    return $v->status == 1;
                })->values()->map(function($v) {
                    return [
                        'id' => $v->id,
                        'attribute_value_id' => $v->attribute_value_id,
                        'purchase_price' => $v->purchase_price,
                        'sale_price' => $v->sale_price,
                        'custom_sizes' => $v->custom_sizes ?? [],
                        'attr_name' => $v->attributeValue->attribute->name ?? '',
                        'value_name' => $v->attributeValue->value ?? '',
                    ];
                })->all();
            }

            // Calculate stock by location
            $stockByLocation = [];
            if ($p->type === 'variable') {
                $variantStock = $variantStockMap[$p->id] ?? [];
                foreach ($variantStock as $locId => $locData) {
                    $stockByLocation[$locId] = [
                        'parent' => $locData['parent'],
                        'variants' => $locData['variants']
                    ];
                }
            } else {
                foreach ($p->inventories as $inv) {
                    $stockByLocation[$inv->location_id] = $inv->quantity;
                }
            }
            $data['stock_by_location'] = $stockByLocation;

            return $data;
        })->values();

        $existingItems = $sale->items->map(function ($item) {
            return [
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'pair_type'          => $item->pair_type ?? 'single',
                'custom_size_value'  => $item->custom_size_value,
                'price'              => $item->price,
                'quantity'           => $item->quantity,
                'discount_type'      => $item->discount_type ?? 'flat',
                'discount_value'     => $item->discount_value ?? 0,
            ];
        })->values();

        return view('sales.edit', ['order' => $sale, 'customers' => $customers, 'locations' => $locations, 'products' => $products, 'allProducts' => $allProducts, 'existingItems' => $existingItems, 'isRestricted' => $isRestricted, 'defaultLocationId' => $defaultLocationId]);
    }

    public function update(Request $request, Order $sale)
    {
        $this->authorize('edit sales');

        $authUser = auth()->user();
        $isRestricted = $authUser->location_id && !$authUser->hasRole('super-admin');
        if ($isRestricted && (int)$request->location_id !== (int)$authUser->location_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You can only update sales for your assigned location.',
            ], 403);
        }

        if (($sale->source ?? 'POS') === 'ONLINE') {
            return response()->json(['status' => 'error', 'message' => 'Online orders cannot be edited from sales.'], 422);
        }

        if (!in_array((int) $sale->status, [Order::STATUS_PENDING, Order::STATUS_APPROVE], true)) {
            return response()->json(['status' => 'error', 'message' => 'Only pending or approved sales can be edited.'], 422);
        }

        if ($sale->cancellationRequest && $sale->cancellationRequest->status === 'pending') {
            return response()->json(['status' => 'error', 'message' => 'This sale has a pending cancellation request and cannot be edited.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'location_id'                => ['required', 'exists:locations,id'],
            'customer_id'                => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value !== '0' && !\DB::table('customers')->where('id', $value)->exists()) {
                        $fail('The selected customer is invalid.');
                    }
                }
            ],
            'payment_method'             => ['required', 'string'],
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_id'         => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.pair_type'          => ['nullable', 'string', 'in:single,pair'],
            'items.*.custom_size_value'  => ['nullable', 'numeric', 'min:0.01'],
            'items.*.quantity'           => ['required', 'integer', 'min:1'],
            'items.*.price'              => ['required', 'numeric', 'min:0.01'],
            'items.*.discount_type'      => ['nullable', 'string', 'in:flat,percentage'],
            'items.*.discount_value'     => ['nullable', 'numeric', 'min:0'],
            'discount_type'              => ['nullable', 'string', 'in:flat,percentage,MANUAL,COUPON'],
            'discount_value'             => ['nullable', 'numeric', 'min:0'],
            'order_discount_type'        => ['nullable', 'string', 'in:flat,percentage'],
            'order_discount_value'       => ['nullable', 'numeric', 'min:0'],
            'status'                     => ['nullable', 'integer', 'in:1,2,6'],
            'payment_status'             => ['nullable', 'integer', 'in:1,2'],
            'source'                     => ['nullable', 'string', 'in:POS,ONLINE'],
            'coupon_id'                  => ['nullable', 'exists:coupons,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        if ($request->customer_id === '0' || $request->customer_id === '') {
            $request->merge(['customer_id' => null]);
        }

        $isApprove = ($request->status ?? 2) == 2;
        $isCancelled = ($request->status ?? 2) == 6;

        $customSizeError = $this->getCustomSizeError($request->items);
        if ($customSizeError) {
            return response()->json([
                'status'  => 'error',
                'message' => ['items' => [$customSizeError]],
            ], 422);
        }

        $minPriceError = $this->getMinPriceError(
            $request->items,
            $request->order_discount_type ?? 'flat',
            (float) ($request->order_discount_value ?? 0)
        );
        if ($minPriceError) {
            return response()->json([
                'status'  => 'error',
                'message' => ['items' => [$minPriceError]],
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $isApprove, $isCancelled, $sale) {
            $wasApproved = ((int) $sale->status === Order::STATUS_APPROVE);
            $oldLocationId = (int) $sale->location_id;

            $oldItemsSnapshot = $sale->items->map(function ($item) {
                return [
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'pair_type'          => $item->pair_type ?? 'single',
                    'quantity'           => $item->quantity,
                    'price'              => (float) $item->price,
                ];
            })->values()->all();

            // Sale was already approved (stock deducted) — restore it before applying the edited items.
            if ($wasApproved) {
                foreach ($oldItemsSnapshot as $old) {
                    $stockRestore = ($old['pair_type'] === 'pair') ? $old['quantity'] * 2 : $old['quantity'];
                    $this->logInventoryChange((int) $old['product_id'], $oldLocationId, $stockRestore, 'Stock restored for edited sale #' . $sale->order_no);
                }
            }

            if ($isApprove) {
                $stockError = $this->getStockError($request->items, (int) $request->location_id);
                if ($stockError) {
                    throw new \RuntimeException($stockError);
                }
            }

            $sale->items()->delete();

            $totalAmount = 0.0;
            $itemsData = [];

            foreach ($request->items as $itemData) {
                $qty = (int)$itemData['quantity'];
                $price = (float)$itemData['price'];
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
                $totalAmount += $itemTotal;

                $itemsData[] = [
                    'product_id'         => $itemData['product_id'],
                    'product_variant_id' => $itemData['product_variant_id'] ?? null,
                    'pair_type'          => $itemData['pair_type'] ?? 'single',
                    'custom_size_value'  => (isset($itemData['custom_size_value']) && $itemData['custom_size_value'] !== '') ? (float) $itemData['custom_size_value'] : null,
                    'quantity'           => $qty,
                    'price'              => $price,
                    'discount_type'      => $discType,
                    'discount_value'     => $discVal,
                    'discount_amount'    => $discAmount,
                    'total'              => $itemTotal,
                ];
            }

            $discVal = (float)($request->order_discount_value ?? 0);
            $discType = $discVal > 0 ? ($request->order_discount_type ?? 'flat') : null;

            $orderDiscountAmount = 0.0;
            if ($discVal > 0) {
                if ($discType === 'flat') {
                    $orderDiscountAmount = $discVal;
                } else if ($discType === 'percentage') {
                    $orderDiscountAmount = $totalAmount * ($discVal / 100);
                }
            }

            if ($orderDiscountAmount > $totalAmount) {
                $orderDiscountAmount = $totalAmount;
            }

            $finalAmount = $totalAmount - $orderDiscountAmount;

            $source = $request->input('source', $sale->source ?? 'POS');
            $isGst = $request->boolean('is_gst');
            if ($source === 'ONLINE') {
                $isGst = false;
            }
            $taxAmount = 0.0;
            $orderPrefix = 'SA';

            if ($isGst) {
                $orderPrefix = 'GS';
                $gstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);
                $taxAmount = $finalAmount * ($gstRate / 100);
            }

            $grandTotal = round($finalAmount + $taxAmount);

            $updateData = [
                'customer_id'          => $request->customer_id,
                'location_id'          => $request->location_id,
                'payment_method'       => $request->payment_method,
                'status'               => $request->status ?? 2,
                'payment_status'       => $isCancelled ? Order::PAYMENT_STATUS_PENDING : ($request->payment_status ?? $sale->payment_status ?? 1),
                'is_gst'               => $isGst,
                'tax_amount'           => $taxAmount,
                'final_amount'         => $grandTotal,
                'source'               => $source,
                'order_discount_type'  => $discType,
                'order_discount_value' => $discVal,
                'coupon_id'            => $request->has('coupon_id') ? $request->coupon_id : $sale->coupon_id,
            ];

            if ($isCancelled && $request->filled('cancellation_reason')) {
                $updateData['cancellation_reason'] = $request->cancellation_reason;
            }

            if ($sale->is_gst !== $isGst) {
                $updateData['order_no'] = generate_invoice_no($orderPrefix, Order::class, 'order_no');
            }

            $sale->update($updateData);

            foreach ($itemsData as $item) {
                OrderItem::create([
                    'order_id'           => $sale->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'pair_type'          => $item['pair_type'],
                    'custom_size_value'  => $item['custom_size_value'],
                    'quantity'           => $item['quantity'],
                    'price'              => $item['price'],
                    'discount_type'      => $item['discount_type'],
                    'discount_value'     => $item['discount_value'],
                    'discount_amount'    => $item['discount_amount'],
                    'total'              => $item['total'],
                ]);

                if ($isApprove) {
                    $stockDeduct = (int) round($item['quantity'] * $this->stockMultiplierFor((int) $item['product_id'], $item['pair_type'], $item['custom_size_value']));
                    $this->logInventoryChange((int) $item['product_id'], (int) $request->location_id, -$stockDeduct, 'Stock deducted for updated sale #' . $sale->order_no);
                }
            }

            $newItemsSnapshot = collect($itemsData)->map(function ($item) {
                return [
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity'           => $item['quantity'],
                    'price'              => (float) $item['price'],
                ];
            })->values()->all();

            ActivityLogger::log(
                'Sales',
                'update',
                $sale,
                ['items' => $oldItemsSnapshot],
                ['items' => $newItemsSnapshot],
                'Order #' . $sale->order_no . ' items modified'
            );
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => ['items' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json(['status' => 'success', 'message' => 'Sale updated successfully.', 'id' => $sale->id]);
    }

    public function updateStatus(Request $request, Order $sale)
    {
        if ($request->filled('status')) {
            $this->authorize('edit sales status');
        }
        if ($request->filled('payment_status')) {
            $this->authorize('edit sales payment status');
        }
        if (!$request->filled('status') && !$request->filled('payment_status')) {
            $this->authorize('edit sales');
        }

        $validator = Validator::make($request->all(), [
            'status'               => ['nullable', 'integer', 'in:1,2,3,4,5,6'],
            'payment_status'       => ['nullable', 'integer', 'in:1,2'],
            'cancellation_reason'  => ['nullable', 'string', 'max:500'],
            'shipped_client_url'   => ['required_if:status,3', 'nullable', 'string', 'max:255'],
            'tracking_id'          => ['required_if:status,3', 'nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request, $sale) {
                if ($request->filled('status')) {
                    $newStatus = (int)$request->status;
                    $oldStatus = (int)$sale->status;

                    if ($newStatus != $oldStatus) {
                        // 1. Terminal status validation
                        if ($oldStatus == Order::STATUS_DELIVERED) {
                            throw new \Exception('Delivered orders cannot be modified.');
                        }
                        if ($oldStatus == Order::STATUS_DECLINE) {
                            throw new \Exception('Cancelled orders cannot be modified.');
                        }

                        // 2. Backward progression validation (Decline is always allowed from any non-terminal status)
                        if ($newStatus !== Order::STATUS_DECLINE) {
                            if ($oldStatus == Order::STATUS_APPROVE && $newStatus == Order::STATUS_PENDING) {
                                throw new \Exception('Cannot change status back to Pending once approved.');
                            }
                            if ($oldStatus == Order::STATUS_SHIPPED && in_array($newStatus, [Order::STATUS_PENDING, Order::STATUS_APPROVE])) {
                                throw new \Exception('Cannot change status back once shipped.');
                            }
                            if ($oldStatus == Order::STATUS_OUT_FOR_DELIVERY && in_array($newStatus, [Order::STATUS_PENDING, Order::STATUS_APPROVE, Order::STATUS_SHIPPED])) {
                                throw new \Exception('Cannot change status back once out for delivery.');
                            }
                        }

                        // 3. Step-by-step transition validation
                        if ($newStatus !== Order::STATUS_DECLINE) {
                            // POS Order checks
                            if (($sale->source ?? 'POS') !== 'ONLINE') {
                                if (!in_array($newStatus, [Order::STATUS_PENDING, Order::STATUS_APPROVE])) {
                                    throw new \Exception('POS orders can only be Pending or Approved.');
                                }
                                if ($oldStatus == Order::STATUS_PENDING && $newStatus != Order::STATUS_APPROVE) {
                                    throw new \Exception('POS orders can only be updated from Pending to Approved.');
                                }
                            } else {
                                // ONLINE Order sequential check (allowing only one-step forward progression)
                                if ($oldStatus == Order::STATUS_PENDING && $newStatus != Order::STATUS_APPROVE) {
                                    throw new \Exception('Pending orders can only be updated to Approved.');
                                }
                                if ($oldStatus == Order::STATUS_APPROVE && $newStatus != Order::STATUS_SHIPPED) {
                                    throw new \Exception('Approved orders can only be updated to Shipped.');
                                }
                                if ($oldStatus == Order::STATUS_SHIPPED && $newStatus != Order::STATUS_OUT_FOR_DELIVERY) {
                                    throw new \Exception('Shipped orders can only be updated to Out for delivery.');
                                }
                                if ($oldStatus == Order::STATUS_OUT_FOR_DELIVERY && $newStatus != Order::STATUS_DELIVERED) {
                                    throw new \Exception('Out for delivery orders can only be updated to Delivered.');
                                }
                            }
                        }

                        // Require cancellation reason when cancelling
                        if ($newStatus == Order::STATUS_DECLINE && empty($request->cancellation_reason)) {
                            throw new \Exception('Please provide a cancellation reason.');
                        }

                        $deductedGroup = [2, 3, 4, 5];
                        $restoredGroup = [1, 6];

                        $oldInDeducted = in_array($oldStatus, $deductedGroup);
                        $newInDeducted = in_array($newStatus, $deductedGroup);

                        // Transition from Restored to Deducted group: deduct stock
                        if (!$oldInDeducted && $newInDeducted) {
                            $stockError = $this->getStockError($sale->items, (int) $sale->location_id);
                            if ($stockError) {
                                throw new \Exception($stockError);
                            }

                            // Deduct stock
                            foreach ($sale->items as $item) {
                                $stockDeduct = (int) round($item->quantity * $this->stockMultiplierFor((int) $item->product_id, $item->pair_type, $item->custom_size_value));
                                $this->logInventoryChange((int) $item->product_id, (int) $sale->location_id, -$stockDeduct, 'Stock deducted for sale #' . $sale->order_no . ' status change');
                            }
                        }
                        // Transition from Deducted to Restored group: restore stock
                        elseif ($oldInDeducted && !$newInDeducted) {
                            // Restore stock
                            foreach ($sale->items as $item) {
                                $stockRestore = (int) round($item->quantity * $this->stockMultiplierFor((int) $item->product_id, $item->pair_type, $item->custom_size_value));
                                $this->logInventoryChange((int) $item->product_id, (int) $sale->location_id, $stockRestore, 'Stock restored for sale #' . $sale->order_no . ' status change');
                            }
                        }

                        $updateData = ['status' => $newStatus];
                        if ($newStatus == Order::STATUS_DECLINE) {
                            $updateData['cancellation_reason'] = $request->cancellation_reason;

                            // Process Refund for Direct Admin Cancellation
                            $refundAmount = (float)$sale->final_amount;
                            $shippingDeducted = 0.0;
                            $shippedOrLater = in_array((int)$sale->status, [Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED], true);

                            if ($shippedOrLater) {
                                $address = CustomerAddress::find($sale->customer_address_id);
                                if ($address && $address->state) {
                                    $state = State::where('name', $address->state)->first();
                                    if ($state) {
                                        $shippingDeducted = (float)$state->shipping_charge;
                                    }
                                }
                                $refundAmount = max(0.0, $refundAmount - $shippingDeducted);
                            }

                            $refundGatewayId = null;

                            // Trigger Razorpay refund if paid online and payment is captured
                            $payment = $sale->payment;
                            if ($payment && $payment->gateway === 'razorpay' && !empty($payment->gateway_payment_id) && $payment->status === OrderPayment::STATUS_CAPTURED) {
                                $paymentMode = Setting::getValue('razorpay_payment_mode', 'test');
                                $razorpayKeyId = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_id' : 'razorpay_test_key_id', '');
                                $razorpayKeySecret = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_secret' : 'razorpay_test_key_secret', '');

                                if (empty($razorpayKeyId) || empty($razorpayKeySecret)) {
                                    throw new \Exception('Razorpay credentials are not configured.');
                                }

                                $refundAmountInPaise = round($refundAmount * 100);

                                if ($refundAmountInPaise > 0) {
                                    $response = Http::withBasicAuth($razorpayKeyId, $razorpayKeySecret)
                                        ->post("https://api.razorpay.com/v1/payments/{$payment->gateway_payment_id}/refund", [
                                            'amount' => $refundAmountInPaise,
                                            'speed'  => 'normal',
                                        ]);

                                    if ($response->failed()) {
                                        Log::error('Razorpay Refund API Failed from Admin updateStatus: ' . $response->body());
                                        throw new \Exception('Razorpay Refund failed: ' . ($response->json('error.description') ?? 'Unknown error'));
                                    }

                                    $refundData = $response->json();
                                    $refundGatewayId = $refundData['id'] ?? null;
                                }

                                // Update payment record to refunded
                                $payment->update([
                                    'status' => OrderPayment::STATUS_REFUNDED
                                ]);

                                // Create a new refunded transaction entry
                                OrderPayment::create([
                                    'order_id'           => $sale->id,
                                    'gateway'            => 'razorpay',
                                    'gateway_order_id'   => $payment->gateway_order_id,
                                    'gateway_payment_id' => $payment->gateway_payment_id,
                                    'status'             => OrderPayment::STATUS_REFUNDED,
                                    'amount'             => -$refundAmount,
                                    'currency'           => $payment->currency ?? 'INR',
                                ]);
                            }

                            // Create or update cancellation request record
                            $cancellationRequest = OrderCancellationRequest::where('order_id', $sale->id)->first();
                            if ($cancellationRequest) {
                                $cancellationRequest->update([
                                    'status'              => OrderCancellationRequest::STATUS_APPROVED,
                                    'cancellation_reason' => $request->cancellation_reason ?? $cancellationRequest->cancellation_reason,
                                    'refund_amount'       => $refundAmount,
                                    'refund_gateway_id'   => $refundGatewayId,
                                ]);
                            } else {
                                OrderCancellationRequest::create([
                                    'order_id'            => $sale->id,
                                    'customer_id'         => $sale->customer_id,
                                    'cancellation_reason' => $request->cancellation_reason ?? 'Cancelled by Admin',
                                    'status'              => OrderCancellationRequest::STATUS_APPROVED,
                                    'refund_amount'       => $refundAmount,
                                    'refund_gateway_id'   => $refundGatewayId,
                                ]);
                            }
                        }

                        // Record dates for status change
                        if ($newStatus == Order::STATUS_APPROVE) {
                            $updateData['confirmed_at'] = now();
                        } elseif ($newStatus == Order::STATUS_SHIPPED) {
                            $updateData['shipped_at'] = now();
                            $updateData['shipped_client_url'] = $request->shipped_client_url;
                            $updateData['tracking_id'] = $request->tracking_id;
                        } elseif ($newStatus == Order::STATUS_OUT_FOR_DELIVERY) {
                            $updateData['out_for_delivery_at'] = now();
                        } elseif ($newStatus == Order::STATUS_DELIVERED) {
                            $updateData['delivered_at'] = now();
                        }

                        $sale->update($updateData);

                        if ($sale->customer && $sale->customer->email) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($sale->customer->email)->send(new \App\Mail\OrderStatusMail($sale));
                            } catch (\Exception $mailEx) {
                                \Illuminate\Support\Facades\Log::error('Failed to send status mail: ' . $mailEx->getMessage());
                            }
                        }
                    }
                }

                if ($request->filled('payment_status')) {
                    $sale->update(['payment_status' => $request->payment_status]);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $pendingCount = \App\Models\Order::where('status', 1)->count();
        return response()->json([
            'status' => 'success',
            'message' => 'Sale status updated successfully.',
            'pending_count' => $pendingCount
        ]);
    }

    private function logInventoryChange(int $productId, int $locationId, int $delta, string $description): void
    {
        $inventory = Inventory::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();

        if (!$inventory) {
            return;
        }

        $oldQty = $inventory->quantity;
        $inventory->increment('quantity', $delta);
        $newQty = $oldQty + $delta;

        ActivityLogger::log('Inventory', 'update', $inventory, ['quantity' => $oldQty], ['quantity' => $newQty], $description);
    }

    /**
     * How many stock units one sold "quantity" unit represents: a chosen
     * custom size (e.g. 6 pcs) for custom_size-mode pair products, 2 for
     * plain pair products sold as a pair, or 1 otherwise.
     */
    private function stockMultiplierFor(int $productId, ?string $pairType, ?float $customSizeValue): float
    {
        if ($customSizeValue !== null && $customSizeValue !== '' && (float)$customSizeValue > 0) {
            return (float) $customSizeValue;
        }

        $product = Product::find($productId);

        if (!$product || !$product->pair_product) {
            return 1.0;
        }

        return $pairType === 'pair' ? 2.0 : 1.0;
    }

    /**
     * For custom_size-mode pair products, make sure the sale line picked a
     * size that matches one of the product's configured custom sizes.
     */
    private function getCustomSizeError(iterable $items): ?string
    {
        foreach ($items as $itemData) {
            $productId = is_array($itemData) ? $itemData['product_id'] : $itemData->product_id;
            $customSizeValue = is_array($itemData) ? ($itemData['custom_size_value'] ?? null) : ($itemData->custom_size_value ?? null);

            $product = Product::find($productId);
            if (!$product || !$product->pair_product) {
                continue;
            }

            $value = ($customSizeValue !== null && $customSizeValue !== '') ? (float) $customSizeValue : null;
            $validSizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn ($s) => (float) $s);

            if (!$value || !$validSizes->contains(fn ($s) => abs($s - $value) < 0.001)) {
                return 'Please select a valid size for "' . $product->name . '".';
            }
        }

        return null;
    }

    private function getStockError(iterable $items, int $locationId): ?string
    {
        $requested = [];

        foreach ($items as $item) {
            $productId = (int) (is_array($item) ? $item['product_id'] : $item->product_id);
            $variantId = is_array($item)
                ? ($item['product_variant_id'] ?? null)
                : $item->product_variant_id;
            $variantId = $variantId ? (int) $variantId : null;
            $quantity  = (int) (is_array($item) ? $item['quantity'] : $item->quantity);
            $pairType  = is_array($item) ? ($item['pair_type'] ?? 'single') : ($item->pair_type ?? 'single');
            $customSizeValue = is_array($item) ? ($item['custom_size_value'] ?? null) : ($item->custom_size_value ?? null);

            $stockQty = (int) round($quantity * $this->stockMultiplierFor($productId, $pairType, $customSizeValue ? (float) $customSizeValue : null));

            $key = $productId . ':' . ($variantId ?? 0);

            if (!isset($requested[$key])) {
                $requested[$key] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity'   => 0,
                ];
            }

            $requested[$key]['quantity'] += $stockQty;
        }

        foreach ($requested as $stockRequest) {
            $product = Product::with('variants.attributeValue.attribute')
                ->find($stockRequest['product_id']);

            if (!$product) {
                return 'Selected product was not found.';
            }

            $variantId = $stockRequest['variant_id'];
            $label = $product->name;

            if ($variantId) {
                $variant = $product->variants->firstWhere('id', $variantId);
                if (!$variant) {
                    return 'Selected variant does not belong to product "' . $product->name . '".';
                }

                $stockData = $product->getVariantStock($locationId);
                $available = (int) ($stockData['variants'][$variantId] ?? 0);
                $attributeName = $variant->attributeValue->attribute->name ?? 'Variant';
                $attributeValue = $variant->attributeValue->value ?? $variantId;
                $label .= ' (' . $attributeName . ': ' . $attributeValue . ')';
            } else {
                $available = (int) (Inventory::where('product_id', $product->id)
                    ->where('location_id', $locationId)
                    ->value('quantity') ?? 0);
            }

            if ($available < $stockRequest['quantity']) {
                return 'Product "' . $label . '" only has ' . $available
                    . ' units in stock; ' . $stockRequest['quantity'] . ' requested.';
            }
        }

        return null;
    }

    private function getMinPriceError(iterable $items, string $orderDiscountType = 'flat', float $orderDiscountValue = 0): ?string
    {
        $itemsTotal    = 0.0;
        $minFloorTotal = 0.0;
        $hasItems      = false;

        foreach ($items as $itemData) {
            $productId = is_array($itemData) ? $itemData['product_id'] : $itemData->product_id;
            $variantId = is_array($itemData) ? ($itemData['product_variant_id'] ?? null) : $itemData->product_variant_id;
            $qty       = (int) (is_array($itemData) ? $itemData['quantity'] : $itemData->quantity);
            $price     = (float) (is_array($itemData) ? $itemData['price'] : $itemData->price);
            $discVal   = (float) (is_array($itemData) ? ($itemData['discount_value'] ?? 0) : ($itemData->discount_value ?? 0));
            $discType  = is_array($itemData) ? ($itemData['discount_type'] ?? 'flat') : ($itemData->discount_type ?? 'flat');

            if ($qty <= 0) {
                continue;
            }
            $hasItems = true;

            $subtotal = $qty * $price;
            $discAmount = $discType === 'percentage' ? $subtotal * ($discVal / 100) : $discVal;
            if ($discAmount > $subtotal) {
                $discAmount = $subtotal;
            }
            $itemTotal = $subtotal - $discAmount;
            $itemsTotal += $itemTotal;

            if ($variantId) {
                $variant = ProductVariant::with('attributeValue.attribute')->find($variantId);
                $purchasePrice = $variant->purchase_price ?? null;
                $product = $variant->product ?? Product::find($productId);
                $label = ($product->name ?? 'Product');
                if ($variant && $variant->attributeValue) {
                    $label .= ' (' . ($variant->attributeValue->attribute->name ?? 'Variant') . ': ' . $variant->attributeValue->value . ')';
                }
            } else {
                $product = Product::find($productId);
                $purchasePrice = $product->purchase_price ?? null;
                $label = $product->name ?? 'Product';
            }

            $bypass = (bool) ($product->bypass_min_price ?? false);

            if ($bypass) {
                if ($itemTotal < 0) {
                    return 'Item amount cannot be negative.';
                }
                continue;
            }

            if ($purchasePrice === null) {
                continue;
            }

            if (!empty($product->pair_product)) {
                $customSizes = !empty($variant->custom_sizes) ? $variant->custom_sizes : ($product->custom_sizes ?? []);
                if (!empty($customSizes) && (is_array($customSizes) || $customSizes instanceof \Illuminate\Support\Collection)) {
                    $sizes = collect($customSizes)->map(function ($s) {
                        return (float) (is_array($s) ? ($s['size'] ?? 0) : (is_object($s) ? ($s->size ?? 0) : $s));
                    })->filter(fn($s) => $s > 0);
                    $maxSize = $sizes->count() > 0 ? $sizes->max() : 2.0;
                    $customSizeValRaw = is_array($itemData) ? ($itemData['custom_size_value'] ?? null) : ($itemData->custom_size_value ?? null);
                    $customSizeVal = !empty($customSizeValRaw) ? (float)$customSizeValRaw : $maxSize;
                    if ($maxSize > 0) {
                        $purchasePrice = $purchasePrice * ($customSizeVal / $maxSize);
                    }
                } else {
                    $isPairRaw = is_array($itemData) ? ($itemData['pair_type'] ?? null) : ($itemData->pair_type ?? null);
                    $isPair = isset($isPairRaw) && $isPairRaw === 'pair';
                    if (!$isPair) {
                        $purchasePrice = $purchasePrice / 2.0;
                    }
                }
            }

            $minTotal = $qty * $purchasePrice * 1.10;
            $minFloorTotal += $minTotal;

            if ($discVal > 0 && $itemTotal < $minTotal - 0.01) {
                return 'Discount cannot be applied to this order.';
            }
        }

        $orderDiscountAmount = 0.0;
        if ($orderDiscountValue > 0) {
            $orderDiscountAmount = $orderDiscountType === 'percentage'
                ? $itemsTotal * ($orderDiscountValue / 100)
                : $orderDiscountValue;
        }
        if ($orderDiscountAmount > $itemsTotal) {
            $orderDiscountAmount = $itemsTotal;
        }

        $finalAmount = $itemsTotal - $orderDiscountAmount;

        if ($hasItems && $finalAmount < 0) {
            return 'Order total cannot be negative.';
        }

        $hasAnyDiscount = ($orderDiscountValue > 0);
        foreach ($items as $item) {
            if (!empty($item['discount_value']) && (float)$item['discount_value'] > 0) {
                $hasAnyDiscount = true;
                break;
            }
        }

        if ($hasAnyDiscount && $minFloorTotal > 0 && $finalAmount < $minFloorTotal - 0.01) {
            return 'Discount cannot be applied to this order.';
        }

        return null;
    }

    public function approveCancellation(Order $sale)
    {
        $this->authorize('edit sales status');

        $cancellationRequest = OrderCancellationRequest::where('order_id', $sale->id)
            ->where('status', OrderCancellationRequest::STATUS_PENDING)
            ->first();

        if (!$cancellationRequest) {
            return response()->json(['status' => 'error', 'message' => 'No pending cancellation request found.'], 404);
        }

        try {
            DB::beginTransaction();

            $refundAmount = (float)$sale->final_amount;
            $shippingDeducted = 0.0;
            $shippedOrLater = in_array((int)$sale->status, [Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED], true);

            if ($shippedOrLater) {
                // Find state shipping charge
                $address = CustomerAddress::find($sale->customer_address_id);
                if ($address && $address->state) {
                    $state = State::where('name', $address->state)->first();
                    if ($state) {
                        $shippingDeducted = (float)$state->shipping_charge;
                    }
                }
                $refundAmount = max(0.0, $refundAmount - $shippingDeducted);
            }

            $refundGatewayId = null;

            // Trigger Razorpay refund if paid online and Razorpay order exists
            $payment = $sale->payment;
            if ($payment && $payment->gateway === 'razorpay' && !empty($payment->gateway_payment_id) && $payment->status === OrderPayment::STATUS_CAPTURED) {
                $paymentMode = Setting::getValue('razorpay_payment_mode', 'test');
                $razorpayKeyId = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_id' : 'razorpay_test_key_id', '');
                $razorpayKeySecret = Setting::getValue($paymentMode === 'live' ? 'razorpay_live_key_secret' : 'razorpay_test_key_secret', '');

                if (empty($razorpayKeyId) || empty($razorpayKeySecret)) {
                    throw new \Exception('Razorpay credentials are not configured.');
                }

                // Razorpay expects amount in paise
                $refundAmountInPaise = round($refundAmount * 100);

                if ($refundAmountInPaise > 0) {
                    $response = Http::withBasicAuth($razorpayKeyId, $razorpayKeySecret)
                        ->post("https://api.razorpay.com/v1/payments/{$payment->gateway_payment_id}/refund", [
                            'amount' => $refundAmountInPaise,
                            'speed'  => 'normal',
                        ]);

                    if ($response->failed()) {
                        Log::error('Razorpay Refund API Failed: ' . $response->body());
                        throw new \Exception('Razorpay Refund failed: ' . ($response->json('error.description') ?? 'Unknown error'));
                    }

                    $refundData = $response->json();
                    $refundGatewayId = $refundData['id'] ?? null;
                }

                // Update payment record to refunded
                $payment->update([
                    'status' => OrderPayment::STATUS_REFUNDED
                ]);

                // Create a new refunded transaction entry
                OrderPayment::create([
                    'order_id'           => $sale->id,
                    'gateway'            => 'razorpay',
                    'gateway_order_id'   => $payment->gateway_order_id,
                    'gateway_payment_id' => $payment->gateway_payment_id,
                    'status'             => OrderPayment::STATUS_REFUNDED,
                    'amount'             => -$refundAmount,
                    'currency'           => $payment->currency ?? 'INR',
                ]);
            }

            // Restore Stock/Inventory
            foreach ($sale->items as $item) {
                $stockRestore = (int) round($item->quantity * $this->stockMultiplierFor((int) $item->product_id, $item->pair_type, $item->custom_size_value));
                Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $sale->location_id)
                    ->increment('quantity', $stockRestore);
            }

            // Update cancellation request status
            $cancellationRequest->update([
                'status'            => OrderCancellationRequest::STATUS_APPROVED,
                'refund_amount'     => $refundAmount,
                'refund_gateway_id' => $refundGatewayId,
            ]);

            // Update order status
            $sale->update([
                'status'              => Order::STATUS_DECLINE,
                'payment_status'      => Order::PAYMENT_STATUS_PENDING,
                'cancellation_reason' => $cancellationRequest->cancellation_reason,
            ]);

            DB::commit();

            // Send status mail
            if ($sale->customer && $sale->customer->email) {
                try {
                    Mail::to($sale->customer->email)->send(new OrderStatusMail($sale->fresh(['customer'])));
                } catch (\Exception $mailEx) {
                    Log::error('Failed to send cancellation approval email: ' . $mailEx->getMessage());
                }
            }

            // Log activity
            ActivityLogger::log('Sales', 'update', $sale, null, null, 'Cancellation request approved & order cancelled. Refund processed: ₹' . $refundAmount);

            return response()->json([
                'status'  => 'success',
                'message' => 'Cancellation request approved. Refund processed: ₹' . number_format($refundAmount, 2),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cancellation Approval Failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to approve cancellation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rejectCancellation(Order $sale)
    {
        $this->authorize('edit sales status');

        $cancellationRequest = OrderCancellationRequest::where('order_id', $sale->id)
            ->where('status', OrderCancellationRequest::STATUS_PENDING)
            ->first();

        if (!$cancellationRequest) {
            return response()->json(['status' => 'error', 'message' => 'No pending cancellation request found.'], 404);
        }

        try {
            $cancellationRequest->update([
                'status' => OrderCancellationRequest::STATUS_REJECTED,
            ]);

            // Log activity
            ActivityLogger::log('Sales', 'update', $sale, null, null, 'Cancellation request rejected.');

            return response()->json([
                'status'  => 'success',
                'message' => 'Cancellation request rejected successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Cancellation Rejection Failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to reject cancellation: ' . $e->getMessage()
            ], 500);
        }
    }
}
