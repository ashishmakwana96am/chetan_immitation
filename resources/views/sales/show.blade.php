@extends('layouts.app')

@section('title', 'Sale ' . $order->order_no)

@section('content')
    @php
        $statusColors = [
            'pending' => 'bg-label-secondary',
            'approve' => 'bg-label-success',
            'decline' => 'bg-label-danger',
        ];
        $statusLabels = [
            'pending' => 'Pending',
            'approve' => 'Approve',
            'decline' => 'Decline',
        ];

        $paymentColors = [
            'pending' => 'bg-label-warning',
            'paid'    => 'bg-label-info',
        ];
        $paymentLabels = [
            'pending' => 'Pending',
            'paid'    => 'Paid',
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
                @if($order->status === 'pending')
                    <a href="{{ route('admin.sales.edit', $order) }}" class="btn btn-label-info">
                        <i class="ti ti-pencil me-1"></i> Edit
                    </a>
                @endif
                @if(($order->payment_status ?? 'pending') === 'pending' && $order->status === 'approve')
                    <button class="btn btn-success"
                        data-common-confirm="{{ route('admin.sales.status', $order) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Mark as Paid"
                        data-confirm-text="Mark this sale as paid?"
                        data-confirm-btn="Yes, Mark Paid"
                        data-confirm-btn-class="btn-success"
                        data-confirm-data='{"payment_status":"paid"}'>
                        <i class="ti ti-credit-card me-1"></i> Mark Paid
                    </button>
                @endif
                @if($order->status === 'pending')
                    <button class="btn btn-primary"
                        data-common-confirm="{{ route('admin.sales.status', $order) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Approve Sale"
                        data-confirm-text="Mark this sale as approved?"
                        data-confirm-btn="Yes, Approve"
                        data-confirm-btn-class="btn-primary"
                        data-confirm-data='{"status":"approve"}'>
                        <i class="ti ti-check me-1"></i> Approve
                    </button>
                    <button class="btn btn-label-danger"
                        data-common-confirm="{{ route('admin.sales.status', $order) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Decline Sale"
                        data-confirm-text="Decline this sale? Stock will be restored."
                        data-confirm-btn="Yes, Decline"
                        data-confirm-btn-class="btn-danger"
                        data-confirm-data='{"status":"decline"}'>
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
                        <span class="badge {{ $statusColors[$order->status] ?? 'bg-label-secondary' }}">{{ $statusLabels[$order->status] ?? ucfirst($order->status) }}</span>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Payment Status</p>
                        <span class="badge {{ $paymentColors[$order->payment_status] ?? 'bg-label-secondary' }}">{{ $paymentLabels[$order->payment_status] ?? ucfirst($order->payment_status) }}</span>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Payment Method</p>
                        <p class="mb-0">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</p>
                    </div>
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
                            @foreach($order->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <p class="fw-semibold mb-0">{{ $item->product->name ?? '-' }}</p>
                                        <small class="text-muted">{{ $item->product->sku ?? '' }}</small>
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
