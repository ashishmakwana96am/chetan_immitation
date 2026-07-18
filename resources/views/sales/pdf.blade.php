<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Invoice {{ $order->order_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #2d2d2d;
            background: #fff;
        }

        /* ─── Layout ──────────────────────────────── */
        .page {
            padding: 36px 40px 30px 40px;
        }

        /* ─── Header ──────────────────────────────── */
        .header-wrap {
            width: 100%;
            margin-bottom: 28px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .logo-cell {
            width: 180px;
        }
        .logo-cell img {
            max-width: 160px;
            max-height: 56px;
            object-fit: contain;
        }
        .brand-cell {
            padding-left: 14px;
            vertical-align: middle;
        }
        .brand-name {
            font-size: 18px;
            font-weight: bold;
            color: #B4771E;
            letter-spacing: 0.3px;
        }
        .brand-tagline {
            font-size: 9.5px;
            color: #888;
            margin-top: 2px;
        }
        .invoice-label-cell {
            text-align: right;
            vertical-align: middle;
        }
        .invoice-label {
            font-size: 24px;
            font-weight: bold;
            color: #B4771E;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .invoice-no {
            font-size: 12px;
            font-weight: bold;
            color: #444;
            margin-top: 4px;
        }

        /* ─── Gold divider ────────────────────────── */
        .divider {
            width: 100%;
            height: 2px;
            background: #B4771E;
            margin-bottom: 24px;
        }
        .divider-thin {
            width: 100%;
            height: 1px;
            background: #e8e0d2;
            margin: 18px 0;
        }

        /* ─── Status badge ────────────────────────── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-pending        { background: #f2f2f2;  color: #666;    border: 1px solid #ddd; }
        .badge-approve        { background: #e8f5e9;  color: #2e7d32; border: 1px solid #c8e6c9; }
        .badge-shipped        { background: #e3f2fd;  color: #1565c0; border: 1px solid #bbdefb; }
        .badge-outdelivery    { background: #fff8e1;  color: #f57f17; border: 1px solid #ffe082; }
        .badge-delivered      { background: #e8f5e9;  color: #2e7d32; border: 1px solid #c8e6c9; }
        .badge-decline        { background: #fce4ec;  color: #c62828; border: 1px solid #f8bbd0; }
        .badge-paid           { background: #e3f2fd;  color: #1565c0; border: 1px solid #bbdefb; }
        .badge-unpaid         { background: #fff8e1;  color: #f57f17; border: 1px solid #ffe082; }
        .badge-online         { background: #e8f5e9;  color: #2e7d32; border: 1px solid #c8e6c9; }
        .badge-pos            { background: #e3f2fd;  color: #1565c0; border: 1px solid #bbdefb; }

        /* ─── Info section ────────────────────────── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .info-table td {
            border: none;
            padding: 0;
            vertical-align: top;
            width: 33.33%;
        }
        .info-box {
            padding-right: 16px;
        }
        .info-box-mid {
            padding-left: 8px;
            padding-right: 8px;
        }
        .info-box-right {
            padding-left: 16px;
            text-align: right;
        }
        .info-section-title {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #B4771E;
            border-bottom: 1px solid #e8e0d2;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .info-row {
            margin-bottom: 5px;
            font-size: 10.5px;
            line-height: 1.5;
        }
        .info-label {
            color: #888;
            font-size: 9.5px;
        }
        .info-value {
            font-weight: bold;
            color: #2d2d2d;
        }

        /* ─── Items table ──────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .items-table thead tr {
            background: #B4771E;
        }
        .items-table thead th {
            padding: 8px 10px;
            color: #fff;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        .items-table thead th.text-right { text-align: right; }
        .items-table tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0ebe2;
            font-size: 10.5px;
            color: #2d2d2d;
            vertical-align: middle;
        }
        .items-table tbody tr:nth-child(even) td {
            background: #fdf9f4;
        }
        .items-table tbody td.text-right { text-align: right; }
        .items-table tfoot td {
            padding: 7px 10px;
            border: none;
            font-size: 10.5px;
        }
        .items-table tfoot td.text-right { text-align: right; }
        .items-table tfoot tr.subtotal-row td { border-top: 1px solid #e8e0d2; }
        .items-table tfoot tr.discount-row td { color: #c62828; }
        .items-table tfoot tr.coupon-row td   { color: #2e7d32; }
        .items-table tfoot tr.total-row td {
            border-top: 2px solid #B4771E;
            font-size: 13px;
            font-weight: bold;
            color: #B4771E;
            padding-top: 10px;
        }

        /* ─── Delivery address box ─────────────────── */
        .address-box {
            border: 1px solid #e8e0d2;
            border-radius: 4px;
            padding: 10px 14px;
            background: #fdf9f4;
            margin-bottom: 24px;
        }
        .address-box-title {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #B4771E;
            margin-bottom: 7px;
        }
        .address-row {
            font-size: 10.5px;
            margin-bottom: 3px;
            color: #2d2d2d;
            line-height: 1.5;
        }

        /* ─── Footer ───────────────────────────────── */
        .footer {
            margin-top: 32px;
            border-top: 1px solid #e8e0d2;
            padding-top: 12px;
            text-align: center;
        }
        .footer-text {
            font-size: 9px;
            color: #aaa;
        }
        .footer-thank {
            font-size: 10.5px;
            color: #B4771E;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* ─── Watermark strip ──────────────────────── */
        .strip-bar {
            width: 100%;
            height: 5px;
            background: #B4771E;
            margin-bottom: 0;
        }
    </style>
</head>
<body>

@php
    $statusLabels = [
        1 => 'Pending',
        2 => 'Approved',
        3 => 'Shipped',
        4 => 'Out for Delivery',
        5 => 'Delivered',
        6 => 'Cancelled',
    ];
    $statusBadge = [
        1 => 'badge-pending',
        2 => 'badge-approve',
        3 => 'badge-shipped',
        4 => 'badge-outdelivery',
        5 => 'badge-delivered',
        6 => 'badge-decline',
    ];
    $payLabels = [1 => 'Unpaid', 2 => 'Paid'];
    $payBadge  = [1 => 'badge-unpaid', 2 => 'badge-paid'];

    $isOnline          = ($order->source ?? 'POS') === 'ONLINE';
    $totalItemDiscount = $order->items->sum('discount_amount');
    // True subtotal = sum of (price × qty) before any discount
    $subtotal          = $order->items->sum(fn($i) => (float)$i->price * (float)$i->quantity);
    // Coupon discount = actual amount deducted by coupon
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

    // Prepare items with variant resolution
    $preparedItems    = collect();
    $groupedByProduct = $order->items->groupBy('product_id');
    foreach ($groupedByProduct as $productId => $siblings) {
        $siblings  = $siblings->sortBy('id')->values();
        $firstItem = $siblings->first();
        $product   = $firstItem->product ?? null;

        if ($product && $product->type === 'variable') {
            $hasNewVariantIdTracking = $siblings->contains(fn($i) => !is_null($i->product_variant_id));

            if ($hasNewVariantIdTracking) {
                $parentItem = $siblings->first(fn($i) => is_null($i->product_variant_id));
                $variantItems = $siblings->filter(fn($i) => !is_null($i->product_variant_id))->values();

                if ($parentItem) {
                    $parentItem->is_parent = true;
                    $parentItem->resolved_variant_name = null;
                    $preparedItems->push($parentItem);
                }

                foreach ($variantItems as $vItem) {
                    $vItem->is_parent = false;
                    $vName = null;
                    $v = $product->variants->firstWhere('id', $vItem->product_variant_id);
                    if ($v && $v->attributeValue) {
                        $vName = ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '');
                    }
                    $vItem->resolved_variant_name = $vName;
                    $preparedItems->push($vItem);
                }
            } else {
                $parentItem = $firstItem;
                $parentItem->is_parent = true;
                $parentItem->resolved_variant_name = null;

                $variantItems      = $siblings->slice(1)->values();
                $variants          = $product->variants ?? collect();
                $matchedMap        = [];
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
                        $ms          = $unmatchedSiblings[$matchedIdx];
                        $vName       = null;
                        if ($v->attributeValue) {
                            $vName = ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '');
                        }
                        $ms->resolved_variant_name = $vName;
                        $ms->is_parent             = false;
                        $matchedMap[$ms->id]       = $ms;
                        unset($unmatchedSiblings[$matchedIdx]);
                    }
                }

                $unmatchedSiblings = array_values($unmatchedSiblings);
                $unmatchedVariants = [];
                foreach ($variants as $v) {
                    $vName = null;
                    if ($v->attributeValue) {
                        $vName = ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '');
                    }
                    $already = false;
                    foreach ($matchedMap as $ms) {
                        if ($ms->resolved_variant_name === $vName) { $already = true; break; }
                    }
                    if (!$already) $unmatchedVariants[] = $v;
                }

                foreach ($unmatchedSiblings as $idx => $sibling) {
                    $v = $unmatchedVariants[$idx] ?? null;
                    $sibling->resolved_variant_name = $v
                        ? (($v->attributeValue ? (($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '')) : null))
                        : null;
                    $sibling->is_parent = false;
                    $matchedMap[$sibling->id] = $sibling;
                }

                $preparedItems->push($parentItem);
                foreach ($variantItems as $vItem) {
                    $preparedItems->push($matchedMap[$vItem->id] ?? $vItem);
                }
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

<div class="strip-bar"></div>
<div class="page">

    {{-- ── HEADER ─────────────────────────────────────────────────── --}}
    <div class="header-wrap">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('assets/img/logo.png') }}" alt="Chetan Imitation" />
                </td>
                <td class="brand-cell">
                    <div class="brand-name">Chetan Imitation</div>
                    <div class="brand-tagline">Premium Imitation Jewellery</div>
                </td>
                <td class="invoice-label-cell">
                    <div class="invoice-label">Sale Invoice</div>
                    <div class="invoice-no">{{ $order->order_no }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    {{-- ── INFO SECTION ────────────────────────────────────────────── --}}
    <table class="info-table">
        <tr>
            {{-- Customer --}}
            <td>
                <div class="info-box">
                    <div class="info-section-title">Customer</div>
                    <div class="info-row">
                        <div class="info-value">{{ $order->customer->name ?? 'Walk-in Customer' }}</div>
                    </div>
                    @if($order->customer?->phone)
                    <div class="info-row">
                        <span class="info-label">Phone: </span>{{ $order->customer->phone }}
                    </div>
                    @endif
                    @if($order->customer?->email)
                    <div class="info-row">
                        <span class="info-label">Email: </span>{{ $order->customer->email }}
                    </div>
                    @endif
                </div>
            </td>

            {{-- Payment --}}
            <td>
                <div class="info-box-mid">
                    <div class="info-section-title">Payment</div>
                    <div class="info-row">
                        <span class="info-label">Method: </span>
                        <span class="info-value">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status: </span>
                        <span class="info-value">{{ $payLabels[$order->payment_status ?? 1] ?? 'Unpaid' }}</span>
                    </div>
                    @if($order->coupon_id && $order->coupon)
                    <div class="info-row">
                        <span class="info-label">Coupon: </span>
                        <span class="info-value">{{ $order->coupon->code }}</span>
                    </div>
                    @endif
                    @if($isOnline && $order->payment?->gateway_payment_id)
                    <div class="info-row">
                        <span class="info-label">Razorpay ID: </span>
                        <span class="info-value" style="font-size:9.5px;">{{ $order->payment->gateway_payment_id }}</span>
                    </div>
                    @endif
                </div>
            </td>

            {{-- Sale Details --}}
            <td>
                <div class="info-box-right">
                    <div class="info-section-title">Sale Details</div>
                    <div class="info-row">
                        <span class="info-label">Date: </span>
                        <span class="info-value">{{ format_date($order->created_at) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Sale No: </span>
                        <span class="info-value">{{ $order->order_no }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Status: </span>
                        <span class="info-value" style="color:#c62828; text-transform:uppercase;">{{ $statusLabels[$order->status ?? 1] ?? 'Pending' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Generated: </span>{{ now()->format('d M Y') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── DELIVERY ADDRESS (online orders only) ───────────────────── --}}
    @if($isOnline && $order->customerAddress)
        @php $addr = $order->customerAddress; @endphp
        <div class="address-box">
            <div class="address-box-title">&#x1F4CD; Delivery Address</div>
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:50%; border:none; padding:0; vertical-align:top;">
                        <div class="address-row"><strong>{{ $addr->name }}</strong></div>
                        <div class="address-row">{{ $addr->phone }}@if($addr->alternate_phone) &nbsp;/&nbsp; {{ $addr->alternate_phone }}@endif</div>
                        <div class="address-row">{{ $addr->address }}</div>
                    </td>
                    <td style="width:50%; border:none; padding:0; vertical-align:top; text-align:right;">
                        <div class="address-row">{{ $addr->city }}, {{ $addr->state }}{{ $addr->pincode ? ' - ' . $addr->pincode : '' }}</div>
                        @if($addr->email)
                        <div class="address-row">{{ $addr->email }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endif

    {{-- ── CANCELLATION & REFUND DETAILS ─────────────────────────── --}}
    @if($order->cancellationRequest && in_array($order->cancellationRequest->status, ['approved', 'pending']))
        <div class="address-box" style="background:#fdf4f4; border-color:#f5c8c8;">
            <div class="address-box-title" style="color:#c62828;">&#x26A0; Order Cancellation & Refund Information</div>
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:55%; border:none; padding:0; vertical-align:top;">
                        <div class="address-row"><strong>Request Status:</strong> <span style="text-transform:uppercase; font-weight:bold; color:{{ $order->cancellationRequest->status === 'approved' ? '#2e7d32' : '#b4771e' }};">{{ $order->cancellationRequest->status }}</span></div>
                        <div class="address-row"><strong>Reason:</strong> {{ $order->cancellationRequest->cancellation_reason }}</div>
                        <div class="address-row"><strong>Submitted On:</strong> {{ $order->cancellationRequest->created_at->format('d M Y, h:i A') }}</div>
                    </td>
                    <td style="width:45%; border:none; padding:0; vertical-align:top; text-align:right;">
                        @if($order->cancellationRequest->status === 'approved')
                            <div class="address-row"><strong>Refunded Amount:</strong> <strong style="color:#2e7d32; font-size:12px;">₹{{ number_format($order->cancellationRequest->refund_amount, 2) }}</strong></div>
                            @if($order->cancellationRequest->refund_gateway_id)
                                <div class="address-row"><strong>Refund Transaction ID:</strong> <span style="font-family:monospace; font-size:9px; background:#f5ecec; padding:1px 3px; border-radius:2px;">{{ $order->cancellationRequest->refund_gateway_id }}</span></div>
                            @endif
                            <div class="address-row" style="color:#666; font-size:9px; margin-top:4px;">Processed via Online Gateway (credited in 5-7 business days)</div>
                        @else
                            <div class="address-row" style="color:#b4771e; font-weight:bold; margin-top:4px;">Refund is pending approval</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endif

    {{-- ── ITEMS TABLE ──────────────────────────────────────────────── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:44%;">Product</th>
                <th class="text-right" style="width:12%;">Price</th>
                <th class="text-right" style="width:14%;">Qty</th>
                <th class="text-right" style="width:14%;">Discount</th>
                <th class="text-right" style="width:12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($preparedItems as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td @if(!$item->is_parent) style="padding-left:0;" @endif>
                        @if(!$item->is_parent)
                            <strong style="font-size:10.5px;">{{ $item->product->name ?? '-' }}</strong>
                            <span style="color:#B4771E; font-weight:bold; margin:0 4px;">&#8627;</span>
                            <span style="font-size:10px; color:#666;">{{ $item->resolved_variant_name ?? '-' }}</span>
                        @else
                            <strong>{{ $item->product->name ?? '-' }}</strong>
                        @endif
                    </td>
                    <td class="text-right">{{ format_price($item->price) }}</td>
                    <td class="text-right" style="white-space: nowrap;">
                        {{ $item->quantity }}
                        @if($item->custom_size_value)
                            &times;{{ rtrim(rtrim(number_format((float) $item->custom_size_value, 2), '0'), '.') }}pcs
                        @else
                            {{ ($item->pair_type ?? 'single') === 'pair' ? 'Pairs' : 'Pcs' }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item->discount_amount > 0)
                            @if($item->discount_type === 'percentage')
                                {{ number_format($item->discount_value, 2) }}%
                            @else
                                {{ format_price($item->discount_amount) }}
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right"><strong>{{ format_price($item->total) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="subtotal-row">
                <td colspan="5" class="text-right" style="font-weight:bold; color:#555;">Subtotal</td>
                <td class="text-right" style="font-weight:bold;">{{ format_price($subtotal) }}</td>
            </tr>
            @if($totalDiscount > 0)
            <tr class="discount-row">
                <td colspan="5" class="text-right">Discount</td>
                <td class="text-right">-{{ format_price($totalDiscount) }}</td>
            </tr>
            @elseif($order->coupon_id && $order->coupon)
            <tr class="coupon-row">
                <td colspan="5" class="text-right">
                    Coupon Applied &nbsp; {{ $order->coupon->code }}
                </td>
                <td class="text-right">-</td>
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
                    <tr class="cgst-row">
                        <td colspan="5" class="text-right">CGST ({{ $halfRate }}%)</td>
                        <td class="text-right">{{ format_price($halfTax) }}</td>
                    </tr>
                    <tr class="sgst-row">
                        <td colspan="5" class="text-right">SGST ({{ $halfRate }}%)</td>
                        <td class="text-right">{{ format_price($halfTax) }}</td>
                    </tr>
                @else
                    <tr class="igst-row">
                        <td colspan="5" class="text-right">IGST ({{ $gstRate }}%)</td>
                        <td class="text-right">{{ format_price($taxAmount) }}</td>
                    </tr>
                @endif
            @endif
            <tr class="shipping-row">
                <td colspan="5" class="text-right">Shipping</td>
                <td class="text-right">{{ $order->shipping_charge > 0 ? format_price($order->shipping_charge) : 'Free' }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="5" class="text-right">Final Amount</td>
                <td class="text-right">{{ format_price($order->final_amount) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ── FOOTER ────────────────────────────────────────────────────── --}}
    <div class="footer">
        <div class="footer-thank">Thank you for shopping by chetan imitation!</div>
        <div class="footer-text">
            Generated on {{ now()->format('d M Y, H:i') }}
            &nbsp;&bull;&nbsp;
            {{ config('app.name', 'Chetan Imitation') }}
            &nbsp;&bull;&nbsp;
            This is a computer-generated invoice and does not require a signature.
        </div>
    </div>

</div>
<div class="strip-bar"></div>

</body>
</html>
