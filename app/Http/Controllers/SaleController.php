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
        $orders    = Order::with(['customer', 'location', 'user', 'items.product'])
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
        $canDelete                 = auth()->user()->can('delete sales');
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
            6 => 'Decline',
        ];

        $paymentColors = [
            1 => 'bg-label-warning',
            2 => 'bg-label-info',
        ];
        $paymentLabels = [
            1 => 'Pending',
            2 => 'Paid',
        ];

        $data = $orders->map(function ($order, $index) use ($canEdit, $canDelete, $canEditSalesStatus, $canEditSalesPaymentStatus, $canDownloadSales, $statusColors, $statusLabels, $paymentColors, $paymentLabels, $inventoryByProduct) {
            $stockWarningHtml = '';
            if (($order->source ?? 'POS') === 'ONLINE' && $order->location_id) {
                $issueBlocks = [];
                foreach ($order->items as $item) {
                    $invRows = $inventoryByProduct->get($item->product_id, collect());
                    $qtyAtLocation = (int) ($invRows->firstWhere('location_id', $order->location_id)->quantity ?? 0);

                    if ($qtyAtLocation < $item->quantity) {
                        $otherRows = $invRows->where('location_id', '!=', $order->location_id)
                            ->filter(fn ($inv) => $inv->quantity > 0)
                            ->map(function ($inv) {
                                return "<div class='sw-branch'><span>" . e($inv->location->name ?? 'Unknown') . "</span><span class='sw-qty'>" . (int) $inv->quantity . "</span></div>";
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
                    $stockWarningHtml = ' <i class="ti ti-alert-triangle text-warning fs-5 align-middle cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="stock-warning-tooltip" title="' . $tooltipHtml . '"></i>';
                }
            }

            $status        = '<span class="badge ' . ($statusColors[$order->status] ?? 'bg-label-secondary') . '">' . ($statusLabels[$order->status] ?? ucfirst($order->status)) . '</span>';
            if ($order->status == 6 && !empty($order->cancellation_reason)) {
                $status .= ' <i class="fas fa-info-circle text-danger fs-5 ms-1 align-middle cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="' . e($order->cancellation_reason) . '"></i>';
            }
            $paymentStatus = '<span class="badge ' . ($paymentColors[$order->payment_status] ?? 'bg-label-secondary') . '">' . ($paymentLabels[$order->payment_status] ?? ucfirst($order->payment_status)) . '</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.sales.show', $order) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            if ($canDownloadSales) {
                if (($order->source ?? 'POS') === 'ONLINE') {
                    $actions .= '<a href="' . route('admin.sales.pdf', $order) . '" class="dropdown-item" target="_blank"><i class="ti ti-file-text me-2"></i>Invoice</a>';
                } else {
                    $actions .= '<a href="' . route('admin.sales.thermal', $order) . '" class="dropdown-item" onclick="window.open(this.href, \'_blank\', \'width=900,height=800,resizable=yes,scrollbars=yes\'); return false;"><i class="ti ti-file-text me-2"></i>Invoice</a>';
                }
            }
            $isEditable = ($order->source ?? 'POS') === 'POS' && $order->status == 1;
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
            if ($canDelete && $order->status == 6) {
                $actions .= '<div class="dropdown-divider"></div>';
                $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.sales.destroy', $order) . '" data-row-id="sale-row-' . $order->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
            }
            $actions .= '</div></div>';
            $sourceVal = $order->source ?? 'POS';
            $sourceBadge = $sourceVal === 'ONLINE'
                ? '<span class="badge bg-label-success">ONLINE</span>'
                : '<span class="badge bg-label-info">POS</span>';

            return [
                'index'          => $index + 1,
                'stock_warning'  => $stockWarningHtml,
                'order_no'       => '<code>' . $order->order_no . '</code>',
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
        $products    = Product::with(['variants.attributeValue.attribute', 'primaryImage'])->where('status', 1)->orderBy('name')->get();
        $orderNo     = generate_invoice_no('ORD', Order::class, 'order_no');
        $allProducts = $products->map(function ($p) {
            $data = [
                'id'              => $p->id,
                'name'            => $p->name,
                'price'           => $p->sale_price,
                'sku'             => $p->sku,
                'barcode'         => $p->barcode,
                'label'           => $p->name . ' (' . $p->sku . ')',
                'type'            => $p->type,
                'image'           => $p->primaryImage ? $p->primaryImage->image_url : null,
                'single_price'    => $p->sale_price,
                'purchase_price'  => $p->purchase_price,
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
                        'attr_name' => $v->attributeValue->attribute->name ?? '',
                        'value_name' => $v->attributeValue->value ?? '',
                    ];
                })->all();
            }
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

        DB::transaction(function () use ($request, $isApprove) {
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

            $order = Order::create([
                'customer_id'          => $request->customer_id,
                'location_id'          => $request->location_id,
                'user_id'              => auth()->id(),
                'order_no'             => generate_invoice_no('ORD', Order::class, 'order_no'),
                'order_type'           => 'sale',
                'status'               => $request->status ?? 2,
                'payment_status'       => $request->payment_status ?? 2,
                'payment_method'       => $request->payment_method,
                'final_amount'         => $finalAmount,
                'source'               => $request->input('source', 'POS'),
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
                    'quantity'           => $item['quantity'],
                    'price'              => $item['price'],
                    'discount_type'      => $item['discount_type'],
                    'discount_value'     => $item['discount_value'],
                    'discount_amount'    => $item['discount_amount'],
                    'total'              => $item['total'],
                ]);

                if ($isApprove) {
                    $this->logInventoryChange((int) $item['product_id'], (int) $request->location_id, -$item['quantity'], 'Stock deducted for new sale #' . $order->order_no);
                }
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Sale created successfully.']);
    }

    public function show(Order $sale)
    {
        $this->authorize('view sales');

        // Prevent location user from viewing other locations' sales
        if (auth()->user()->location_id && !auth()->user()->hasRole('super-admin') && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        $sale->load(['customer', 'location', 'user', 'coupon', 'customerAddress', 'items.product.variants.attributeValue.attribute', 'items.product.primaryImage', 'payment']);
        return view('sales.show', ['order' => $sale]);
    }

    public function pdf(Order $sale)
    {
        $this->authorize('view sales');

        if (auth()->user()->location_id && !auth()->user()->hasRole('super-admin') && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        $sale->load(['customer', 'location', 'user', 'coupon', 'customerAddress', 'items.product.variants.attributeValue.attribute', 'payment']);

        $pdf = Pdf::loadView('sales.pdf', ['order' => $sale])
            ->setPaper('a4', 'portrait');

        ActivityLogger::log('Sales', 'export', $sale, null, null, 'Invoice PDF exported for sale #' . $sale->order_no);

        return $pdf->download('sale-' . $sale->order_no . '.pdf');
    }

    public function thermal(Order $sale)
    {
        $this->authorize('view sales');

        if (auth()->user()->location_id && !auth()->user()->hasRole('super-admin') && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        if (($sale->source ?? 'POS') !== 'POS') {
            abort(403, 'Thermal print is only available for POS orders.');
        }

        $sale->load(['customer', 'location', 'user', 'coupon', 'customerAddress', 'items.product.variants.attributeValue.attribute', 'payment']);

        ActivityLogger::log('Sales', 'export', $sale, null, null, 'Thermal receipt printed for sale #' . $sale->order_no);

        return view('sales.thermal', ['order' => $sale]);
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

        if ($sale->status != 1) {
            return redirect()->route('admin.sales.show', $sale)
                ->with('error', 'Only pending sales can be edited.');
        }

        $customers   = Customer::where('status', 1)->orderBy('name')->get();
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }
        $products    = Product::with(['variants.attributeValue.attribute', 'primaryImage'])->where('status', 1)->orderBy('name')->get();
        $sale->load(['items.product.variants.attributeValue.attribute']);
        $defaultLocationId = $isRestricted ? $user->location_id : null;

        $allProducts = $products->map(function ($p) {
            $data = [
                'id'              => $p->id,
                'name'            => $p->name,
                'price'           => $p->sale_price,
                'sku'             => $p->sku,
                'barcode'         => $p->barcode,
                'label'           => $p->name . ' (' . $p->sku . ')',
                'type'            => $p->type,
                'image'           => $p->primaryImage ? $p->primaryImage->image_url : null,
                'single_price'    => $p->sale_price,
                'purchase_price'  => $p->purchase_price,
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
                        'attr_name' => $v->attributeValue->attribute->name ?? '',
                        'value_name' => $v->attributeValue->value ?? '',
                    ];
                })->all();
            }
            return $data;
        })->values();

        $existingItems = $sale->items->map(function ($item) {
            return [
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
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

        if ($sale->status != 1) {
            return response()->json(['status' => 'error', 'message' => 'Only pending sales can be edited.'], 422);
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

        DB::transaction(function () use ($request, $isApprove, $sale) {
            $oldItemsSnapshot = $sale->items->map(function ($item) {
                return [
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                    'price'              => (float) $item->price,
                ];
            })->values()->all();

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

            $sale->update([
                'customer_id'          => $request->customer_id,
                'location_id'          => $request->location_id,
                'payment_method'       => $request->payment_method,
                'status'               => $request->status ?? 2,
                'payment_status'       => $request->payment_status ?? $sale->payment_status ?? 1,
                'final_amount'         => $finalAmount,
                'source'               => $request->input('source', $sale->source ?? 'POS'),
                'order_discount_type'  => $discType,
                'order_discount_value' => $discVal,
                'coupon_id'            => $request->has('coupon_id') ? $request->coupon_id : $sale->coupon_id,
            ]);

            foreach ($itemsData as $item) {
                OrderItem::create([
                    'order_id'           => $sale->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity'           => $item['quantity'],
                    'price'              => $item['price'],
                    'discount_type'      => $item['discount_type'],
                    'discount_value'     => $item['discount_value'],
                    'discount_amount'    => $item['discount_amount'],
                    'total'              => $item['total'],
                ]);

                if ($isApprove) {
                    $this->logInventoryChange((int) $item['product_id'], (int) $request->location_id, -$item['quantity'], 'Stock deducted for updated sale #' . $sale->order_no);
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

        return response()->json(['status' => 'success', 'message' => 'Sale updated successfully.']);
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
                            throw new \Exception('Declined orders cannot be modified.');
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

                        // Require cancellation reason when declining
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
                                $this->logInventoryChange((int) $item->product_id, (int) $sale->location_id, -$item->quantity, 'Stock deducted for sale #' . $sale->order_no . ' status change');
                            }
                        }
                        // Transition from Deducted to Restored group: restore stock
                        elseif ($oldInDeducted && !$newInDeducted) {
                            // Restore stock
                            foreach ($sale->items as $item) {
                                $this->logInventoryChange((int) $item->product_id, (int) $sale->location_id, $item->quantity, 'Stock restored for sale #' . $sale->order_no . ' status change');
                            }
                        }

                        $updateData = ['status' => $newStatus];
                        if ($newStatus == Order::STATUS_DECLINE) {
                            $updateData['cancellation_reason'] = $request->cancellation_reason;
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

            $stockQty = $quantity;

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

            if ($purchasePrice === null) {
                continue;
            }

            $minTotal = $qty * $purchasePrice * 1.10;
            $minFloorTotal += $minTotal;

            if ($itemTotal < $minTotal - 0.01) {
                return 'Final price for "' . $label . '" cannot be less than purchase price + 10% (minimum '
                    . format_price($minTotal) . ' for the selected quantity).';
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

        if ($minFloorTotal > 0 && $finalAmount < $minFloorTotal - 0.01) {
            return 'Order total cannot be less than ' . format_price($minFloorTotal)
                . ' (combined purchase price + 10% of all items).';
        }

        return null;
    }

    public function destroy(Order $sale)
    {
        $this->authorize('delete sales');

        if ($sale->status != 6) {
            return response()->json(['status' => 'error', 'message' => 'Only declined sales can be deleted.'], 422);
        }

        $sale->delete();

        return response()->json(['status' => 'success', 'message' => 'Sale deleted successfully.']);
    }
}
