@extends('layouts.app')

@section('title', 'Invoice ' . $purchase->invoice_no)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-semibold mb-0">Invoice <code>{{ $purchase->invoice_no }}</code></h4>
            @php
                $statusColors = ['pending' => 'bg-label-secondary', 'approve' => 'bg-label-success', 'decline' => 'bg-label-danger'];
                $statusLabels = ['pending' => 'Pending', 'approve' => 'Approve', 'decline' => 'Decline'];
            @endphp
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.purchases.pdf', $purchase) }}" class="btn btn-label-info" target="_blank">
                <i class="ti ti-file-type-pdf me-1"></i> Download PDF
            </a>
            @can('edit purchases')
                @if($purchase->status === 'pending')
                    <a href="{{ route('admin.purchases.edit', $purchase) }}" class="btn btn-label-info">
                        <i class="ti ti-pencil me-1"></i> Edit
                    </a>
                    <button class="btn btn-success"
                        data-common-confirm="{{ route('admin.purchases.status', $purchase) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Approve Invoice"
                        data-confirm-text="Are you sure you want to approve this invoice? Inventory will be updated."
                        data-confirm-btn="Yes, Approve"
                        data-confirm-btn-class="btn-success"
                        data-confirm-data='{"status":"approve"}'>
                        <i class="ti ti-check me-1"></i> Approve
                    </button>
                    <button class="btn btn-label-danger"
                        data-common-confirm="{{ route('admin.purchases.status', $purchase) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Decline Invoice"
                        data-confirm-text="Are you sure you want to decline this invoice?"
                        data-confirm-btn="Yes, Decline"
                        data-confirm-btn-class="btn-danger"
                        data-confirm-data='{"status":"decline"}'>
                        <i class="ti ti-x me-1"></i> Decline
                    </button>
                @endif
                @if(($purchase->payment_status ?? 'pending') === 'pending' && $purchase->status === 'approve')
                    <button class="btn btn-success"
                        data-common-confirm="{{ route('admin.purchases.update-payment-status', $purchase) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Mark as Paid"
                        data-confirm-text="Are you sure you want to mark this invoice as paid?"
                        data-confirm-btn="Yes, Mark as Paid"
                        data-confirm-btn-class="btn-success"
                        data-confirm-data='{"payment_status":"paid"}'>
                        <i class="ti ti-currency-dollar me-1"></i> Mark as Paid
                    </button>
                @endif
            @endcan
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- Invoice Info -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Invoice Info</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Invoice No</p>
                        <p class="fw-semibold mb-0"><code>{{ $purchase->invoice_no }}</code></p>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Status</p>
                        <span class="badge {{ $statusColors[$purchase->status] ?? 'bg-label-secondary' }}">{{ $statusLabels[$purchase->status] ?? ucfirst($purchase->status) }}</span>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Payment Status</p>
                        @php
                            $payColors = ['pending' => 'bg-label-warning', 'paid' => 'bg-label-info'];
                        @endphp
                        <span class="badge {{ $payColors[$purchase->payment_status] ?? 'bg-label-secondary' }}">{{ ucfirst($purchase->payment_status ?? 'pending') }}</span>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Supplier</p>
                        <p class="fw-semibold mb-0">{{ $purchase->supplier->name ?? '-' }}</p>
                        @if($purchase->supplier?->phone)
                            <small class="text-muted">{{ $purchase->supplier->phone }}</small>
                        @endif
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Total Amount</p>
                        <p class="fw-bold text-primary mb-0">{{ format_price($purchase->total_amount) }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Created By</p>
                        <p class="mb-0">{{ $purchase->createdBy->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Date</p>
                        <p class="mb-0">{{ format_date($purchase->created_at) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Purchase Items</h5></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-end">Purchase Price</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Total</th>
                                <th>Allocations</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <p class="fw-semibold mb-0">{{ $item->product->name ?? '-' }}</p>
                                        <small class="text-muted">{{ $item->product->sku ?? '' }}</small>
                                    </td>
                                    <td class="text-end text-nowrap">{{ format_price($item->purchase_price) }}</td>
                                    <td class="text-end text-nowrap">{{ $item->quantity }}</td>
                                    <td class="text-end text-nowrap fw-semibold text-primary">{{ format_price($item->total) }}</td>
                                    <td>
                                        @foreach($item->allocations as $allocation)
                                            <span class="badge bg-label-info me-1 mb-1">
                                                {{ $allocation->location->name ?? '-' }}: {{ $allocation->quantity }}
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">Grand Total</td>
                                <td class="text-end text-nowrap fw-bold text-primary">{{ format_price($purchase->total_amount) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
