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
            ->when($user->location_id, fn($q) => $q->where('location_id', $user->location_id))
            ->latest()
            ->get();
        $canEdit   = auth()->user()->can('edit sales');
        $canDelete = auth()->user()->can('delete sales');

        $statusColors = [
            'pending'   => 'bg-label-warning',
            'paid'      => 'bg-label-info',
            'completed' => 'bg-label-success',
            'cancelled' => 'bg-label-danger',
        ];

        $paymentColors = [
            'pending' => 'bg-label-warning',
            'paid'    => 'bg-label-success',
            'failed'  => 'bg-label-danger',
        ];

        $data = $orders->map(function ($order, $index) use ($canEdit, $canDelete, $statusColors, $paymentColors) {
            $status        = '<span class="badge ' . ($statusColors[$order->status] ?? 'bg-label-secondary') . '">' . ucfirst($order->status) . '</span>';
            $paymentStatus = '<span class="badge ' . ($paymentColors[$order->payment_status] ?? 'bg-label-secondary') . '">' . ucfirst($order->payment_status) . '</span>';

            $actions = '<a href="' . route('admin.sales.show', $order) . '" class="btn btn-sm btn-icon btn-label-secondary me-1"><i class="ti ti-eye"></i></a>';
            if ($canEdit && in_array($order->status, ['pending', 'paid'])) {
                $actions .= '<a href="' . route('admin.sales.edit', $order) . '" class="btn btn-sm btn-icon btn-label-info me-1"><i class="ti ti-pencil"></i></a>';
            }
            if ($canDelete && $order->status === 'cancelled') {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.sales.destroy', $order) . '" data-row-id="sale-row-' . $order->id . '"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'          => $index + 1,
                'order_no'       => '<code>' . $order->order_no . '</code>',
                'customer'       => $order->customer->name ?? '<span class="text-muted">Walk-in</span>',
                'location'       => $order->location->name ?? '-',
                'final_amount'   => format_price($order->final_amount),
                'status'         => $status,
                'payment_status' => $paymentStatus,
                'payment_method' => ucfirst($order->payment_method),
                'created_at'     => format_date($order->created_at),
                'actions'        => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create sales');
        $customers   = Customer::where('status', 'active')->orderBy('name')->get();
        $locations   = Location::where('status', 'active')->orderBy('name')->get();
        $products    = Product::where('status', 'active')->orderBy('name')->get();
        $orderNo     = generate_invoice_no('ORD', Order::class, 'order_no');
        $allProducts = $products->map(function ($p) {
            return [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => $p->sale_price,
                'sku'   => $p->sku,
                'label' => $p->name . ' (' . $p->sku . ')',
            ];
        })->values();
        return view('sales.create', compact('customers', 'locations', 'products', 'orderNo', 'allProducts'));
    }

    public function store(Request $request)
    {
        $this->authorize('create sales');

        $validator = Validator::make($request->all(), [
            'location_id'        => ['required', 'exists:locations,id'],
            'customer_id'        => ['nullable', 'exists:customers,id'],
            'payment_method'     => ['required', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        foreach ($request->items as $index => $item) {
            $stock = Inventory::where('product_id', $item['product_id'])
                ->where('location_id', $request->location_id)
                ->value('quantity') ?? 0;

            if ($stock < $item['quantity']) {
                $product = Product::find($item['product_id']);
                return response()->json([
                    'status'  => 'error',
                    'message' => ['items' => ['Item #' . ($index + 1) . ' (' . $product->name . '): Only ' . $stock . ' in stock at selected location.']],
                ], 422);
            }
        }

        DB::transaction(function () use ($request) {
            $totalAmount   = collect($request->items)->sum(fn($item) => ($item['price'] * $item['quantity']));
            $finalAmount   = $totalAmount;

            $order = Order::create([
                'customer_id'    => $request->customer_id,
                'location_id'    => $request->location_id,
                'user_id'        => auth()->id(),
                'order_no'       => generate_invoice_no('ORD', Order::class, 'order_no'),
                'order_type'     => 'sale',
                'status'         => 'pending',
                'payment_status' => $request->payment_method === 'cash' ? 'paid' : 'pending',
                'payment_method' => $request->payment_method,
                'total_amount'   => $totalAmount,
                'final_amount'   => $finalAmount,
            ]);

            foreach ($request->items as $itemData) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $itemData['product_id'],
                    'quantity'   => $itemData['quantity'],
                    'price'      => $itemData['price'],
                    'total'      => ($itemData['price'] * $itemData['quantity']),
                ]);

                Inventory::where('product_id', $itemData['product_id'])
                    ->where('location_id', $request->location_id)
                    ->decrement('quantity', $itemData['quantity']);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Sale created successfully.']);
    }

    public function show(Order $sale)
    {
        $this->authorize('view sales');

        // Prevent location user from viewing other locations' sales
        if (auth()->user()->location_id && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        $sale->load(['customer', 'location', 'user', 'items.product.primaryImage']);
        return view('sales.show', ['order' => $sale]);
    }

    public function pdf(Order $sale)
    {
        $this->authorize('view sales');

        if (auth()->user()->location_id && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        $sale->load(['customer', 'location', 'user', 'items.product']);

        $pdf = Pdf::loadView('sales.pdf', ['order' => $sale])
            ->setPaper('a4', 'portrait');

        return $pdf->download('sale-' . $sale->order_no . '.pdf');
    }

    public function edit(Order $sale)
    {
        $this->authorize('edit sales');

        // Prevent location user from editing other locations' sales
        if (auth()->user()->location_id && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        if (!in_array($sale->status, ['pending', 'paid'])) {
            return redirect()->route('admin.sales.show', $sale)
                ->with('error', 'Only pending or paid sales can be edited.');
        }

        $customers   = Customer::where('status', 'active')->orderBy('name')->get();
        $locations   = Location::where('status', 'active')->orderBy('name')->get();
        $products    = Product::where('status', 'active')->orderBy('name')->get();
        $sale->load(['items.product']);

        $allProducts = $products->map(function ($p) {
            return ['id' => $p->id, 'name' => $p->name, 'price' => $p->sale_price, 'sku' => $p->sku, 'label' => $p->name . ' (' . $p->sku . ')'];
        })->values();

        $existingItems = $sale->items->map(function ($item) {
            return ['product_id' => $item->product_id, 'price' => $item->price, 'quantity' => $item->quantity];
        })->values();

        return view('sales.edit', ['order' => $sale, 'customers' => $customers, 'locations' => $locations, 'products' => $products, 'allProducts' => $allProducts, 'existingItems' => $existingItems]);
    }

    public function update(Request $request, Order $sale)
    {
        $this->authorize('edit sales');

        if (!in_array($sale->status, ['pending', 'paid'])) {
            return response()->json(['status' => 'error', 'message' => 'Only pending or paid sales can be edited.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'location_id'        => ['required', 'exists:locations,id'],
            'customer_id'        => ['nullable', 'exists:customers,id'],
            'payment_method'     => ['required', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        foreach ($request->items as $index => $item) {
            $oldItem   = $sale->items->firstWhere('product_id', $item['product_id']);
            $available = (Inventory::where('product_id', $item['product_id'])->where('location_id', $request->location_id)->value('quantity') ?? 0) + ($oldItem ? $oldItem->quantity : 0);

            if ($available < $item['quantity']) {
                $product = Product::find($item['product_id']);
                return response()->json(['status' => 'error', 'message' => ['items' => ['Item #' . ($index + 1) . ' (' . $product->name . '): Only ' . $available . ' available.']]], 422);
            }
        }

        DB::transaction(function () use ($request, $sale) {
            foreach ($sale->items as $oldItem) {
                Inventory::where('product_id', $oldItem->product_id)->where('location_id', $sale->location_id)->increment('quantity', $oldItem->quantity);
            }
            $sale->items()->delete();

            $totalAmount   = collect($request->items)->sum(fn($item) => ($item['price'] * $item['quantity']));

            $sale->update([
                'customer_id'    => $request->customer_id,
                'location_id'    => $request->location_id,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'cash' ? 'paid' : 'pending',
                'total_amount'   => $totalAmount,
                'final_amount'   => $totalAmount,
            ]);

            foreach ($request->items as $itemData) {
                OrderItem::create([
                    'order_id'   => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'quantity'   => $itemData['quantity'],
                    'price'      => $itemData['price'],
                    'total'      => ($itemData['price'] * $itemData['quantity']),
                ]);
                Inventory::where('product_id', $itemData['product_id'])->where('location_id', $request->location_id)->decrement('quantity', $itemData['quantity']);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Sale updated successfully.']);
    }

    public function updateStatus(Request $request, Order $sale)
    {
        $this->authorize('edit sales');

        $validator = Validator::make($request->all(), [
            'status'         => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $sale) {
            if ($request->filled('status')) {
                $newStatus = $request->status;
                if ($newStatus === 'cancelled' && $sale->status !== 'cancelled') {
                    foreach ($sale->items as $item) {
                        Inventory::where('product_id', $item->product_id)->where('location_id', $sale->location_id)->increment('quantity', $item->quantity);
                    }
                    $sale->update(['status' => $newStatus, 'payment_status' => 'failed']);
                } else {
                    $sale->update(['status' => $newStatus]);
                }
            }

            if ($request->filled('payment_status')) {
                $sale->update(['payment_status' => $request->payment_status]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Sale updated successfully.']);
    }

    public function destroy(Order $sale)
    {
        $this->authorize('delete sales');

        if ($sale->status !== 'cancelled') {
            return response()->json(['status' => 'error', 'message' => 'Only cancelled sales can be deleted.'], 422);
        }

        $sale->delete();

        return response()->json(['status' => 'success', 'message' => 'Sale deleted successfully.']);
    }
}
