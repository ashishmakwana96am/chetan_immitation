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
            padding: 6pt 8pt;
        }

        .receipt-container {
            width: 200pt;
            @if(!empty($pdfHeight))
            height: {{ $pdfHeight - 12 }}pt;
            @endif
            position: relative;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        .fw-bold     { font-weight: bold; }

        .store-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .gstin-label {
            font-size: 10.5px;
            font-weight: bold;
            margin-top: 1px;
        }

        .divider-dotted {
            border-top: 1px dashed #000;
            margin: 3px 0;
        }

        .divider-solid {
            border-top: 1px solid #000;
            margin: 4px 0;
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

        // GST & Tax Calculations
        $isGst = (bool)$order->is_gst;

        if ($isGst) {
            $taxableAmt = (float) $subtotal;
            $totalTax   = max(0, (float)$order->final_amount - $taxableAmt - (float)$order->shipping_charge);
            $taxCgst    = round($totalTax / 2, 2);
            $taxSgst    = round($totalTax - $taxCgst, 2);
        } else {
            $taxableAmt = (float)$order->final_amount;
            $totalTax   = 0.00;
            $taxCgst    = 0.00;
            $taxSgst    = 0.00;
        }

        $paymentCash = 0.00;
        $paymentUpi  = 0.00;

        $pm = strtolower($order->payment_method ?? '');
        if (in_array($pm, ['upi', 'online', 'razorpay', 'qr'])) {
            $paymentUpi = (float)$order->final_amount;
        } else {
            $paymentCash = (float)$order->final_amount;
        }

        $totalQty = $order->items->sum('quantity');

        // Location & Phone details
        $locations = \App\Models\Location::orderByRaw('id = ? DESC', [$order->location_id ?? 0])->limit(2)->get();
        $loc1 = $locations[0] ?? null;
        $loc2 = $locations[1] ?? null;

        $gstinVal = $order->location?->gst_number ?? \App\Models\Setting::getValue('store_gst_number', '24SCOPS0159A1ZB');
    @endphp

    <div class="receipt-container">

        {{-- Header Section --}}
        <div class="text-center">
            <div class="store-title">CHETAN IMITATION</div>
            @if($isGst || !empty($gstinVal))
                <div class="gstin-label">GSTIN: {{ $gstinVal }}</div>
            @endif
        </div>

        {{-- Logo --}}
        <div class="text-center" style="margin: 3px 0;">
            <img src="{{ public_path('assets/img/thermal-logo.png') }}" style="width: 50px; height: 50px; object-fit: contain;" alt="Logo" />
        </div>

        <table style="width: 100%; border-collapse: collapse; font-weight: bold; font-size: 10.5px; margin-bottom: 2px;">
            <tr>
                <td style="text-align: left; width: 50%; border: none; padding: 0;">
                    {{ strtoupper($loc1->name ?? 'KATARGAM') }}
                </td>
                <td style="text-align: right; width: 50%; border: none; padding: 0;">
                    {{ strtoupper($loc2->name ?? 'MOTA VRACHA') }}
                </td>
            </tr>
        </table>

        <div class="divider-dotted"></div>

        {{-- Invoice Title & Phones Row --}}
        <table style="width: 100%; border-collapse: collapse; font-weight: bold; font-size: 11px;">
            <tr>
                <td style="text-align: left; width: 33%; border: none; padding: 0;">
                    {{ $loc1->phone ?? '7725978871' }}
                </td>
                <td style="text-align: center; width: 34%; font-size: 11.5px; border: none; padding: 0;">
                    INVOICE
                </td>
                <td style="text-align: right; width: 33%; border: none; padding: 0;">
                    {{ $loc2->phone ?? '8980293353' }}
                </td>
            </tr>
        </table>

        <div class="divider-dotted"></div>

        {{-- Customer & Bill Details Block --}}
        <table style="width: 100%; border-collapse: collapse; line-height: 1.3; font-size: 10.5px; font-weight: bold; margin-bottom: 2px;">
            <tr>
                <td style="width: 50%; text-align: left; vertical-align: top; padding: 1px 0; text-transform: uppercase; border: none;">
                    @php
                        $custName = $order->customer ? $order->customer->name : 'WALK-IN';
                        if (strtoupper($custName) === 'WALK-IN CUSTOMER') {
                            $custName = 'WALK-IN';
                        }
                    @endphp
                    NAME : {{ $custName }}
                </td>
                <td style="width: 50%; text-align: left; vertical-align: top; padding: 1px 0; padding-left: 4px; border: none;">
                    BILL NO : {{ $order->order_no }}
                </td>
            </tr>
            <tr>
                <td style="width: 50%; text-align: left; vertical-align: top; padding: 1px 0; text-transform: uppercase; border: none;">
                    ADD : {{ $order->customerAddress ? $order->customerAddress->address : '' }}
                </td>
                <td style="width: 50%; text-align: left; vertical-align: top; padding: 1px 0; padding-left: 4px; border: none;">
                    DATE : {{ $order->created_at->format('d/m/y') }}
                </td>
            </tr>
            <tr>
                <td style="width: 50%; text-align: left; vertical-align: top; padding: 1px 0; border: none;">
                    PH : {{ $order->customer?->phone ?? ($order->customerAddress?->phone ?? '') }}
                </td>
                <td style="width: 50%; text-align: left; vertical-align: top; padding: 1px 0; padding-left: 4px; border: none;">
                    TIME : {{ $order->created_at->format('h:i:s A') }}
                </td>
            </tr>
        </table>

        <div class="divider-solid"></div>

        {{-- Items Table --}}
        <table style="width: 100%; border-collapse: collapse; margin: 2px 0; font-size: 10.5px; font-weight: bold; line-height: 1.3;">
            <thead>
                <tr style="border-bottom: 1px solid #000;">
                    <th style="text-align: left; padding-bottom: 2px; font-weight: bold; border: none;">ITEM NAME</th>
                    <th style="text-align: center; padding-bottom: 2px; font-weight: bold; width: 20%; border: none;">Qty</th>
                    <th style="text-align: right; padding-bottom: 2px; font-weight: bold; width: 22%; border: none;">Rate</th>
                    <th style="text-align: right; padding-bottom: 2px; font-weight: bold; width: 22%; border: none;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td style="text-align: left; padding: 2px 0; text-transform: uppercase; vertical-align: top; border: none;">
                            {{ $item->product?->subCategory?->name ?? $item->product?->category?->name ?? $item->product?->name ?? '-' }}
                        </td>
                        <td style="text-align: center; padding: 2px 0; vertical-align: top; border: none; white-space: nowrap;">
                            {{ $item->quantity }}
                            @php
                                $szVal = $item->custom_size_value ?: ($item->product?->pair_product ? (collect($item->product?->custom_sizes ?? [])->pluck('size')->max() ?: 2) : null);
                            @endphp
                            @if($szVal)
                                &times; {{ rtrim(rtrim(number_format((float) $szVal, 2), '0'), '.') }}pcs
                            @endif
                        </td>
                        <td style="text-align: right; padding: 2px 0; vertical-align: top; border: none;">
                            {{ number_format($item->price, 2) }}
                        </td>
                        <td style="text-align: right; padding: 2px 0; vertical-align: top; border: none;">
                            {{ number_format($item->total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider-dotted"></div>

        {{-- Totals Block --}}
        <table style="width: 100%; border-collapse: collapse; font-size: 11px; font-weight: bold; line-height: 1.3;">
            @if($totalDiscount > 0)
            <tr>
                <td style="text-align: left; width: 65%; border: none; padding: 1px 0;">Discount Amt :</td>
                <td style="text-align: right; width: 35%; border: none; padding: 1px 0;">{{ number_format($totalDiscount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td style="text-align: left; width: 45%; border: none; padding-top: 2px;">TOTAL</td>
                <td style="text-align: center; width: 20%; border: none; padding-top: 2px;">{{ $totalQty }}</td>
                <td style="text-align: right; width: 35%; border: none; padding-top: 2px;">
                    {{ number_format((float)$order->final_amount, 0) }}
                </td>
            </tr>
        </table>

        <div class="divider-dotted"></div>

        @if($isGst)
        {{-- TAX DETAIL & PAYMENT DETAIL Side-by-Side Table (For GST Bills) --}}
        <table style="width: 100%; border-collapse: collapse; font-size: 10.5px; font-weight: bold; line-height: 1.35; margin-bottom: 2px;">
            <tr style="border-bottom: 1px dashed #000;">
                <td style="width: 50%; text-align: left; padding-bottom: 2px; border: none;">TAX DETAIL</td>
                <td style="width: 50%; text-align: right; padding-bottom: 2px; border: none;">
                    <span style="float: left;">CASH :</span> {{ number_format($paymentCash, 2) }}
                </td>
            </tr>
            <tr>
                <td style="width: 50%; text-align: left; padding: 1px 0; border: none;">
                    AMOUNT : {{ number_format($taxableAmt, 2) }}
                </td>
                <td style="width: 50%; text-align: right; padding: 1px 0; border: none;">
                    <span style="float: left;">UPI :</span> {{ number_format($paymentUpi, 2) }}
                </td>
            </tr>
            <tr>
                <td style="width: 50%; text-align: left; padding: 1px 0; border: none;">
                    SGST : {{ number_format($taxSgst, 2) }}
                </td>
                <td style="width: 50%; text-align: right; padding: 1px 0; border: none;"></td>
            </tr>
            <tr>
                <td style="width: 50%; text-align: left; padding: 1px 0; border: none;">
                    CGST : {{ number_format($taxCgst, 2) }}
                </td>
                <td style="width: 50%; text-align: right; padding: 1px 0; border: none;"></td>
            </tr>
            <tr>
                <td style="width: 50%; text-align: left; padding: 1px 0; border: none;">
                    TOTAL : {{ number_format($totalTax, 2) }}
                </td>
                <td style="width: 50%; text-align: right; padding: 1px 0; border: none;"></td>
            </tr>
        </table>
        @else
        {{-- PAYMENT DETAIL Block (For Non-GST Bills) --}}
        <table style="width: 100%; border-collapse: collapse; font-size: 11px; font-weight: bold; line-height: 1.4; margin-bottom: 2px;">
            <tr style="border-bottom: 1px dashed #000;">
                <td colspan="2" style="text-align: left; padding-bottom: 3px; border: none;">PAYMENT DETAIL</td>
            </tr>
            <tr>
                <td style="width: 50%; text-align: left; padding: 2px 0; border: none;">CASH :</td>
                <td style="width: 50%; text-align: right; padding: 2px 0; border: none;">{{ number_format($paymentCash, 2) }}</td>
            </tr>
            <tr>
                <td style="width: 50%; text-align: left; padding: 2px 0; border: none;">UPI :</td>
                <td style="width: 50%; text-align: right; padding: 2px 0; border: none;">{{ number_format($paymentUpi, 2) }}</td>
            </tr>
        </table>
        @endif

        <div class="divider-dotted"></div>

        {{-- Terms & Conditions Footer --}}
        @php $arrow = '<span style="font-family: DejaVu Sans, sans-serif; font-weight: bold; font-size: 12.5px;">&#8594;</span>'; @endphp
        <div style="text-align: left; font-size: 10.5px; font-weight: bold; line-height: 1.3;">
            <div style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">TERMS & CONDITION</div>
            <div>{!! $arrow !!} KEEP THE PRODUCT AWAY FROM PERFUME, WATER AND CHEMICALS.</div>
            <div>{!! $arrow !!} ITEM THAT CAN BE REPAIRED WILL BE REPAIRED, CHARGEABLE.</div>
            <div>{!! $arrow !!} NO RETURN, NO EXCHANGE.</div>
            <div>{!! $arrow !!} NO GUARANTEE ON POLISH.</div>
        </div>

        <div class="divider-dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>

        {{-- Monospaced Thank You Note --}}
        <div style="text-align: center; font-family: Arial, sans-serif; font-size: 11px; font-weight: bold; line-height: 1.4;">
            <div>Thank you for shopping by chetan imitation!</div>
        </div>

    </div>

</body>
</html>
