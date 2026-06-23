<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    public function index()
    {
        $this->authorize('view sales');
        return view('sales.index');
    }

    public function data()
    {
        $this->authorize('view sales');

        $user      = auth()->user();
        $orders    = Order::with(['customer', 'location', 'user'])
            ->where('order_type', 'sale')
            ->when($user->location_id && $user->type !== 'super-admin', fn($q) => $q->where('location_id', $user->location_id))
            ->orderBy('id', 'desc')
            ->get();
        $canEdit                   = auth()->user()->can('edit sales');
        $canDelete                 = auth()->user()->can('delete sales');
        $canEditSalesStatus        = auth()->user()->can('edit sales status');
        $canEditSalesPaymentStatus = auth()->user()->can('edit sales payment status');
        $canDownloadSales          = auth()->user()->can('download sales');

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

        $data = $orders->map(function ($order, $index) use ($canEdit, $canDelete, $canEditSalesStatus, $canEditSalesPaymentStatus, $canDownloadSales, $statusColors, $statusLabels, $paymentColors, $paymentLabels) {
            $status        = '<span class="badge ' . ($statusColors[$order->status] ?? 'bg-label-secondary') . '">' . ($statusLabels[$order->status] ?? ucfirst($order->status)) . '</span>';
            $paymentStatus = '<span class="badge ' . ($paymentColors[$order->payment_status] ?? 'bg-label-secondary') . '">' . ($paymentLabels[$order->payment_status] ?? ucfirst($order->payment_status)) . '</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.sales.show', $order) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            if ($canDownloadSales) {
                $actions .= '<a href="' . route('admin.sales.pdf', $order) . '" class="dropdown-item" target="_blank"><i class="ti ti-file-text me-2"></i>PDF</a>';
            }
            if ($canEdit && $order->status == 1) {
                $actions .= '<a href="' . route('admin.sales.edit', $order) . '" class="dropdown-item"><i class="ti ti-pencil me-2"></i>Edit</a>';
            }
            if ($canEditSalesStatus) {
                $actions .= '<button class="dropdown-item change-sale-status-btn" data-url="' . route('admin.sales.status', $order) . '" data-current="' . $order->status . '"><i class="ti ti-adjustments-horizontal me-2"></i>Update Status</button>';
            }
            if ($canEditSalesPaymentStatus) {
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
                'order_no'       => '<code>' . $order->order_no . '</code>',
                'customer'       => $order->customer->name ?? '<span class="text-muted">Walk-in</span>',
                'location'       => $order->location->name ?? '-',
                'source'         => $sourceBadge,
                'final_amount'   => format_price($order->final_amount),
                'status'         => $status,
                'payment_status' => $paymentStatus,
                'payment_method' => ucwords(str_replace('_', ' ', $order->payment_method)),
                'date_group'     => $order->created_at->format('d M Y'),
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
        $customers   = Customer::where('status', 1)->orderBy('name')->get();
        $locations   = Location::where('status', 1)->orderBy('name')->get();
        $products    = Product::with('variants.attributeValue.attribute')->where('status', 1)->orderBy('name')->get();
        $orderNo     = generate_invoice_no('ORD', Order::class, 'order_no');
        $allProducts = $products->map(function ($p) {
            $data = [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => $p->sale_price,
                'sku'   => $p->sku,
                'label' => $p->name . ' (' . $p->sku . ')',
                'type'  => $p->type,
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
        return view('sales.create', compact('customers', 'locations', 'products', 'orderNo', 'allProducts'));
    }

    public function store(Request $request)
    {
        $this->authorize('create sales');

        $validator = Validator::make($request->all(), [
            'location_id'            => ['required', 'exists:locations,id'],
            'customer_id'            => ['nullable', 'exists:customers,id'],
            'date'                   => ['required', 'date'],
            'payment_method'         => ['required', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.discount_type'  => ['nullable', 'string', 'in:flat,percentage'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_type'          => ['nullable', 'string', 'in:flat,percentage,MANUAL,COUPON'],
            'discount_value'         => ['nullable', 'numeric', 'min:0'],
            'status'                 => ['nullable', 'integer', 'in:1,2,6'],
            'payment_status'         => ['nullable', 'integer', 'in:1,2'],
            'source'                 => ['nullable', 'string', 'in:POS,ONLINE'],
            'coupon_id'              => ['nullable', 'exists:coupons,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $isApprove = ($request->status ?? 2) == 2;

        if ($isApprove) {
            foreach ($request->items as $index => $item) {
                $available = Inventory::where('product_id', $item['product_id'])
                    ->where('location_id', $request->location_id)
                    ->value('quantity') ?? 0;

                if ($available < $item['quantity']) {
                    $product = Product::find($item['product_id']);
                    return response()->json([
                        'status'  => 'error',
                        'message' => ['items' => ['Item #' . ($index + 1) . ' (' . $product->name . '): Only ' . $available . ' available.']],
                    ], 422);
                }
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
                    'product_id'      => $itemData['product_id'],
                    'quantity'        => $qty,
                    'price'           => $price,
                    'discount_type'   => $discType,
                    'discount_value'  => $discVal,
                    'discount_amount' => $discAmount,
                    'total'           => $itemTotal,
                ];
            }

            // Overall discount calculation (removed/hardcoded to 0)
            $order = Order::create([
                'customer_id'     => $request->customer_id,
                'location_id'     => $request->location_id,
                'user_id'         => auth()->id(),
                'order_no'        => generate_invoice_no('ORD', Order::class, 'order_no'),
                'order_type'      => 'sale',
                'status'          => $request->status ?? 2,
                'payment_status'  => $request->payment_status ?? 1,
                'payment_method'  => $request->payment_method,
                'final_amount'    => $totalAmount,
                'source'          => $request->input('source', 'POS'),
                'discount_type'   => in_array($request->discount_type, ['MANUAL', 'COUPON']) ? $request->discount_type : 'MANUAL',
                'coupon_id'       => $request->input('coupon_id', null),
            ]);

            if ($request->filled('date')) {
                $order->created_at = $request->date;
                $order->save();
            }

            foreach ($itemsData as $item) {
                OrderItem::create([
                    'order_id'        => $order->id,
                    'product_id'      => $item['product_id'],
                    'quantity'        => $item['quantity'],
                    'price'           => $item['price'],
                    'discount_type'   => $item['discount_type'],
                    'discount_value'  => $item['discount_value'],
                    'discount_amount' => $item['discount_amount'],
                    'total'           => $item['total'],
                ]);

                if ($isApprove) {
                    Inventory::where('product_id', $item['product_id'])
                        ->where('location_id', $request->location_id)
                        ->decrement('quantity', $item['quantity']);
                }
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Sale created successfully.']);
    }

    public function show(Order $sale)
    {
        $this->authorize('view sales');

        // Prevent location user from viewing other locations' sales
        if (auth()->user()->location_id && auth()->user()->type !== 'super-admin' && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        $sale->load(['customer', 'location', 'user', 'items.product.variants.attributeValue.attribute', 'items.product.primaryImage']);
        return view('sales.show', ['order' => $sale]);
    }

    public function pdf(Order $sale)
    {
        $this->authorize('view sales');

        if (auth()->user()->location_id && auth()->user()->type !== 'super-admin' && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        $sale->load(['customer', 'location', 'user', 'items.product.variants.attributeValue.attribute']);

        $pdf = Pdf::loadView('sales.pdf', ['order' => $sale])
            ->setPaper('a4', 'portrait');

        return $pdf->download('sale-' . $sale->order_no . '.pdf');
    }

    public function edit(Order $sale)
    {
        $this->authorize('edit sales');

        // Prevent location user from editing other locations' sales
        if (auth()->user()->location_id && auth()->user()->type !== 'super-admin' && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        if ($sale->status != 1) {
            return redirect()->route('admin.sales.show', $sale)
                ->with('error', 'Only pending sales can be edited.');
        }

        $customers   = Customer::where('status', 1)->orderBy('name')->get();
        $locations   = Location::where('status', 1)->orderBy('name')->get();
        $products    = Product::with('variants.attributeValue.attribute')->where('status', 1)->orderBy('name')->get();
        $sale->load(['items.product.variants.attributeValue.attribute']);

        $allProducts = $products->map(function ($p) {
            $data = [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => $p->sale_price,
                'sku'   => $p->sku,
                'label' => $p->name . ' (' . $p->sku . ')',
                'type'  => $p->type,
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
                'product_id'     => $item->product_id,
                'price'          => $item->price,
                'quantity'       => $item->quantity,
                'discount_type'  => $item->discount_type ?? 'flat',
                'discount_value' => $item->discount_value ?? 0,
            ];
        })->values();

        return view('sales.edit', ['order' => $sale, 'customers' => $customers, 'locations' => $locations, 'products' => $products, 'allProducts' => $allProducts, 'existingItems' => $existingItems]);
    }

    public function update(Request $request, Order $sale)
    {
        $this->authorize('edit sales');

        if ($sale->status != 1) {
            return response()->json(['status' => 'error', 'message' => 'Only pending sales can be edited.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'location_id'        => ['required', 'exists:locations,id'],
            'customer_id'        => ['nullable', 'exists:customers,id'],
            'date'               => ['required', 'date'],
            'payment_method'     => ['required', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.discount_type'  => ['nullable', 'string', 'in:flat,percentage'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_type'          => ['nullable', 'string', 'in:flat,percentage,MANUAL,COUPON'],
            'discount_value'         => ['nullable', 'numeric', 'min:0'],
            'status'                 => ['nullable', 'integer', 'in:1,2,6'],
            'payment_status'         => ['nullable', 'integer', 'in:1,2'],
            'source'                 => ['nullable', 'string', 'in:POS,ONLINE'],
            'coupon_id'              => ['nullable', 'exists:coupons,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $isApprove = ($request->status ?? 2) == 2;

        if ($isApprove) {
            foreach ($request->items as $index => $item) {
                // Since the order was previously 'pending', no stock was deducted.
                // Thus, the available stock is simply the current inventory quantity.
                $available = Inventory::where('product_id', $item['product_id'])
                    ->where('location_id', $request->location_id)
                    ->value('quantity') ?? 0;

                if ($available < $item['quantity']) {
                    $product = Product::find($item['product_id']);
                    return response()->json([
                        'status'  => 'error',
                        'message' => ['items' => ['Item #' . ($index + 1) . ' (' . $product->name . '): Only ' . $available . ' available.']],
                    ], 422);
                }
            }
        }

        DB::transaction(function () use ($request, $isApprove, $sale) {
            // Delete old items (we don't need to increment inventory because they were pending and had no stock deducted)
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
                    'product_id'      => $itemData['product_id'],
                    'quantity'        => $qty,
                    'price'           => $price,
                    'discount_type'   => $discType,
                    'discount_value'  => $discVal,
                    'discount_amount' => $discAmount,
                    'total'           => $itemTotal,
                ];
            }

            $sale->update([
                'customer_id'     => $request->customer_id,
                'location_id'     => $request->location_id,
                'payment_method'  => $request->payment_method,
                'status'          => $request->status ?? 2,
                'payment_status'  => $request->payment_status ?? 1,
                'final_amount'    => $totalAmount,
                'source'          => $request->input('source', $sale->source ?? 'POS'),
                'discount_type'   => in_array($request->discount_type, ['MANUAL', 'COUPON']) ? $request->discount_type : ($sale->discount_type ?? 'MANUAL'),
                'coupon_id'       => $request->has('coupon_id') ? $request->coupon_id : $sale->coupon_id,
            ]);

            if ($request->filled('date')) {
                $sale->created_at = $request->date;
                $sale->save();
            }

            foreach ($itemsData as $item) {
                OrderItem::create([
                    'order_id'        => $sale->id,
                    'product_id'      => $item['product_id'],
                    'quantity'        => $item['quantity'],
                    'price'           => $item['price'],
                    'discount_type'   => $item['discount_type'],
                    'discount_value'  => $item['discount_value'],
                    'discount_amount' => $item['discount_amount'],
                    'total'           => $item['total'],
                ]);

                if ($isApprove) {
                    Inventory::where('product_id', $item['product_id'])
                        ->where('location_id', $request->location_id)
                        ->decrement('quantity', $item['quantity']);
                }
            }
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
            'status'         => ['nullable', 'integer', 'in:1,2,3,4,5,6'],
            'payment_status' => ['nullable', 'integer', 'in:1,2'],
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

                        // 2. Backward progression validation
                        if ($oldStatus == Order::STATUS_APPROVE && $newStatus == Order::STATUS_PENDING) {
                            throw new \Exception('Cannot change status back to Pending once approved.');
                        }
                        if ($oldStatus == Order::STATUS_SHIPPED && in_array($newStatus, [Order::STATUS_PENDING, Order::STATUS_APPROVE, Order::STATUS_DECLINE])) {
                            throw new \Exception('Cannot change status back once shipped.');
                        }
                        if ($oldStatus == Order::STATUS_OUT_FOR_DELIVERY && in_array($newStatus, [Order::STATUS_PENDING, Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_DECLINE])) {
                            throw new \Exception('Cannot change status back once out for delivery.');
                        }

                        $deductedGroup = [2, 3, 4, 5];
                        $restoredGroup = [1, 6];

                        $oldInDeducted = in_array($oldStatus, $deductedGroup);
                        $newInDeducted = in_array($newStatus, $deductedGroup);

                        // Transition from Restored to Deducted group: deduct stock
                        if (!$oldInDeducted && $newInDeducted) {
                            foreach ($sale->items as $item) {
                                $stock = Inventory::where('product_id', $item->product_id)
                                    ->where('location_id', $sale->location_id)
                                    ->value('quantity') ?? 0;

                                if ($stock < $item->quantity) {
                                    $product = Product::find($item->product_id);
                                    throw new \Exception('Product "' . $product->name . '" only has ' . $stock . ' units in stock.');
                                }
                            }

                            // Deduct stock
                            foreach ($sale->items as $item) {
                                Inventory::where('product_id', $item->product_id)
                                    ->where('location_id', $sale->location_id)
                                    ->decrement('quantity', $item->quantity);
                            }
                        }
                        // Transition from Deducted to Restored group: restore stock
                        elseif ($oldInDeducted && !$newInDeducted) {
                            // Restore stock
                            foreach ($sale->items as $item) {
                                Inventory::where('product_id', $item->product_id)
                                    ->where('location_id', $sale->location_id)
                                    ->increment('quantity', $item->quantity);
                            }
                        }

                        $updateData = ['status' => $newStatus];
                        if ($newStatus == Order::STATUS_DECLINE) {
                            $updateData['payment_status'] = 1;
                        }

                        // Record dates for status change
                        if ($newStatus == Order::STATUS_APPROVE) {
                            $updateData['confirmed_at'] = now();
                        } elseif ($newStatus == Order::STATUS_SHIPPED) {
                            $updateData['shipped_at'] = now();
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

        return response()->json(['status' => 'success', 'message' => 'Sale status updated successfully.']);
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
