@extends('layouts.app')

@section('title', 'Sale ' . $order->order_no)

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
        $paymentColors = [1 => 'bg-label-warning', 2 => 'bg-label-info'];
        $paymentLabels = [1 => 'Pending',          2 => 'Paid'];

        $isOnline          = ($order->source ?? 'POS') === 'ONLINE';
        $totalItemDiscount = $order->items->sum('discount_amount');
        // True subtotal = sum of (price × qty) for each item, before any discount
        $itemsGross        = $order->items->sum(fn($i) => (float)$i->price * (float)$i->quantity);
        $subtotal          = $itemsGross;
        // Coupon discount = actual deducted amount (gross - item discounts - final_amount)
        $couponDiscount = 0;
        $couponCode     = null;
        if ($order->coupon_id && $order->coupon) {
            $couponCode     = $order->coupon->code;
            $couponDiscount = max(0, round($subtotal - $totalItemDiscount - ((float)$order->final_amount - (float)$order->shipping_charge), 2));
        }

        $orderDiscountAmount = 0.0;
        if ($order->order_discount_value > 0) {
            $itemsTotal = $subtotal - $totalItemDiscount;
            if ($order->order_discount_type === 'flat') {
                $orderDiscountAmount = (float)$order->order_discount_value;
            } else if ($order->order_discount_type === 'percentage') {
                $orderDiscountAmount = $itemsTotal * ((float)$order->order_discount_value / 100);
            }
            $orderDiscountAmount = min($orderDiscountAmount, $itemsTotal);
        }

        $totalDiscount = $totalItemDiscount + $orderDiscountAmount + $couponDiscount;
    @endphp

    {{-- ── Page header ────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Sale <code>{{ $order->order_no }}</code> @if($order->is_gst) <span class="badge bg-label-success ms-1" style="font-size: 0.8rem;">GST</span> @endif</h4>
            <small class="text-muted">{{ format_date($order->created_at) }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">

            @can('download sales')
                @if($isOnline)
                    <a href="{{ route('admin.sales.pdf', [$order, 'auto_print' => 1]) }}" class="btn btn-label-primary" target="_blank">
                        <i class="ti ti-printer me-1"></i> Invoice
                    </a>
                    <a href="{{ route('admin.sales.label', [$order, 'auto_print' => 1]) }}" class="btn btn-label-success" target="_blank">
                        <i class="ti ti-printer me-1"></i> Print Label
                    </a>
                @else
                    <a href="{{ route('admin.sales.thermal', [$order, 'auto_print' => 1]) }}" class="btn btn-label-primary" target="_blank">
                        <i class="ti ti-printer me-1"></i> Invoice
                    </a>
                    @if($order->is_gst)
                        <a href="{{ route('admin.sales.tax-invoice', [$order, 'auto_print' => 1]) }}" class="btn btn-label-info" target="_blank">
                            <i class="ti ti-file-text me-1"></i> Tax Invoice
                        </a>
                    @endif
                @endif
            @endcan

            @can('edit sales')
                @if($order->status == 1 && !$isOnline)
                    <a href="{{ route('admin.sales.edit', $order) }}" class="btn btn-label-warning">
                        <i class="ti ti-pencil me-1"></i> Edit
                    </a>
                @endif
            @endcan

            @can('edit sales payment status')
                @if(($order->payment_status ?? 1) == 1)
                    <button class="btn btn-success"
                        data-common-confirm="{{ route('admin.sales.status', $order) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Mark as Paid"
                        data-confirm-text="Are you sure you want to mark this sale as paid?"
                        data-confirm-btn="Yes, Mark as Paid"
                        data-confirm-btn-class="btn-success"
                        data-confirm-data='{"payment_status":2}'>
                        <i class="ti ti-currency-rupee me-1"></i> Mark as Paid
                    </button>
                @endif
            @endcan

            @can('edit sales status')
                @php
                    $cs  = (int)$order->status;
                    $isOnline = ($order->source ?? 'POS') === 'ONLINE';
                    
                    if (!$isOnline) {
                        // POS: Only Pending and Approve are possible, and if status is already Approve (2), disable dropdown
                        $dis = ($cs >= 2) ? 'disabled' : '';
                        $o1  = ($cs === 1) ? '' : 'disabled';
                        $o2  = ($cs === 2) ? '' : (($cs === 1) ? '' : 'disabled');
                    } else {
                        // Online: Standard sequential flow
                        $dis = in_array($cs, [5, 6]) ? 'disabled' : '';
                        $o1  = ($cs === 1) ? '' : 'disabled';
                        $o2  = ($cs === 2) ? '' : ((!in_array($cs, [1, 2])) ? 'disabled' : '');
                        $o3  = ($cs === 3) ? '' : ((!in_array($cs, [2, 3])) ? 'disabled' : '');
                        $o4  = ($cs === 4) ? '' : ((!in_array($cs, [3, 4])) ? 'disabled' : '');
                        $o5  = ($cs === 5) ? '' : ((!in_array($cs, [4, 5])) ? 'disabled' : '');
                        $o6  = ($cs === 6) ? '' : ((in_array($cs, [5, 6]))  ? 'disabled' : '');
                    }
                @endphp
                <select id="change-sale-status" class="form-select no-select2" data-current="{{ $order->status }}" {{ $dis }} autocomplete="off" style="min-width:160px;width:auto;">
                    @if(!$isOnline)
                        <option value="1" {{ $order->status==1?'selected':'' }} {{ $o1 }}>Pending</option>
                        <option value="2" {{ $order->status==2?'selected':'' }} {{ $o2 }}>Approve</option>
                    @else
                        <option value="1" {{ $order->status==1?'selected':'' }} {{ $o1 }}>Pending</option>
                        <option value="2" {{ $order->status==2?'selected':'' }} {{ $o2 }}>Approve</option>
                        <option value="3" {{ $order->status==3?'selected':'' }} {{ $o3 }}>Shipped</option>
                        <option value="4" {{ $order->status==4?'selected':'' }} {{ $o4 }}>Out for delivery</option>
                        <option value="5" {{ $order->status==5?'selected':'' }} {{ $o5 }}>Delivered</option>
                        <option value="6" {{ $order->status==6?'selected':'' }} {{ $o6 }}>Cancelled</option>
                    @endif
                </select>
            @endcan

            <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>

        </div>

        {{-- Cancellation reason is now captured via SweetAlert popup --}}
        @can('edit sales status')
        <div id="cancel-reason-wrap" style="display:none !important;"></div>
        @endcan
    </div>

    @if($order->cancellationRequest && $order->cancellationRequest->status === 'pending')
    <div class="alert alert-danger d-flex flex-column gap-3 mb-4" role="alert" id="cancellation-request-alert">
        <div class="d-flex align-items-center">
            <i class="ti ti-alert-triangle me-2 fs-4 text-danger"></i>
            <div>
                <h5 class="alert-heading mb-1 text-danger">Cancellation Request Pending</h5>
                <span>Customer requested to cancel this order. Reason: <strong>{{ $order->cancellationRequest->cancellation_reason }}</strong></span>
            </div>
        </div>
        @can('edit sales status')
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm approve-cancel-btn" data-url="{{ route('admin.sales.cancellation.approve', $order) }}">
                <i class="ti ti-check me-1"></i> Approve & Refund
            </button>
            <button class="btn btn-danger btn-sm reject-cancel-btn" data-url="{{ route('admin.sales.cancellation.reject', $order) }}">
                <i class="ti ti-x me-1"></i> Reject
            </button>
        </div>
        @endcan
    </div>
    @endif

    <div class="row g-4">

        {{-- ══════════════════ LEFT COLUMN ══════════════════ --}}
        <div class="col-lg-4 d-flex flex-column gap-4">

            {{-- Sale Info --}}
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-receipt-2"></i></span>
                    <h6 class="mb-0 fw-semibold">Sale Info</h6>
                </div>
                <div class="card-body py-1 px-3">

                    <div class="sale-info-row">
                        <span class="sale-info-label">Sale No</span>
                        <code class="sale-info-value">{{ $order->order_no }}</code>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Source</span>
                        <span class="sale-info-value">
                            @if($isOnline)
                                <span class="badge bg-label-success">ONLINE</span>
                            @else
                                <span class="badge bg-label-primary">POS</span>
                            @endif
                        </span>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Status</span>
                        <span class="badge {{ $statusColors[$order->status] ?? 'bg-label-secondary' }}">
                            {{ $statusLabels[$order->status] ?? 'Pending' }}
                        </span>
                    </div>

                    @if($order->status == 6 && $order->cancellation_reason)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Cancel Reason</span>
                        <span class="sale-info-value text-danger" style="max-width:65%;">{{ $order->cancellation_reason }}</span>
                    </div>
                    @endif

                    @if($order->cancellationRequest && $order->cancellationRequest->status === 'approved')
                    <div class="sale-info-row">
                        <span class="sale-info-label">Refunded Amount</span>
                        <span class="sale-info-value text-success">₹{{ number_format($order->cancellationRequest->refund_amount, 2) }}</span>
                    </div>
                    @if($order->cancellationRequest->refund_gateway_id)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Refund ID</span>
                        <span class="sale-info-value text-muted">{{ $order->cancellationRequest->refund_gateway_id }}</span>
                    </div>
                    @endif
                    @endif

                    @if($order->shipped_client_url)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Shipping URL</span>
                        <span class="sale-info-value text-truncate" style="max-width:65%;">
                            <a href="{{ $order->shipped_client_url }}" target="_blank" class="text-primary">{{ $order->shipped_client_url }}</a>
                        </span>
                    </div>
                    @endif

                    @if($order->tracking_id)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Tracking ID</span>
                        <span class="sale-info-value">{{ $order->tracking_id }}</span>
                    </div>
                    @endif

                    <div class="sale-info-row">
                        <span class="sale-info-label">Payment</span>
                        <span class="badge {{ $paymentColors[$order->payment_status ?? 1] ?? 'bg-label-secondary' }}">
                            {{ $paymentLabels[$order->payment_status ?? 1] ?? 'Pending' }}
                        </span>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Method</span>
                        <span class="sale-info-value">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</span>
                    </div>

                    @if($couponCode)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Coupon</span>
                        <span class="sale-info-value">
                            <code>{{ $couponCode }}</code>
                            @if($couponDiscount > 0)
                                <small class="text-muted d-block">-{{ format_price($couponDiscount) }}</small>
                            @endif
                        </span>
                    </div>
                    @endif

                    <div class="sale-info-row">
                        <span class="sale-info-label">Date</span>
                        <span class="sale-info-value">{{ format_date($order->created_at) }}</span>
                    </div>

                    @if($order->payment?->gateway_order_id)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Razorpay Order ID</span>
                        <div class="sale-info-value d-flex gap-2 align-items-center">
                            <code id="razorpayOrderId" style="cursor: pointer;">{{ $order->payment->gateway_order_id }}</code>
                            <i class="ti ti-copy text-primary"
                            style="cursor: pointer;"
                            onclick="copyToClipboard('razorpayOrderId')"
                            title="Copy"></i>
                        </div>
                    </div>
                    @endif

                    @if($order->payment?->gateway_payment_id)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Razorpay Payment ID</span>
                        <div class="sale-info-value d-flex gap-2 align-items-center">
                            <code id="razorpayPaymentId" style="cursor: pointer;">{{ $order->payment->gateway_payment_id }}</code>
                            <i class="ti ti-copy text-primary"
                            style="cursor: pointer;"
                            onclick="copyToClipboard('razorpayPaymentId')"
                            title="Copy"></i>
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Customer --}}
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-user"></i></span>
                    <h6 class="mb-0 fw-semibold">Customer</h6>
                </div>
                <div class="card-body py-1 px-3">
                    <div class="sale-info-row">
                        <span class="sale-info-label">Name</span>
                        <span class="sale-info-value">{{ $order->customer->name ?? 'Walk-in Customer' }}</span>
                    </div>
                    @if($order->customer?->phone)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Phone</span>
                        <span class="sale-info-value">{{ $order->customer->phone }}</span>
                    </div>
                    @endif
                    @if($order->customer?->email)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Email</span>
                        <span class="sale-info-value">{{ $order->customer->email }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Delivery Address — online only --}}
            @if($isOnline && $order->customerAddress)
                @php $addr = $order->customerAddress; @endphp
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="card-title-icon"><i class="ti ti-map-pin"></i></span>
                        <h6 class="mb-0 fw-semibold">Delivery Address</h6>
                    </div>
                    <div class="card-body py-1 px-3">
                        <div class="sale-info-row">
                            <span class="sale-info-label">Name</span>
                            <span class="sale-info-value">{{ $addr->name }}</span>
                        </div>
                        <div class="sale-info-row">
                            <span class="sale-info-label">Phone</span>
                            <span class="sale-info-value">{{ $addr->phone }}</span>
                        </div>
                        @if($addr->alternate_phone)
                        <div class="sale-info-row">
                            <span class="sale-info-label">Alt. Phone</span>
                            <span class="sale-info-value">{{ $addr->alternate_phone }}</span>
                        </div>
                        @endif
                        <div class="sale-info-row">
                            <span class="sale-info-label">Address</span>
                            <span class="sale-info-value" style="max-width:65%;">{{ $addr->address }}</span>
                        </div>
                        <div class="sale-info-row">
                            <span class="sale-info-label">City</span>
                            <span class="sale-info-value">{{ $addr->city }}</span>
                        </div>
                        <div class="sale-info-row">
                            <span class="sale-info-label">State</span>
                            <span class="sale-info-value">{{ $addr->state }}</span>
                        </div>
                        @if($addr->pincode)
                        <div class="sale-info-row">
                            <span class="sale-info-label">Pincode</span>
                            <span class="sale-info-value">{{ $addr->pincode }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>

        {{-- ══════════════════ RIGHT COLUMN ══════════════════ --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-shopping-bag"></i></span>
                    <h6 class="mb-0 fw-semibold">Sale Items</h6>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:4%">#</th>
                                <th>Product</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $index => $item)
                                @php
                                    $displayName = $item->product->name ?? '-';
                                    if ($item->variant) {
                                        $v = $item->variant;
                                        if ($v->attributeValue) {
                                            $displayName .= ' (' . ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '') . ')';
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td class="text-muted small">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $item->product?->primary_image_url ?? asset('website/assets/images/no-image.svg') }}" alt="{{ $displayName }}" class="rounded me-3 product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <span class="fw-semibold">{{ $displayName }}</span>
                                                @if($item->product?->barcode)
                                                    <br><small class="text-muted">{{ $item->product->barcode }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end text-nowrap small">{{ format_price($item->price) }}</td>
                                    <td class="text-end text-nowrap small">
                                        {{ $item->quantity }}
                                        @php
                                            $szVal = $item->custom_size_value ?: ($item->product?->pair_product ? (collect($item->product?->custom_sizes ?? [])->pluck('size')->max() ?: 2) : null);
                                        @endphp
                                        @if($szVal)
                                            <small class="text-muted">&times; {{ rtrim(rtrim(number_format((float) $szVal, 2), '0'), '.') }}pcs</small>
                                        @else
                                            <small class="text-muted">Pcs</small>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap small">
                                        @if($item->discount_amount > 0)
                                             @if($item->discount_type === 'percentage')
                                                 {{ number_format($item->discount_value, 2) }}%
                                             @else
                                                 -{{ format_price($item->discount_amount) }}
                                             @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap fw-semibold" style="color:#B4771E;">{{ format_price($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="5" class="text-end tfoot-label">Total Items</td>
                                <td class="text-end tfoot-amount">{{ $order->items->sum('quantity') }}</td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="5" class="text-end tfoot-label">Subtotal</td>
                                <td class="text-end tfoot-amount">{{ format_price($subtotal) }}</td>
                            </tr>
                            @if($totalDiscount > 0)
                            <tr>
                                <td colspan="5" class="text-end tfoot-label text-danger">Discount</td>
                                <td class="text-end tfoot-amount text-danger">-{{ format_price($totalDiscount) }}</td>
                            </tr>
                            @elseif($order->coupon_id && $order->coupon)
                            <tr>
                                <td colspan="5" class="text-end tfoot-label" style="color:#2e7d32;">
                                    Coupon Applied &nbsp;<code style="color:#2e7d32;">{{ $order->coupon->code }}</code>
                                </td>
                                <td class="text-end tfoot-amount" style="color:#2e7d32;">-</td>
                            </tr>
                            @endif
                            @if($order->is_gst && $order->tax_amount > 0)
                                @php
                                     $isPos = ($order->source ?? 'POS') === 'POS';
                                     $buyerState = 'gujarat';
                                     if (!$isPos && $order->customerAddress) {
                                         $buyerState = strtolower(trim($order->customerAddress->state));
                                     }
                                     $storeState = strtolower(trim(\App\Models\Setting::getValue('store_state', 'gujarat')));
                                     $gstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);
                                     $taxAmount = (float) $order->tax_amount;
                                @endphp
                                @if($isPos || $buyerState === '' || $buyerState === $storeState)
                                    @php
                                        $halfRate = $gstRate / 2;
                                        $halfTax = $taxAmount / 2;
                                    @endphp
                                    <tr>
                                        <td colspan="5" class="text-end tfoot-label">CGST ({{ $halfRate }}%)</td>
                                        <td class="text-end tfoot-amount">{{ format_price($halfTax) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end tfoot-label">SGST ({{ $halfRate }}%)</td>
                                        <td class="text-end tfoot-amount">{{ format_price($halfTax) }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="5" class="text-end tfoot-label">IGST ({{ $gstRate }}%)</td>
                                        <td class="text-end tfoot-amount">{{ format_price($taxAmount) }}</td>
                                    </tr>
                                @endif
                            @endif
                            @if(($order->source ?? 'POS') !== 'POS')
                            <tr>
                                <td colspan="5" class="text-end tfoot-label">Shipping</td>
                                <td class="text-end tfoot-amount">{{ $order->shipping_charge > 0 ? format_price($order->shipping_charge) : 'Free' }}</td>
                            </tr>
                            @endif
                            <tr style="border-top:2px solid #B4771E;">
                                <td colspan="5" class="text-end fw-bold" style="font-size:1rem; color:#B4771E;">Final Amount</td>
                                <td class="text-end fw-bold" style="font-size:1rem; color:#B4771E;">{{ format_price($order->final_amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('page-js')
<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent.trim();

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            toastr.success('Copied to clipboard');
        }).catch(() => {
            fallbackCopyTextToClipboard(text);
        });
    } else {
        fallbackCopyTextToClipboard(text);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        toastr.success('Copied to clipboard');
    } catch (err) {
        toastr.error('Failed to copy');
    }

    document.body.removeChild(textArea);
}

$(document).ready(function () {

    $('#change-sale-status').on('change', function () {
        const status  = $(this).val();
        const current = $(this).data('current');
        const url     = "{{ route('admin.sales.status', $order) }}";

        if (status == '6') {
            Swal.fire({
                title: 'Cancel Order',
                html: `
                    <div class="mb-3 text-start">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="swal-cancel-reason" class="form-label fw-semibold mb-0">Cancellation Reason <span class="text-danger">*</span></label>
                            <small class="text-muted" id="swal-char-counter">0/500</small>
                        </div>
                        <textarea id="swal-cancel-reason" class="form-control" rows="3" maxlength="500" placeholder="Enter the reason for cancellation..."></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Yes, Cancel Order',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-danger me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false,
                didOpen: () => {
                    const reasonInput = document.getElementById('swal-cancel-reason');
                    const charCounter = document.getElementById('swal-char-counter');
                    reasonInput.addEventListener('input', () => {
                        charCounter.textContent = `${reasonInput.value.length}/500`;
                    });
                },
                preConfirm: () => {
                    const reason = document.getElementById('swal-cancel-reason').value.trim();
                    if (!reason) {
                        Swal.showValidationMessage('Please enter a cancellation reason.');
                        return false;
                    }
                    return { reason: reason };
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    window.showAjaxLoader();
                    $.ajax({
                        url: url,
                        type: 'PATCH',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: 6,
                            cancellation_reason: result.value.reason
                        },
                        success: function (res) {
                            window.hideAjaxLoader();
                            if (res.status === 'success') {
                                toastr.success(res.message);
                                if (res.pending_count !== undefined) {
                                    const badge = $('.pending-sales-counter-badge');
                                    if (badge.length > 0) {
                                        badge.text(res.pending_count);
                                        if (res.pending_count > 0) {
                                            badge.attr('style', 'display: inline-block !important;');
                                        } else {
                                            badge.attr('style', 'display: none !important;');
                                        }
                                    }
                                }
                                setTimeout(() => location.reload(), 800);
                            }
                        },
                        error: function (xhr) {
                            window.hideAjaxLoader();
                            const msg = xhr.responseJSON?.message || 'Something went wrong.';
                            toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                            $('#change-sale-status').val(current);
                        }
                    });
                } else {
                    $('#change-sale-status').val(current);
                }
            });
            return;
        }

        if (status == '3') {
            Swal.fire({
                title: 'Enter Shipping Details',
                html:
                    '<div class="mb-3 text-start"><label class="form-label small fw-semibold">Shipping Client URL</label><input id="swal-shipped-url" class="form-control" placeholder="https://tracking-url.com"></div>' +
                    '<div class="mb-3 text-start"><label class="form-label small fw-semibold">Tracking ID</label><input id="swal-tracking-id" class="form-control" placeholder="Tracking ID / No."></div>',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Submit & Ship',
                customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
                buttonsStyling: false,
                preConfirm: () => {
                    const urlVal = document.getElementById('swal-shipped-url').value.trim();
                    const trackingVal = document.getElementById('swal-tracking-id').value.trim();
                    if (!urlVal) {
                        Swal.showValidationMessage('Please enter Shipping Client URL');
                        return false;
                    }
                    if (!trackingVal) {
                        Swal.showValidationMessage('Please enter Tracking ID');
                        return false;
                    }
                    return { shipped_client_url: urlVal, tracking_id: trackingVal };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.showAjaxLoader();
                    $.ajax({
                        url: url, type: 'PATCH',
                        data: { 
                            _token: '{{ csrf_token() }}', 
                            status: status, 
                            shipped_client_url: result.value.shipped_client_url, 
                            tracking_id: result.value.tracking_id 
                        },
                        success: function (res) {
                            window.hideAjaxLoader();
                            if (res.status === 'success') {
                                toastr.success(res.message);
                                if (res.pending_count !== undefined) {
                                    const badge = $('.pending-sales-counter-badge');
                                    if (badge.length > 0) {
                                        badge.text(res.pending_count);
                                        if (res.pending_count > 0) {
                                            badge.attr('style', 'display: inline-block !important;');
                                        } else {
                                            badge.attr('style', 'display: none !important;');
                                        }
                                    }
                                }
                                setTimeout(() => location.reload(), 800);
                            }
                        },
                        error: function (xhr) {
                            window.hideAjaxLoader();
                            const msg = xhr.responseJSON?.message || 'Something went wrong.';
                            toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                            $('#change-sale-status').val(current);
                        }
                    });
                } else {
                    $('#change-sale-status').val(current);
                }
            });
            return;
        }

        Swal.fire({
            title: 'Update Sale Status',
            text: 'Are you sure you want to update the status of this sale?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Update',
            customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.showAjaxLoader();
                $.ajax({
                    url: url, type: 'PATCH',
                    data: { _token: '{{ csrf_token() }}', status: status },
                    success: function (res) {
                        window.hideAjaxLoader();
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            if (res.pending_count !== undefined) {
                                const badge = $('.pending-sales-counter-badge');
                                if (badge.length > 0) {
                                    badge.text(res.pending_count);
                                    if (res.pending_count > 0) {
                                        badge.attr('style', 'display: inline-block !important;');
                                    } else {
                                        badge.attr('style', 'display: none !important;');
                                    }
                                }
                            }
                            setTimeout(() => location.reload(), 800);
                        }
                    },
                    error: function (xhr) {
                        window.hideAjaxLoader();
                        const msg = xhr.responseJSON?.message || 'Something went wrong.';
                        toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                        $('#change-sale-status').val(current);
                    }
                });
            } else {
                $('#change-sale-status').val(current);
            }
        });
    });


    $(document).on('click', '.approve-cancel-btn', function (e) {
        e.preventDefault();
        const url = $(this).data('url');

        Swal.fire({
            title: 'Approve Cancellation?',
            text: 'This will cancel the order, restore inventory stock, and automatically process the Razorpay refund if paid online.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve & Refund',
            customClass: { confirmButton: 'btn btn-success me-3', cancelButton: 'btn btn-label-secondary' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.showAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        window.hideAjaxLoader();
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(res.message || 'Failed to approve cancellation.');
                        }
                    },
                    error: function (xhr) {
                        window.hideAjaxLoader();
                        const msg = xhr.responseJSON?.message || 'Something went wrong.';
                        toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                    }
                });
            }
        });
    });

    $(document).on('click', '.reject-cancel-btn', function (e) {
        e.preventDefault();
        const url = $(this).data('url');

        Swal.fire({
            title: 'Reject Cancellation?',
            text: 'This will reject the customer\'s cancellation request and keep the order active.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Reject Request',
            customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.showAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        window.hideAjaxLoader();
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(res.message || 'Failed to reject cancellation.');
                        }
                    },
                    error: function (xhr) {
                        window.hideAjaxLoader();
                        const msg = xhr.responseJSON?.message || 'Something went wrong.';
                        toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                    }
                });
            }
        });
    });

    $('#cancel-decline-btn').on('click', function () {
        $('#change-sale-status').val($('#change-sale-status').data('current'));
        $('#cancel-reason-input').val('');
        $('#cancel-reason-wrap').hide();
    });
});
</script>
@endsection
