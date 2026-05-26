<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseAllocation;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        $this->authorize('view purchases');
        return view('purchases.index');
    }

    public function data()
    {
        $this->authorize('view purchases');

        $invoices  = PurchaseInvoice::with(['supplier', 'createdBy'])->latest()->get();
        $canEdit   = auth()->user()->can('edit purchases');
        $canDelete = auth()->user()->can('delete purchases');

        $data = $invoices->map(function ($invoice, $index) use ($canEdit, $canDelete) {
            $statusColors = [
                'draft'     => 'bg-label-secondary',
                'confirmed' => 'bg-label-success',
                'cancelled' => 'bg-label-danger',
            ];
            $statusBadge = '<span class="badge ' . ($statusColors[$invoice->status] ?? 'bg-label-secondary') . '">' . ucfirst($invoice->status) . '</span>';

            $actions = '<a href="' . route('admin.purchases.show', $invoice) . '" class="btn btn-sm btn-icon btn-label-secondary me-1"><i class="ti ti-eye"></i></a>';
            if ($canEdit && $invoice->status === 'draft') {
                $actions .= '<a href="' . route('admin.purchases.edit', $invoice) . '" class="btn btn-sm btn-icon btn-label-info me-1"><i class="ti ti-pencil"></i></a>';
            }
            if ($canDelete && $invoice->status === 'draft') {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.purchases.destroy', $invoice) . '" data-row-id="purchase-row-' . $invoice->id . '"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'        => $index + 1,
                'invoice_no'   => '<code>' . $invoice->invoice_no . '</code>',
                'supplier'     => $invoice->supplier->name ?? '-',
                'total_amount' => format_price($invoice->total_amount),
                'status'       => $statusBadge,
                'created_by'   => $invoice->createdBy->name ?? '-',
                'created_at'   => format_date($invoice->created_at),
                'actions'      => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function show(PurchaseInvoice $purchase)
    {
        $this->authorize('view purchases');
        $purchase->load(['supplier', 'createdBy', 'items.product.primaryImage', 'items.allocations.location']);
        return view('purchases.show', compact('purchase'));
    }

    public function create()
    {
        $this->authorize('create purchases');
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $products  = Product::where('status', 'active')->orderBy('name')->get();
        $locations = Location::where('status', 'active')->orderBy('name')->get();
        $invoiceNo = generate_invoice_no('PUR', PurchaseInvoice::class);
        return view('purchases.create', compact('suppliers', 'products', 'locations', 'invoiceNo'));
    }

    public function store(Request $request)
    {
        $this->authorize('create purchases');

        $validator = Validator::make($request->all(), [
            'supplier_id'            => ['required', 'exists:suppliers,id'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        // Validate allocation totals match item quantities
        foreach ($request->items as $index => $item) {
            $allocatedQty = collect($item['allocations'] ?? [])->sum(fn($a) => (int) $a['quantity']);
            if ($allocatedQty !== (int) $item['quantity']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['items' => ['Item #' . ($index + 1) . ': Allocated (' . $allocatedQty . ') must equal item quantity (' . $item['quantity'] . ')']],
                ], 422);
            }
        }

        DB::transaction(function () use ($request) {
            $totalAmount = collect($request->items)->sum(fn($item) => $item['purchase_price'] * $item['quantity']);

            $invoice = PurchaseInvoice::create([
                'supplier_id'  => $request->supplier_id,
                'invoice_no'   => generate_invoice_no('PUR', PurchaseInvoice::class),
                'total_amount' => $totalAmount,
                'status'       => 'draft',
                'created_by'   => auth()->id(),
            ]);

            foreach ($request->items as $itemData) {
                $item = PurchaseItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id'          => $itemData['product_id'],
                    'purchase_price'      => $itemData['purchase_price'],
                    'quantity'            => $itemData['quantity'],
                    'total'               => $itemData['purchase_price'] * $itemData['quantity'],
                ]);

                // Only save allocations with qty > 0
                foreach ($itemData['allocations'] ?? [] as $allocationData) {
                    if ((int) $allocationData['quantity'] <= 0) continue;
                    PurchaseAllocation::create([
                        'purchase_item_id' => $item->id,
                        'location_id'      => $allocationData['location_id'],
                        'quantity'         => $allocationData['quantity'],
                    ]);
                }
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase invoice created successfully.',
        ]);
    }

    public function edit(PurchaseInvoice $purchase)
    {
        $this->authorize('edit purchases');

        if ($purchase->status !== 'draft') {
            return redirect()->route('admin.purchases.show', $purchase)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $products  = Product::where('status', 'active')->orderBy('name')->get();
        $locations = Location::where('status', 'active')->orderBy('name')->get();
        $purchase->load(['items.product', 'items.allocations']);

        $existingItems = $purchase->items->map(function ($item) {
            return [
                'product_id'     => $item->product_id,
                'purchase_price' => $item->purchase_price,
                'quantity'       => $item->quantity,
                'allocations'    => $item->allocations->map(function ($a) {
                    return [
                        'location_id' => $a->location_id,
                        'quantity'    => $a->quantity,
                    ];
                })->values(),
            ];
        })->values();

        return view('purchases.edit', compact('purchase', 'suppliers', 'products', 'locations', 'existingItems'));
    }

    public function update(Request $request, PurchaseInvoice $purchase)
    {
        $this->authorize('edit purchases');

        if ($purchase->status !== 'draft') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only draft invoices can be edited.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id'            => ['required', 'exists:suppliers,id'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        foreach ($request->items as $index => $item) {
            $allocatedQty = collect($item['allocations'] ?? [])->sum(fn($a) => (int) $a['quantity']);
            if ($allocatedQty !== (int) $item['quantity']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['items' => ['Item #' . ($index + 1) . ': Allocated (' . $allocatedQty . ') must equal item quantity (' . $item['quantity'] . ')']],
                ], 422);
            }
        }

        DB::transaction(function () use ($request, $purchase) {
            $totalAmount = collect($request->items)->sum(fn($item) => $item['purchase_price'] * $item['quantity']);

            $purchase->update([
                'supplier_id'  => $request->supplier_id,
                'total_amount' => $totalAmount,
            ]);

            $purchase->items()->delete();

            foreach ($request->items as $itemData) {
                $item = PurchaseItem::create([
                    'purchase_invoice_id' => $purchase->id,
                    'product_id'          => $itemData['product_id'],
                    'purchase_price'      => $itemData['purchase_price'],
                    'quantity'            => $itemData['quantity'],
                    'total'               => $itemData['purchase_price'] * $itemData['quantity'],
                ]);

                foreach ($itemData['allocations'] ?? [] as $allocationData) {
                    if ((int) $allocationData['quantity'] <= 0) continue;
                    PurchaseAllocation::create([
                        'purchase_item_id' => $item->id,
                        'location_id'      => $allocationData['location_id'],
                        'quantity'         => $allocationData['quantity'],
                    ]);
                }
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase invoice updated successfully.',
        ]);
    }

    public function updateStatus(Request $request, PurchaseInvoice $purchase)
    {
        $this->authorize('edit purchases');

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:draft,confirmed,cancelled'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $newStatus = $request->status;

        if ($purchase->status === $newStatus) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invoice is already ' . $newStatus . '.',
            ], 422);
        }

        if ($purchase->status !== 'draft') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only draft invoices can be updated.',
            ], 422);
        }

        DB::transaction(function () use ($purchase, $newStatus) {
            $purchase->update(['status' => $newStatus]);

            if ($newStatus === 'confirmed') {
                $purchase->load('items.allocations');
                foreach ($purchase->items as $item) {
                    foreach ($item->allocations as $allocation) {
                        Inventory::updateOrCreate(
                            [
                                'product_id'  => $item->product_id,
                                'location_id' => $allocation->location_id,
                            ],
                            ['created_by' => auth()->id()]
                        );
                        Inventory::where('product_id', $item->product_id)
                            ->where('location_id', $allocation->location_id)
                            ->increment('quantity', $allocation->quantity);
                    }
                }
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Invoice status updated to ' . $newStatus . '.',
        ]);
    }

    public function destroy(PurchaseInvoice $purchase)
    {
        $this->authorize('delete purchases');

        if ($purchase->status !== 'draft') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only draft invoices can be deleted.',
            ], 422);
        }

        $purchase->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Purchase invoice deleted successfully.',
        ]);
    }

    public function pdf(PurchaseInvoice $purchase)
    {
        $this->authorize('view purchases');
        $purchase->load(['supplier', 'createdBy', 'items.product', 'items.allocations.location']);

        $pdf = Pdf::loadView('purchases.pdf', compact('purchase'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('purchase-' . $purchase->invoice_no . '.pdf');
    }

    public function getProductPrice(Product $product)
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'purchase_price' => $product->purchase_price,
                'name'           => $product->name,
                'sku'            => $product->sku,
            ],
        ]);
    }
}
