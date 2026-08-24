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
    $paymentColors = [1 => 'bg-label-warning', 2 => 'bg-label-info', 3 => 'bg-label-primary'];
    $paymentLabels = [1 => 'Pending',          2 => 'Paid',         3 => 'Partially Paid'];

    $isOnline          = ($order->source ?? 'POS') === 'ONLINE';
    $totalItemDiscount = $order->items->sum('discount_amount');
    
    $itemsGross        = $order->items->sum(fn($i) => ((float)($i->mrp ?? $i->price)) * (float)$i->quantity);
    $subtotal          = $itemsGross;
    
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

    $totalDiscountOnMrp = max(0, round($subtotal - (float)$order->final_amount, 2));
    $totalDiscount = max($totalDiscountOnMrp, round($totalItemDiscount + $orderDiscountAmount + $couponDiscount, 2));

    $showPaidCash = (float)($order->paid_cash_amount ?? 0);
    $showPaidOnline = (float)($order->paid_online_amount ?? 0);
    $showPaidTotal = $showPaidCash + $showPaidOnline;
    $showPayments = $order->salePayments()->get();
@endphp

<div class="row g-4">

    {{-- ══════════════════ LEFT COLUMN ══════════════════ --}}
    <div class="{{ $layoutLeftClass ?? 'col-lg-4' }} d-flex flex-column gap-4">

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
                    <span class="sale-info-value">
                        @if($order->payment_method === 'online_cash')
                            Cash: {{ format_price($order->paid_cash_amount) }}, Online: {{ format_price($order->paid_online_amount) }}
                        @else
                            {{ $order->payment_method ? ucwords(str_replace('_', ' ', $order->payment_method)) : '-' }}
                        @endif
                    </span>
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

            </div>
        </div>

        @if(in_array(($order->payment_status ?? 1), [2, 3]) && $showPaidTotal > 0)
        {{-- Payment History --}}
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="card-title-icon"><i class="ti ti-history"></i></span>
                <h6 class="mb-0 fw-semibold">Payment History</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($showPayments->isNotEmpty())
                            @foreach($showPayments as $p)
                                @php
                                    $pMethodParts = [];
                                    $pc = (float)($p->cash_amount ?? 0);
                                    $po = (float)($p->online_amount ?? 0);
                                    if ($pc > 0) $pMethodParts[] = 'Cash: ' . format_price($pc);
                                    if ($po > 0) $pMethodParts[] = 'Online: ' . format_price($po);
                                    $pMethodStr = count($pMethodParts) > 0 ? ' (' . implode(' + ', $pMethodParts) . ')' : '';
                                @endphp
                                <tr>
                                    <td class="small text-nowrap">{{ format_date($p->created_at) }}</td>
                                    <td class="text-end small fw-semibold text-success">{{ format_price($p->amount) }}{{ $pMethodStr }}</td>
                                </tr>
                            @endforeach
                        @else
                            @php
                                $showMethodParts = [];
                                if ($showPaidCash > 0) $showMethodParts[] = 'Cash: ' . format_price($showPaidCash);
                                if ($showPaidOnline > 0) $showMethodParts[] = 'Online: ' . format_price($showPaidOnline);
                                $showMethodStr = count($showMethodParts) > 0 ? ' (' . implode(' + ', $showMethodParts) . ')' : '';
                            @endphp
                            <tr>
                                <td class="small text-nowrap">{{ $order->updated_at ? format_date($order->updated_at) : format_date($order->created_at) }}</td>
                                <td class="text-end small fw-semibold text-success">{{ format_price($showPaidTotal) }}{{ $showMethodStr }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif

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
    <div class="{{ $layoutRightClass ?? 'col-lg-8' }}">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="card-title-icon"><i class="ti ti-shopping-cart"></i></span>
                <h6 class="mb-0 fw-semibold">Sale Items</h6>
            </div>
            <div class="card-datatable table-responsive sale-items-wrap p-3">
                <table class="table mb-0 sale-items-table" id="{{ $itemsTableId ?? 'orderItemsTable' }}">
                    <thead class="table-light">
                        <tr>
                            <th class="col-index">#</th>
                            <th class="col-product">Product</th>
                            <th class="text-end col-mrp" style="width: 110px;">MRP</th>
                            <th class="text-end col-price d-none">Price</th>
                            <th class="text-end col-qty">Qty</th>
                            <th class="text-end col-discount">Discount</th>
                            <th class="text-end col-total">Total</th>
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
                                $itemMrp = $item->mrp ?? ($item->variant?->mrp ?? ($item->product?->mrp ?? 0));
                                if ($item->product?->pair_product && $item->custom_size_value && !$item->mrp) {
                                    $customSizes = collect($item->product->custom_sizes ?? []);
                                    if ($item->product_variant_id) {
                                        $vSizes = collect($item->product->variant_custom_sizes ?? [])->where('product_variant_id', $item->product_variant_id);
                                        if ($vSizes->isNotEmpty()) {
                                            $customSizes = $vSizes;
                                        }
                                    }
                                    $matchedSize = $customSizes->firstWhere('size', (string)$item->custom_size_value);
                                    if ($matchedSize && isset($matchedSize['mrp'])) {
                                        $itemMrp = (float) $matchedSize['mrp'];
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $item->product?->primary_image_url ?? asset('website/assets/images/no-image.svg') }}" alt="{{ $displayName }}" class="rounded me-3 product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="min-w-0">
                                            <span class="fw-semibold product-name">{{ $displayName }}</span>
                                            @if($item->product?->barcode)
                                                <small class="text-muted product-code">{{ $item->product->barcode }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end text-nowrap small fw-semibold text-heading">{{ $itemMrp > 0 ? format_price($itemMrp) : '-' }}</td>
                                <td class="text-end text-nowrap small d-none">{{ format_price($item->price) }}</td>
                                <td class="text-end text-nowrap small">
                                    <span class="text-nowrap">{{ $item->quantity }}</span>
                                    @php
                                        $szVal = $item->custom_size_value ?: ($item->product?->pair_product ? (collect($item->product?->custom_sizes ?? [])->pluck('size')->max() ?: 2) : null);
                                    @endphp
                                    @if($szVal)
                                        <span class="small text-muted text-nowrap">&times; {{ rtrim(rtrim(number_format((float) $szVal, 2), '0'), '.') }}pcs</span>
                                    @else
                                        <span class="small text-muted text-nowrap">Pcs</span>
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
