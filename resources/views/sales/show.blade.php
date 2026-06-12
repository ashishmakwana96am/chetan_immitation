@extends('layouts.app')

@section('title', 'Sale ' . $order->order_no)

@section('content')
    @php
        $statusColors = [
            1 => 'bg-label-secondary',
            2 => 'bg-label-success',
            3 => 'bg-label-danger',
        ];
        $statusLabels = [
            1 => 'Pending',
            2 => 'Approve',
            3 => 'Decline',
        ];

        $paymentColors = [
            1 => 'bg-label-warning',
            2 => 'bg-label-info',
        ];
        $paymentLabels = [
            1 => 'Pending',
            2 => 'Paid',
        ];

        $totalItemDiscount = $order->items->sum('discount_amount');
        $subtotal = $order->final_amount + $totalItemDiscount;
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-semibold mb-0">Sale <code>{{ $order->order_no }}</code></h4>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.sales.pdf', $order) }}" class="btn btn-label-info" target="_blank">
                <i class="ti ti-file-type-pdf me-1"></i> Download PDF
            </a>
            @can('edit sales')
                @if($order->status == 1)
                    <a href="{{ route('admin.sales.edit', $order) }}" class="btn btn-label-info">
                        <i class="ti ti-pencil me-1"></i> Edit
                    </a>
                @endif
                @if(($order->payment_status ?? 1) == 1 && $order->status == 2)
                    <button class="btn btn-success"
                        data-common-confirm="{{ route('admin.sales.status', $order) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Mark as Paid"
                        data-confirm-text="Mark this sale as paid?"
                        data-confirm-btn="Yes, Mark Paid"
                        data-confirm-btn-class="btn-success"
                        data-confirm-data='{"payment_status":2}'>
                        <i class="ti ti-credit-card me-1"></i> Mark Paid
                    </button>
                @endif
                @if($order->status == 1)
                    <button class="btn btn-primary"
                        data-common-confirm="{{ route('admin.sales.status', $order) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Approve Sale"
                        data-confirm-text="Mark this sale as approved?"
                        data-confirm-btn="Yes, Approve"
                        data-confirm-btn-class="btn-primary"
                        data-confirm-data='{"status":2}'>
                        <i class="ti ti-check me-1"></i> Approve
                    </button>
                    <button class="btn btn-label-danger"
                        data-common-confirm="{{ route('admin.sales.status', $order) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Decline Sale"
                        data-confirm-text="Decline this sale? Stock will be restored."
                        data-confirm-btn="Yes, Decline"
                        data-confirm-btn-class="btn-danger"
                        data-confirm-data='{"status":3}'>
                        <i class="ti ti-x me-1"></i> Decline
                    </button>
                @endif
            @endcan
            <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- Sale Info -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Sale Info</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Sale No</p>
                        <p class="fw-semibold mb-0"><code>{{ $order->order_no }}</code></p>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Customer</p>
                        <p class="fw-semibold mb-0">{{ $order->customer->name ?? 'Walk-in Customer' }}</p>
                        @if($order->customer?->phone)
                            <small class="text-muted">{{ $order->customer->phone }}</small>
                        @endif
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Location</p>
                        <p class="mb-0">{{ $order->location->name ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Status</p>
                        <span class="badge {{ $statusColors[$order->status] ?? 'bg-label-secondary' }}">{{ $statusLabels[$order->status] ?? 'Pending' }}</span>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Payment Status</p>
                        <span class="badge {{ $paymentColors[$order->payment_status ?? 1] ?? 'bg-label-secondary' }}">{{ $paymentLabels[$order->payment_status ?? 1] ?? 'Pending' }}</span>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Payment Method</p>
                        <p class="mb-0">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Source</p>
                        <p class="mb-0">
                            @if(($order->source ?? 'POS') === 'ONLINE')
                                <span class="badge bg-label-success">ONLINE</span>
                            @else
                                <span class="badge bg-label-info">POS</span>
                            @endif
                        </p>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Discount Type</p>
                        <p class="mb-0">
                            @if(($order->discount_type ?? 'MANUAL') === 'COUPON')
                                <span class="badge bg-label-primary">COUPON</span>
                            @else
                                <span class="badge bg-label-secondary">MANUAL</span>
                            @endif
                        </p>
                    </div>
                    @if($order->coupon_id)
                        <div class="mb-3">
                            <p class="text-muted small mb-1">Coupon</p>
                            <p class="mb-0"><code>{{ $order->coupon->code ?? '-' }}</code></p>
                        </div>
                    @endif
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Served By</p>
                        <p class="mb-0">{{ $order->user->name ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Date</p>
                        <p class="mb-0">{{ format_date($order->created_at) }}</p>
                    </div>

                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Sale Items</h5></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $preparedItems = collect();
                                $groupedByProduct = $order->items->groupBy('product_id');

                                foreach ($groupedByProduct as $productId => $siblings) {
                                    $siblings = $siblings->sortBy('id')->values();
                                    $firstItem = $siblings->first();
                                    $product = $firstItem->product ?? null;
                                    
                                    if ($product && $product->type === 'variable') {
                                        $parentItem = $firstItem;
                                        $parentItem->is_parent = true;
                                        $parentItem->resolved_variant_name = null;
                                        
                                        $variantItems = $siblings->slice(1)->values();
                                        $variants = $product->variants ?? collect();
                                        
                                        $matchedMap = [];
                                        $unmatchedSiblings = $variantItems->all();
                                        
                                        foreach ($variants as $v) {
                                            $matchedIdx = -1;
                                            foreach ($unmatchedSiblings as $idx => $sibling) {
                                                if (isset($sibling) && (float)$sibling->price === (float)$v->sale_price) {
                                                    $matchedIdx = $idx;
                                                    break;
                                                }
                                            }
                                            if ($matchedIdx !== -1) {
                                                $matchedSibling = $unmatchedSiblings[$matchedIdx];
                                                $variantName = null;
                                                if ($v->attributeValue) {
                                                    $variantName = ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '');
                                                }
                                                $matchedSibling->resolved_variant_name = $variantName;
                                                $matchedSibling->is_parent = false;
                                                $matchedMap[$matchedSibling->id] = $matchedSibling;
                                                unset($unmatchedSiblings[$matchedIdx]);
                                            }
                                        }
                                        
                                        $unmatchedSiblings = array_values($unmatchedSiblings);
                                        $unmatchedVariants = [];
                                        foreach ($variants as $v) {
                                            $variantName = null;
                                            if ($v->attributeValue) {
                                                $variantName = ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '');
                                            }
                                            
                                            $alreadyMatched = false;
                                            foreach ($matchedMap as $ms) {
                                                if ($ms->resolved_variant_name === $variantName) {
                                                    $alreadyMatched = true;
                                                    break;
                                                }
                                            }
                                            
                                            if (!$alreadyMatched) {
                                                $unmatchedVariants[] = $v;
                                            }
                                        }
                                        
                                        foreach ($unmatchedSiblings as $idx => $sibling) {
                                            if (isset($unmatchedVariants[$idx])) {
                                                $v = $unmatchedVariants[$idx];
                                                $variantName = null;
                                                if ($v->attributeValue) {
                                                    $variantName = ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '');
                                                }
                                                $sibling->resolved_variant_name = $variantName;
                                            } else {
                                                $sibling->resolved_variant_name = null;
                                            }
                                            $sibling->is_parent = false;
                                            $matchedMap[$sibling->id] = $sibling;
                                        }
                                        
                                        $preparedItems->push($parentItem);
                                        foreach ($variantItems as $vItem) {
                                            $preparedItems->push($matchedMap[$vItem->id] ?? $vItem);
                                        }
                                    } else {
                                        foreach ($siblings as $sibling) {
                                            $sibling->is_parent = true;
                                            $sibling->resolved_variant_name = null;
                                            $preparedItems->push($sibling);
                                        }
                                    }
                                }
                            @endphp

                            @foreach($preparedItems as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td @if(!$item->is_parent) style="padding-left: 4.5rem;" @endif>
                                        <p class="fw-semibold mb-0">
                                            @if(!$item->is_parent)
                                                <span class="text-muted me-2 fw-bold" style="font-size: 1.1rem;">↳</span>
                                                <span class="text-muted small">{{ $item->resolved_variant_name }}</span>
                                            @else
                                                {{ $item->product->name ?? '-' }}
                                            @endif
                                        </p>
                                        @if($item->is_parent)
                                            <small class="text-muted">{{ $item->product->sku ?? '' }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">{{ format_price($item->price) }}</td>
                                    <td class="text-end text-nowrap">{{ $item->quantity }}</td>
                                    <td class="text-end text-nowrap">
                                        @if($item->discount_amount > 0)
                                            @if($item->discount_type === 'percentage')
                                                {{ number_format($item->discount_value, 2) }}% 
                                                <small class="text-muted d-block">( -{{ format_price($item->discount_amount) }} )</small>
                                            @else
                                                -{{ format_price($item->discount_amount) }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap fw-semibold text-primary">{{ format_price($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="5" class="text-end fw-bold">Items Total</td>
                                <td class="text-end text-nowrap fw-bold">{{ format_price($subtotal) }}</td>
                            </tr>
                            @if($totalItemDiscount > 0)
                            <tr>
                                <td colspan="5" class="text-end fw-bold text-danger">Discount</td>
                                <td class="text-end text-nowrap fw-bold text-danger">-{{ format_price($totalItemDiscount) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="5" class="text-end fw-bold text-primary fs-5">Final Amount</td>
                                <td class="text-end text-nowrap fw-bold text-primary fs-5">{{ format_price($order->final_amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
