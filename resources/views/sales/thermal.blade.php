<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sale - {{ $order->order_no }}</title>
    <style>
        @page {
            margin: 0px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
            color: #000;
            font-weight: bold;
        }

        html, body {
            width: 216pt;
            @if(!empty($pdfHeight))
            height: {{ $pdfHeight }}pt;
            overflow: hidden;
            @endif
            background: #fff;
        }

        body {
            padding: 8pt;
        }

        .receipt-container {
            width: 200pt;
            @if(!empty($pdfHeight))
            height: {{ $pdfHeight - 16 }}pt;
            @endif
            position: relative;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        .fw-bold     { font-weight: bold; }

        .store-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .gstin-label {
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px;
        }

        .logo-container {
            margin: 4px 0;
            text-align: center;
        }

        .logo-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .divider-dotted {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .divider-solid {
            border-top: 1px solid #000;
            margin: 5px 0;
        }
    </style>
</head>
<body>

    @php
        $totalItemDiscount = $order->items->sum('discount_amount');
        $itemsGross        = $order->items->sum(fn($i) => (float)$i->price * (float)$i->quantity);
        $subtotal          = $itemsGross;

        $couponDiscount = 0;
        if ($order->coupon_id && $order->coupon) {
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

        $mrpSubtotal = 0.00;
        foreach($order->items as $item) {
            $itemMrp = ((float)($item->mrp ?? 0) > 0) ? (float)$item->mrp : ($item->variant?->mrp ?? ($item->product?->mrp ?? 0));
            if ($item->product?->pair_product && $item->custom_size_value && !(float)($item->mrp ?? 0)) {
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
            $itemBaseMrp = $itemMrp > 0 ? $itemMrp : (float)$item->price;
            $mrpSubtotal += ($itemBaseMrp * (float)$item->quantity);
        }

        $gstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);
        $isGst = (bool)$order->is_gst;
        
        $totalTax = $isGst ? (float)$order->tax_amount : 0.00;
        $taxableAmount = (float)$order->final_amount - $totalTax - (float)$order->shipping_charge;

        $sgst = 0.00;
        $cgst = 0.00;
        $igst = 0.00;

        if ($isGst) {
            $isPos = ($order->source ?? 'POS') === 'POS';
            $buyerState = 'gujarat';
            if (!$isPos && $order->customerAddress) {
                $buyerState = strtolower(trim($order->customerAddress->state));
            }
            $storeState = strtolower(trim(\App\Models\Setting::getValue('store_state', 'gujarat')));
            
            if ($isPos || $buyerState === '' || $buyerState === $storeState) {
                $sgst = round($totalTax / 2, 2);
                $cgst = round($totalTax / 2, 2);
                $totalTax = $sgst + $cgst;
            } else {
                $igst = round($totalTax, 2);
                $totalTax = $igst;
            }
        }

        $paymentCash   = 0.00;
        $paymentUpi    = 0.00;
        $paymentChaque = 0.00;
        $paymentCard   = 0.00;
        $dueAmount     = 0.00;
        $paymentStatusInt = (int)($order->payment_status ?? 1);
        $isPartiallyPaid  = $paymentStatusInt === 3; // PAYMENT_STATUS_PARTIAL
        $isPending        = $paymentStatusInt === 1; // PAYMENT_STATUS_PENDING

        if ($paymentStatusInt === 2) { // PAYMENT_STATUS_PAID
            if ((float) $order->paid_cash_amount > 0 || (float) $order->paid_online_amount > 0) {
                $paymentCash = (float) $order->paid_cash_amount;
                $paymentUpi  = (float) $order->paid_online_amount;
            } else {
                $pm = strtolower($order->payment_method ?? '');
                if (in_array($pm, ['upi', 'online', 'razorpay', 'bank_transfer', 'bank transfer'])) {
                    $paymentUpi = (float)$order->final_amount;
                } elseif (in_array($pm, ['cheque', 'chaque', 'check'])) {
                    $paymentChaque = (float)$order->final_amount;
                } elseif (in_array($pm, ['card', 'debit_card', 'credit_card', 'debit card', 'credit card'])) {
                    $paymentCard = (float)$order->final_amount;
                } else {
                    $paymentCash = (float)$order->final_amount;
                }
            }
        } elseif ($isPartiallyPaid) {
            $paymentCash = (float) $order->paid_cash_amount;
            $paymentUpi  = (float) $order->paid_online_amount;
            $totalPaid   = $paymentCash + $paymentUpi;
            $dueAmount   = max(0, (float)$order->final_amount - $totalPaid);
        } elseif ($isPending) {
            $dueAmount = (float) $order->final_amount;
        }

        $totalDiscountOnMrp = max(0, round($mrpSubtotal - (float)$order->final_amount, 2));
        $totalDiscount = max($totalDiscountOnMrp, round($totalItemDiscount + $orderDiscountAmount + $couponDiscount, 2));
        $totalQty = $order->items->sum('quantity');
        $youSaved = max($totalDiscountOnMrp, $totalDiscount);
    @endphp

    <div class="receipt-container">

        <div class="text-center">
            <div class="store-title">Chetan Imitation</div>
            @if((($order->source ?? 'POS') !== 'ONLINE') && $order->location?->gst_number)
                <div class="gstin-label">GSTIN: {{ $order->location->gst_number }}</div>
            @endif
        </div>

        <div class="divider-dotted" style="margin-top: 3px; margin-bottom: 3px;"></div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2px;">
            <tr>
                <td style="width: 65px; vertical-align: middle; border: none; padding: 0;">
                    <img src="{{ public_path('assets/img/thermal-logo.png') }}" style="width: 58px; height: 58px; object-fit: contain;" alt="Logo" />
                </td>
                <td style="vertical-align: middle; border: none; padding-left: 8px; text-align: left; font-size: 11px; font-weight: bold; line-height: 1.35;">
                    @php
                        $locations = \App\Models\Location::orderByRaw('id = ? DESC', [$order->location_id ?? 0])->limit(3)->get();
                    @endphp
                    @foreach($locations as $loc)
                        <div>{{ strtoupper($loc->name) }} - {{ $loc->phone ?: '7725978871' }}</div>
                    @endforeach
                </td>
            </tr>
        </table>

        <div class="divider-dotted" style="margin-top: 3px; margin-bottom: 3px;"></div>

        <table style="width: 100%; border-collapse: collapse; font-weight: bold; font-size: 12px;">
            <tr>
                <td style="text-align: left; width: 50%; font-size: 13px; border: none; padding: 0;">
                    INVOICE
                </td>
                <td style="text-align: right; width: 50%; border: none; padding: 0;">
                    {{ $order->location?->phone ?? '7725978871' }}
                </td>
            </tr>
        </table>

        <div class="divider-dotted"></div>

        <table style="width: 100%; border-collapse: collapse; line-height: 1.3; font-size: 11.5px; font-weight: bold; margin-bottom: 2px;">
            <tr>
                <td style="width: 52%; text-align: left; vertical-align: top; padding: 1px 0; text-transform: uppercase; border: none;">
                    @php
                        $custName = $order->customer ? $order->customer->name : 'WALK-IN';
                        if (strtoupper($custName) === 'WALK-IN CUSTOMER') {
                            $custName = 'WALK-IN';
                        }
                    @endphp
                    NAME : {{ $custName }}
                </td>
                <td style="width: 48%; text-align: left; vertical-align: top; padding: 1px 0; padding-left: 6px; border: none;">
                    BILL NO : {{ $order->order_no }}
                </td>
            </tr>
            <tr>
                <td style="width: 52%; text-align: left; vertical-align: top; padding: 1px 0; text-transform: uppercase; border: none;">
                    ADD : {{ $order->customerAddress ? $order->customerAddress->address : '-' }}
                </td>
                <td style="width: 48%; text-align: left; vertical-align: top; padding: 1px 0; padding-left: 6px; border: none;">
                    DATE : {{ $order->created_at->format('d/m/y') }}
                </td>
            </tr>
            <tr>
                <td style="width: 52%; text-align: left; vertical-align: top; padding: 1px 0; border: none;">
                    PH : {{ $order->customer?->phone ?? ($order->customerAddress?->phone ?? '-') }}
                </td>
                <td style="width: 48%; text-align: left; vertical-align: top; padding: 1px 0; padding-left: 6px; border: none;">
                    TIME : {{ $order->created_at->format('h:i:s A') }}
                </td>
            </tr>
        </table>

        <div class="divider-solid"></div>

        <table style="width: 100%; border-collapse: collapse; margin: 3px 0; font-size: 11.5px; font-weight: bold; line-height: 1.3;">
            <thead>
                <tr style="border-bottom: 1px solid #000;">
                    <th style="text-align: left; padding-bottom: 3px; font-weight: bold; border: none;">ITEM NAME</th>
                    <th style="text-align: center; padding-bottom: 3px; font-weight: bold; width: 24%; border: none;">Qty</th>
                    <th style="text-align: right; padding-bottom: 3px; font-weight: bold; width: 20%; border: none;">Rate</th>
                    <th style="text-align: right; padding-bottom: 3px; font-weight: bold; width: 20%; border: none;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    @php
                        $itemMrp = ((float)($item->mrp ?? 0) > 0) ? (float)$item->mrp : ($item->variant?->mrp ?? ($item->product?->mrp ?? 0));
                        if ($item->product?->pair_product && $item->custom_size_value && !(float)($item->mrp ?? 0)) {
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
                        $rowRate = $itemMrp > 0 ? $itemMrp : (float)$item->price;
                        $rowAmount = $rowRate * (float)$item->quantity;
                    @endphp
                    <tr>
                        <td style="text-align: left; padding: 3px 0; text-transform: uppercase; vertical-align: top; border: none;">
                            {{ $item->product?->subCategory?->name ?? $item->product?->category?->name ?? '-' }}
                        </td>
                        <td style="text-align: center; padding: 3px 0; vertical-align: top; border: none; white-space: nowrap;">
                            {{ $item->quantity }}
                            @php
                                $szVal = $item->custom_size_value ?: ($item->product?->pair_product ? (collect($item->product?->custom_sizes ?? [])->pluck('size')->max() ?: 2) : null);
                            @endphp
                            @if($szVal)
                                &times; {{ rtrim(rtrim(number_format((float) $szVal, 2), '0'), '.') }}pcs
                            @else
                                Pcs
                            @endif
                        </td>
                        <td style="text-align: right; padding: 3px 0; vertical-align: top; border: none;">
                            {{ number_format($rowRate, 2) }}
                        </td>
                        <td style="text-align: right; padding: 3px 0; vertical-align: top; border: none;">
                            {{ number_format($rowAmount, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider-dotted"></div>

        <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; font-weight: bold; line-height: 1.3;">
            <tr>
                <td colspan="2" style="text-align: left; width: 70%; border: none; padding: 1px 0;">SUB TOTAL</td>
                <td style="text-align: right; width: 30%; border: none; padding: 1px 0;">{{ number_format($mrpSubtotal, 2) }}</td>
            </tr>
            @if($totalDiscount > 0)
            <tr>
                <td colspan="2" style="text-align: left; width: 70%; border: none; padding: 1px 0;">DISCOUNT</td>
                <td style="text-align: right; width: 30%; border: none; padding: 1px 0;">{{ number_format($totalDiscount, 2) }}</td>
            </tr>
            @endif
            @if($isGst && $totalTax > 0)
            <tr>
                <td colspan="2" style="text-align: left; width: 70%; border: none; padding: 1px 0;">GST</td>
                <td style="text-align: right; width: 30%; border: none; padding: 1px 0;">{{ number_format($totalTax, 2) }}</td>
            </tr>
            @endif
            @if($order->shipping_charge > 0)
            <tr>
                <td colspan="2" style="text-align: left; width: 70%; border: none; padding: 1px 0;">Shipping Charge</td>
                <td style="text-align: right; width: 30%; border: none; padding: 1px 0;">{{ number_format($order->shipping_charge, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td style="text-align: left; width: 50%; border: none; padding-top: 3px;">TOTAL</td>
                <td style="text-align: center; width: 20%; border: none; padding-top: 3px;">{{ $totalQty }}</td>
                <td style="text-align: right; width: 30%; border: none; padding-top: 3px;">
                    {{ number_format((float)$order->final_amount, 2) }}
                </td>
            </tr>
            @if($youSaved > 0)
            <tr style="border-top: 1px dashed #000;">
                <td colspan="2" style="text-align: left; width: 70%; border: none; padding-top: 3px; font-size: 12px; text-transform: uppercase;">YOU SAVED</td>
                <td style="text-align: right; width: 30%; border: none; padding-top: 3px; font-size: 12px;">{{ number_format($youSaved, 2) }}</td>
            </tr>
            @endif
        </table>

        <div class="divider-dotted"></div>

        {{-- PAYMENT DETAIL ONLY --}}
        <table style="width: 100%; border-collapse: collapse; font-size: 10.5px; font-weight: bold; line-height: 1.35; margin-bottom: 2px;">
            <tr>
                <td style="width: 50%; text-align: left; padding: 1px 0; border: none;">CASH :</td>
                <td style="width: 50%; text-align: right; padding: 1px 0; border: none;">{{ number_format($paymentCash, 2) }}</td>
            </tr>
            <tr>
                <td style="width: 50%; text-align: left; padding: 1px 0; border: none;">UPI :</td>
                <td style="width: 50%; text-align: right; padding: 1px 0; border: none;">{{ number_format($paymentUpi, 2) }}</td>
            </tr>
            @if(($isPartiallyPaid || $isPending) && $dueAmount > 0)
            <tr style="border-top: 1px dashed #000;">
                <td style="width: 50%; text-align: left; padding: 2px 0; border: none;">DUE :</td>
                <td style="width: 50%; text-align: right; padding: 2px 0; border: none;">{{ number_format($dueAmount, 2) }}</td>
            </tr>
            @endif
        </table>

        @php $arrow = '<span style="font-family: DejaVu Sans, sans-serif; font-weight: bold; font-size: 12.5px;">&#8594;</span>'; @endphp
        <div style="text-align: left; font-size: 10.5px; font-weight: bold; line-height: 1.3;">
            <div style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">TERMS & CONDITION</div>
            <div>{!! $arrow !!} KEEP THE PRODUCT AWAY FROM PERFUME, WATER AND CHEMICALS.</div>
            <div>{!! $arrow !!} ITEM THAT CAN BE REPAIRED WILL BE REPAIRED, CHARGEABLE.</div>
            <div>{!! $arrow !!} NO RETURN, NO EXCHANGE.</div>
            <div>{!! $arrow !!} NO GUARANTEE ON POLISH.</div>
        </div>

        <div class="divider-dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>

        <div style="text-align: center; font-family: Arial, sans-serif; font-size: 11px; font-weight: bold; line-height: 1.4;">
            <div>Thank you for shopping by chetan imitation!</div>
        </div>

    </div>

</body>
</html>
