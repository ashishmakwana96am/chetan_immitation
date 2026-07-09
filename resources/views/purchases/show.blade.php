@extends('layouts.app')

@section('title', 'Purchase ' . $purchase->invoice_no)

@section('page-css')
<style>
    .sale-info-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 9px 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        font-size: 0.875rem;
    }
    .sale-info-row:last-child { border-bottom: none; }
    .sale-info-label { color: #8592a3; font-size: 0.8rem; flex-shrink: 0; padding-right: 8px; }
    .sale-info-value { font-weight: 500; text-align: right; }
    .card-section-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #B4771E;
        margin-bottom: 4px;
    }
    .card { border: 1px solid rgba(75,70,92,0.1); border-radius: 0.5rem; }
    .card-header {
        background: #fff;
        border-bottom: 1px solid rgba(75,70,92,0.08);
        padding: 0.9rem 1.25rem;
        border-radius: 0.5rem 0.5rem 0 0 !important;
    }
    .card-header .card-title-icon {
        width: 30px; height: 30px;
        background: rgba(180,119,30,0.1);
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .card-header .card-title-icon i { color: #B4771E; font-size: 1rem; }
    .tfoot-label { font-size: 0.82rem; font-weight: 600; color: #5d596c; }
    .tfoot-amount { font-size: 0.82rem; font-weight: 600; }
</style>
@endsection

@section('content')
    @php
        $statusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-danger'];
        $statusLabels = [1 => 'Pending', 2 => 'Approve', 3 => 'Decline'];
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Purchase <code>{{ $purchase->invoice_no }}</code></h4>
            <small class="text-muted">{{ format_date($purchase->created_at) }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <!-- <a href="{{ route('admin.purchases.pdf', $purchase) }}" class="btn btn-label-info" target="_blank">
                <i class="ti ti-file-type-pdf me-1"></i> Download PDF
            </a> -->
            @can('edit purchases')
                @if($purchase->status == 1)
                    <a href="{{ route('admin.purchases.edit', $purchase) }}" class="btn btn-label-info">
                        <i class="ti ti-pencil me-1"></i> Edit
                    </a>
                    <button class="btn btn-success"
                        data-common-confirm="{{ route('admin.purchases.status', $purchase) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Approve Purchase"
                        data-confirm-text="Are you sure you want to approve this purchase? Inventory will be updated."
                        data-confirm-btn="Yes, Approve"
                        data-confirm-btn-class="btn-success"
                        data-confirm-data='{"status":2}'>
                        <i class="ti ti-check me-1"></i> Approve
                    </button>
                    <button class="btn btn-label-danger"
                        data-common-confirm="{{ route('admin.purchases.status', $purchase) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Decline Purchase"
                        data-confirm-text="Are you sure you want to decline this purchase?"
                        data-confirm-btn="Yes, Decline"
                        data-confirm-btn-class="btn-danger"
                        data-confirm-data='{"status":3}'>
                        <i class="ti ti-x me-1"></i> Decline
                    </button>
                @endif
                @if(($purchase->payment_status ?? 1) == 1 && $purchase->status == 2)
                    <button class="btn btn-success"
                        data-common-confirm="{{ route('admin.purchases.update-payment-status', $purchase) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Mark as Paid"
                        data-confirm-text="Are you sure you want to mark this purchase as paid?"
                        data-confirm-btn="Yes, Mark as Paid"
                        data-confirm-btn-class="btn-success"
                        data-confirm-data='{"payment_status":2}'>
                        <i class="ti ti-currency-rupee me-1"></i> Mark as Paid
                    </button>
                @endif
            @endcan
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">

        {{-- ══════════════════ LEFT COLUMN ══════════════════ --}}
        <div class="col-lg-4 d-flex flex-column gap-4">

            {{-- Purchase Info --}}
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-receipt-2"></i></span>
                    <h6 class="mb-0 fw-semibold">Purchase Info</h6>
                </div>
                <div class="card-body py-1 px-3">

                    <div class="sale-info-row">
                        <span class="sale-info-label">Purchase No</span>
                        <code class="sale-info-value">{{ $purchase->invoice_no }}</code>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Status</span>
                        <span class="badge {{ $statusColors[$purchase->status] ?? 'bg-label-secondary' }}">
                            {{ $statusLabels[$purchase->status] ?? 'Pending' }}
                        </span>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Payment Status</span>
                        @php
                            $payColors = [1 => 'bg-label-warning', 2 => 'bg-label-info'];
                            $payLabels = [1 => 'Pending', 2 => 'Paid'];
                        @endphp
                        <span class="badge {{ $payColors[$purchase->payment_status ?? 1] ?? 'bg-label-secondary' }}">
                            {{ $payLabels[$purchase->payment_status ?? 1] ?? 'Pending' }}
                        </span>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Total Amount</span>
                        <span class="sale-info-value fw-bold text-primary">{{ format_price($purchase->total_amount) }}</span>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Date</span>
                        <span class="sale-info-value">{{ format_date($purchase->created_at) }}</span>
                    </div>

                </div>
            </div>

            {{-- Supplier --}}
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-user"></i></span>
                    <h6 class="mb-0 fw-semibold">Supplier</h6>
                </div>
                <div class="card-body py-1 px-3">
                    <div class="sale-info-row">
                        <span class="sale-info-label">Name</span>
                        <span class="sale-info-value">{{ $purchase->supplier->name ?? '-' }}</span>
                    </div>
                    @if($purchase->supplier?->phone)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Phone</span>
                        <span class="sale-info-value">{{ $purchase->supplier->phone }}</span>
                    </div>
                    @endif
                    @if($purchase->supplier?->address)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Address</span>
                        <span class="sale-info-value" style="max-width:65%;">{{ $purchase->supplier->address }}</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ══════════════════ RIGHT COLUMN ══════════════════ --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-shopping-bag"></i></span>
                    <h6 class="mb-0 fw-semibold">Purchase Items</h6>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:4%">#</th>
                                <th>Product</th>
                                <th class="text-end">Purchase Price</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Total</th>
                                <th>Allocations</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $index => $item)
                                @php
                                    $displayName = $item->product->name ?? '-';
                                    if ($item->variant) {
                                        $v = $item->variant;
                                        if ($v->attributeValue) {
                                            $displayName .= ' (' . ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '') . ')';
                                        }
                                    }

                                    if ($isRestricted && $locationId) {
                                        $myAllocation = $item->allocations->firstWhere('location_id', $locationId);
                                        $displayQty   = $myAllocation ? $myAllocation->quantity : 0;

                                        $displayTotal = $item->purchase_price * $displayQty;
                                        $displayAllocations = $myAllocation ? collect([$myAllocation]) : collect();
                                    } else {
                                        $displayQty         = $item->quantity;
                                        $displayTotal       = $item->total;
                                        $displayAllocations = $item->allocations;
                                    }
                                @endphp
                                <tr>
                                    <td class="text-muted small">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($item->product?->primaryImage)
                                                <img src="{{ $item->product->primaryImage->image_url }}" alt="{{ $displayName }}" class="rounded me-3 product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="rounded bg-label-secondary me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="ti ti-photo text-muted" style="font-size: 1.25rem;"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <span class="fw-semibold">{{ $displayName }}</span>
                                                @if($item->product?->barcode)
                                                    <br><small class="text-muted">{{ $item->product->barcode }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end text-nowrap small">{{ format_price($item->purchase_price) }}</td>
                                    <td class="text-end text-nowrap small">
                                        {{ $displayQty }}
                                        @if($item->product?->pair_product)
                                            <small class="text-muted">Pairs</small>
                                        @else
                                            <small class="text-muted">Pcs</small>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap fw-semibold" style="color:#B4771E;">{{ format_price($displayTotal) }}</td>
                                    <td class="small">
                                        @foreach($displayAllocations as $allocation)
                                            <span class="badge bg-label-info me-1 mb-1">
                                                {{ $allocation->location->name ?? '-' }}: {{ $allocation->quantity }}
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top:2px solid #B4771E;">
                                <td colspan="4" class="text-end fw-bold" style="font-size:1rem; color:#B4771E;">Grand Total</td>
                                <td class="text-end fw-bold" style="font-size:1rem; color:#B4771E;">
                                    @if($isRestricted)
                                        @php
                                            // Sum only this location's allocated items
                                            $locTotal = 0;
                                            foreach($purchase->items as $item) {
                                                $myAlloc = $item->allocations->firstWhere('location_id', $locationId);
                                                $locTotal += $item->purchase_price * ($myAlloc ? $myAlloc->quantity : 0);
                                            }
                                        @endphp
                                        {{ format_price($locTotal) }}
                                    @else
                                        {{ format_price($purchase->total_amount) }}
                                    @endif
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
