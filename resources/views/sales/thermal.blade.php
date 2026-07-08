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
            font-family: 'Courier New', Courier, monospace;
            color: #000;
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
            font-size: 13.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .gstin-label {
            font-size: 8.5px;
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

        // GST Calculations (3% total, split into 1.5% CGST + 1.5% SGST)
        $taxableAmount = round((float)$order->final_amount / 1.03, 2);
        $totalTax      = round((float)$order->final_amount - $taxableAmount, 2);
        $sgst          = round($totalTax / 2, 2);
        $cgst          = round($totalTax - $sgst, 2);

        // Payment Mapping
        $paymentCash = 0.00;
        $paymentUpi = 0.00;
        $paymentCheque = 0.00;
        $paymentCard = 0.00;
        $balance = 0.00;

        $pm = strtolower($order->payment_method ?? '');
        if ($pm === 'cash') {
            $paymentCash = (float)$order->final_amount;
        } elseif (in_array($pm, ['upi', 'online', 'razorpay', 'bank_transfer', 'bank transfer'])) {
            $paymentUpi = (float)$order->final_amount;
        } elseif ($pm === 'card') {
            $paymentCard = (float)$order->final_amount;
        } elseif (in_array($pm, ['cheque', 'chaque'])) {
            $paymentCheque = (float)$order->final_amount;
        }

        $totalQty = $order->items->sum('quantity');
    @endphp

    <div class="receipt-container">

        {{-- Header Section --}}
        <div class="text-center">
            <div class="store-title">Chetan Imitation</div>
            <div class="gstin-label">GSTIN: 24SCOPS0159A1ZB</div>
        </div>

        <div class="divider-dotted" style="margin-top: 3px; margin-bottom: 3px;"></div>

        {{-- Logo and Branch details row --}}
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2px;">
            <tr>
                {{-- Left side: Logo --}}
                <td style="width: 65px; vertical-align: middle; border: none; padding: 0;">
                    <img src="{{ public_path('assets/img/thurmal-logo.png') }}" style="width: 58px; height: 58px; object-fit: contain;" alt="Logo" />
                </td>
                {{-- Right side: Branches sorted by current order's branch first, limited to 3 --}}
                <td style="vertical-align: middle; border: none; padding-left: 8px; text-align: left; font-size: 7.5px; font-weight: bold; line-height: 1.35;">
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

        {{-- Invoice Title Row --}}
        <table style="width: 100%; border-collapse: collapse; font-weight: bold; font-size: 8.5px;">
            <tr>
                <td style="text-align: left; width: 50%; font-size: 9.5px; border: none; padding: 0;">
                    INVOICE
                </td>
                <td style="text-align: right; width: 50%; border: none; padding: 0;">
                    {{ $order->location?->phone ?? '7725978871' }}
                </td>
            </tr>
        </table>

        <div class="divider-dotted"></div>

        {{-- Customer & Bill Details Block --}}
        <table style="width: 100%; border-collapse: collapse; line-height: 1.3; font-size: 8px; font-weight: bold; margin-bottom: 2px;">
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

        {{-- Items Table --}}
        <table style="width: 100%; border-collapse: collapse; margin: 3px 0; font-size: 8px; font-weight: bold; line-height: 1.3;">
            <thead>
                <tr style="border-bottom: 1px solid #000;">
                    <th style="text-align: left; padding-bottom: 3px; font-weight: bold; border: none;">ITEM NAME</th>
                    <th style="text-align: center; padding-bottom: 3px; font-weight: bold; width: 12%; border: none;">Qty</th>
                    <th style="text-align: right; padding-bottom: 3px; font-weight: bold; width: 22%; border: none;">Rate</th>
                    <th style="text-align: right; padding-bottom: 3px; font-weight: bold; width: 22%; border: none;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td style="text-align: left; padding: 3px 0; text-transform: uppercase; vertical-align: top; border: none;">
                            {{ $item->product->name ?? '-' }}
                        </td>
                        <td style="text-align: center; padding: 3px 0; vertical-align: top; border: none;">
                            {{ $item->quantity }} {{ ($item->pair_type ?? 'single') === 'pair' ? 'Pairs' : 'Pcs' }}
                        </td>
                        <td style="text-align: right; padding: 3px 0; vertical-align: top; border: none;">
                            {{ number_format($item->price, 2) }}
                        </td>
                        <td style="text-align: right; padding: 3px 0; vertical-align: top; border: none;">
                            {{ number_format($item->total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider-dotted"></div>

        {{-- Totals Block --}}
        <table style="width: 100%; border-collapse: collapse; font-size: 8px; font-weight: bold; line-height: 1.3;">
            <tr>
                <td colspan="2" style="text-align: left; width: 70%; border: none; padding: 1px 0;">Discount Amt :</td>
                <td style="text-align: right; width: 30%; border: none; padding: 1px 0;">{{ number_format($totalDiscount, 2) }}</td>
            </tr>
            <tr>
                <td style="text-align: left; width: 50%; border: none; padding-top: 3px;">TOTAL</td>
                <td style="text-align: center; width: 20%; border: none; padding-top: 3px;">{{ $totalQty }}</td>
                <td style="text-align: right; width: 30%; border: none; padding-top: 3px;">{{ number_format($order->final_amount, 0) }}</td>
            </tr>
        </table>

        <div class="divider-dotted"></div>

        {{-- Tax & Payment Detail block --}}
        <table style="width: 100%; border-collapse: collapse; font-size: 8px; font-weight: bold; line-height: 1.4; margin-bottom: 2px;">
            <!-- Header Row with solid borders -->
            <tr style="border-bottom: 1px solid #000;">
                <td colspan="2" style="text-align: left; padding-bottom: 3px; border: none;">TAX DETAIL</td>
                <td style="text-align: left; padding-bottom: 3px; border: none; padding-left: 6px; border-left: 1px dotted #000; text-transform: uppercase;">
                    {{ $order->payment_method === 'cod' ? 'COD' : 'CASH' }} :
                </td>
                <td style="text-align: right; padding-bottom: 3px; border: none;">
                    {{ number_format($order->final_amount, 2) }}
                </td>
            </tr>
            <!-- Row 1 -->
            <tr>
                <td style="width: 25%; text-align: left; padding: 2px 0; border: none;">AMOUNT :</td>
                <td style="width: 23%; text-align: right; padding: 2px 0; border: none; padding-right: 4px;">{{ number_format($taxableAmount, 2) }}</td>
                <td style="width: 27%; text-align: left; padding: 2px 0; border: none; padding-left: 6px; border-left: 1px dotted #000;">UPI :</td>
                <td style="width: 25%; text-align: right; padding: 2px 0; border: none;">{{ number_format($paymentUpi, 2) }}</td>
            </tr>
            <!-- Row 2 -->
            <tr>
                <td style="text-align: left; padding: 2px 0; border: none;">SGST :</td>
                <td style="text-align: right; padding: 2px 0; border: none; padding-right: 4px;">{{ number_format($sgst, 2) }}</td>
                <td style="text-align: left; padding: 2px 0; border: none; padding-left: 6px; border-left: 1px dotted #000;">CHAQUE :</td>
                <td style="text-align: right; padding: 2px 0; border: none;">{{ number_format($paymentCheque, 2) }}</td>
            </tr>
            <!-- Row 3 -->
            <tr>
                <td style="text-align: left; padding: 2px 0; border: none;">CGST :</td>
                <td style="text-align: right; padding: 2px 0; border: none; padding-right: 4px;">{{ number_format($cgst, 2) }}</td>
                <td style="text-align: left; padding: 2px 0; border: none; padding-left: 6px; border-left: 1px dotted #000;">Card :</td>
                <td style="text-align: right; padding: 2px 0; border: none;">{{ number_format($paymentCard, 2) }}</td>
            </tr>
            <!-- Row 4 (Totals/Balance) with solid borders -->
            <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000;">
                <td style="text-align: left; padding: 3px 0; border: none;">TOTAL :</td>
                <td style="text-align: right; padding: 3px 0; border: none; padding-right: 4px;">{{ number_format($totalTax, 2) }}</td>
                <td style="text-align: left; padding: 3px 0; border: none; padding-left: 6px; border-left: 1px dotted #000;">BALENCE :</td>
                <td style="text-align: right; padding: 3px 0; border: none;">{{ number_format($balance, 2) }}</td>
            </tr>
        </table>

        {{-- Terms & Conditions Footer --}}
        <div style="text-align: left; font-size: 7px; font-weight: bold; line-height: 1.3;">
            <div style="font-size: 7.5px; font-weight: bold; margin-bottom: 2px;">TERMS & CONDITION</div>
            <div>-> KEEP THE PRODUCT AWAY FROM PERFUME, WATER AND CHEMICALS.</div>
            <div>-> ITEM THAT CAN BE REPAIRED WILL BE REPAIRED, CHARGEABLE.</div>
            <div>-> NO RETURN, NO EXCHANGE.</div>
            <div>-> NO GUARANTEE ON POLISH.</div>
        </div>

        <div class="divider-dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>

        {{-- Monospaced Thank You Note --}}
        <div style="text-align: center; font-family: Courier, monospace; font-size: 7.5px; font-weight: bold; line-height: 1.4;">
            <div>Thank you for shopping with us!</div>
        </div>

    </div>

</body>
</html>
