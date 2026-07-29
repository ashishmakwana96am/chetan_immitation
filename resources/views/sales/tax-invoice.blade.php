<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice {{ $order->order_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
        }
        .invoice {
            width: 100%;
            border: 1px solid #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: top;
        }
        .no-border { border: none !important; }
        .border-left { border-left: 1px solid #000 !important; }
        .border-right { border-right: 1px solid #000 !important; }
        .border-top { border-top: 1px solid #000 !important; }
        .border-bottom { border-bottom: 1px solid #000 !important; }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 6px 0;
        }
        .company {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            padding: 5px 0 2px;
        }
        .company-sub {
            text-align: center;
            font-size: 11px;
            line-height: 1.35;
            padding-bottom: 4px;
        }
        .label { font-weight: bold; }
        .section-title {
            font-weight: bold;
            background: #f2f2f2;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .nowrap { white-space: nowrap; }
        .items th {
            text-align: center;
            font-size: 10px;
            line-height: 1.15;
            padding: 5px 4px;
            background: #f8f8f8;
        }
        .items td {
            font-size: 10.5px;
            line-height: 1.25;
            height: 25px;
            vertical-align: middle;
        }
        .items tbody td {
            border-top: none !important;
            border-bottom: none !important;
        }
        .items tbody tr:last-child td {
            border-bottom: 1px solid #000 !important;
        }
        .items .product {
            font-weight: bold;
            text-transform: uppercase;
        }
        .summary td {
            height: 22px;
        }
        .amount-words {
            height: 44px;
            line-height: 1.45;
        }
        .terms {
            height: 92px;
            line-height: 1.45;
        }
        .sign-box {
            height: 92px;
            text-align: center;
            vertical-align: bottom;
            font-weight: bold;
        }
        .ship-box {
            height: 82px;
            position: relative;
        }
        .ship-lr {
            margin-top: 54px;
            line-height: 1.35;
        }
    </style>
</head>
<body>
@php
    $setting = \App\Models\Setting::class;

    $companyName = 'CHETAN IMITATION';
    $companyGst = $order->location?->gst_number ?: '24SCOPS0159A1ZB';
    $companyAddress = trim(($order->location?->name ? strtoupper($order->location->name) : 'KATARGAM') . ($order->location?->address ? ', ' . $order->location->address : ''));
    $companyPhone = $order->location?->phone ?: '7725978871';

    $customerName = $order->customer?->name ?: 'Walk-in Customer';
    $addr = $order->customerAddress;
    $customerPhone = $order->customer?->phone ?: ($addr?->phone ?: '-');
    $customerGst = $order->customer?->gst_no ?: '-';
    $customerAddress = $addr
        ? collect([$addr->address, $addr->city, $addr->state, $addr->pincode])->filter()->implode(', ')
        : ($order->customer?->address ?: '-');
    $stateName = $order->customer?->state ?: ($addr?->state ?: $setting::getValue('store_state', 'Gujarat'));
    $stateCode = $customerGst !== '-' ? substr($customerGst, 0, 2) : ($companyGst ? substr($companyGst, 0, 2) : '24');

    $grossAmount = $order->items->sum(fn($item) => (float) $item->price * (float) $item->quantity);
    $itemDiscount = (float) $order->items->sum('discount_amount');

    $orderDiscountAmount = 0.0;
    if ((float) $order->order_discount_value > 0) {
        $itemsTotal = $grossAmount - $itemDiscount;
        if ($order->order_discount_type === 'flat') {
            $orderDiscountAmount = (float) $order->order_discount_value;
        } elseif ($order->order_discount_type === 'percentage') {
            $orderDiscountAmount = $itemsTotal * ((float) $order->order_discount_value / 100);
        }
        $orderDiscountAmount = min($orderDiscountAmount, $itemsTotal);
    }

    $couponDiscount = 0.0;
    if ($order->coupon_id && $order->coupon) {
        $couponDiscount = max(0, round($grossAmount - $itemDiscount - ((float) $order->final_amount - (float) $order->shipping_charge), 2));
    }

    $totalDiscount = $itemDiscount + $orderDiscountAmount + $couponDiscount;
    $extraDiscount = $orderDiscountAmount + $couponDiscount;
    $itemsNetTotal = max(0, $grossAmount - $itemDiscount);

    $gstRate = (float) $setting::getValue('purchase_gst_rate', 3);
    $taxAmount = $order->is_gst ? (float) $order->tax_amount : 0.0;
    $shipping = (float) $order->shipping_charge;
    $taxableAmount = max(0, $grossAmount - $totalDiscount);

    $calculatedTotal = max(0, $grossAmount - $totalDiscount) + $taxAmount + $shipping;
    $roundedOff = round((float) $order->final_amount - $calculatedTotal, 2);

    $buyerState = strtolower(trim($stateName));
    $storeState = strtolower(trim($setting::getValue('store_state', 'Gujarat')));
    $isIntraState = $buyerState === '' || $buyerState === $storeState;
    $cgst = $order->is_gst && $isIntraState ? $taxAmount / 2 : 0;
    $sgst = $order->is_gst && $isIntraState ? $taxAmount / 2 : 0;
    $igst = $order->is_gst && ! $isIntraState ? $taxAmount : 0;
    $halfRate = $gstRate / 2;

    $totalQty = $order->items->sum('quantity');
@endphp

<div class="invoice">
    <div class="title">TAX INVOICE</div>

    <div class="company">{{ $companyName }}</div>
    <div class="company-sub">
        {{ $companyAddress }}<br>
        <span class="label">Company GSTIN :</span> {{ $companyGst }}
    </div>

    <table>
        <tr>
            <td style="width: 36%;"><span class="label">Invoice No.</span> : {{ $order->order_no }}</td>
            <td style="width: 32%;"><span class="label">Phone No</span> : {{ $companyPhone }}</td>
            <td style="width: 32%;"><span class="label">Date of Supply</span> : {{ $order->created_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><span class="label">Invoice Date</span> : {{ $order->created_at->format('d/m/Y') }}</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="section-title text-center" style="width: 50%;">Billed to:</td>
            <td class="section-title text-center" style="width: 50%;">Shipping to:</td>
        </tr>
        <tr>
            <td style="width: 50%;">
                <span class="label">Name</span> : {{ strtoupper($customerName) }}<br>
                <span class="label">Address</span> : {{ strtoupper($customerAddress) }}<br>
                <span class="label">Phone</span> : {{ $customerPhone }}<br>
                <span class="label">GSTIN</span> : {{ $customerGst }}<br>
                <span class="label">State</span> : {{ $stateName }}
            </td>
            <td class="ship-box" style="width: 50%;">
                <div class="ship-lr">
                    <span class="label">Lr No</span> :<br>
                    <span class="label">Lr No Date</span> :
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;">SNo</th>
                <th style="width: 24%;">Name of Product</th>
                <th style="width: 9%;">HSN CODE</th>
                <th style="width: 7%;">Qty</th>
                <th style="width: 9%;">Rate</th>
                <th style="width: 10%;">Amount</th>
                <th style="width: 8%;">Disc</th>
                <th style="width: 10%;">G.Value</th>
                @if($isIntraState)
                    <th style="width: 6%;">CGST%</th>
                    <th style="width: 6%;">SGST%</th>
                @else
                    <th style="width: 12%;" colspan="2">IGST%</th>
                @endif
                <th style="width: 6%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
                @php
                    $lineAmount = (float) $item->price * (float) $item->quantity;
                    $lineDiscount = (float) $item->discount_amount;
                    $lineGrossValue = max(0, $lineAmount - $lineDiscount);
                    $lineTax = $order->is_gst ? round($lineGrossValue * ($gstRate / 100), 2) : 0.0;
                    $lineFinalAmount = $lineGrossValue + $lineTax;
                    $productName = 'IMITATION';
                    $hsn = '7117';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="product">{{ $productName }}</td>
                    <td class="text-center">{{ $hsn }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="text-right">{{ number_format((float) $item->price, 2) }}</td>
                    <td class="text-right">{{ number_format($lineAmount, 2) }}</td>
                    <td class="text-right">{{ number_format($lineDiscount, 2) }}</td>
                    <td class="text-right">{{ number_format($lineGrossValue, 2) }}</td>
                    @if($isIntraState)
                        <td class="text-center">{{ $cgst > 0 ? rtrim(rtrim(number_format($halfRate, 2), '0'), '.') : '0.00' }}</td>
                        <td class="text-center">{{ $sgst > 0 ? rtrim(rtrim(number_format($halfRate, 2), '0'), '.') : '0.00' }}</td>
                    @else
                        <td class="text-center" colspan="2">{{ $igst > 0 ? rtrim(rtrim(number_format($gstRate, 2), '0'), '.') : '0.00' }}</td>
                    @endif
                    <td class="text-right">{{ number_format($lineFinalAmount, 2) }}</td>
                </tr>
            @endforeach
            @php
                $itemCount = count($order->items);
                $blankHeight = max(30, 420 - ($itemCount * 28));
            @endphp
            @if($itemCount < 12)
                <tr class="blank-row">
                    <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                    <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                    <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                    <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                    <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                    <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                    <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                    <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                    @if($isIntraState)
                        <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                    @else
                        <td style="height: {{ $blankHeight }}px;" colspan="2">&nbsp;</td>
                    @endif
                    <td style="height: {{ $blankHeight }}px;">&nbsp;</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table>
        <tr>
            <td style="width: 58%; vertical-align: top; padding: 8px;">
                <span class="label">Total Qty:</span> {{ rtrim(rtrim(number_format((float) $totalQty, 2), '0'), '.') }}<br>
                <span class="label">Payment:</span>
                @if($order->payment_method === 'online_cash')
                    Cash: {{ format_price($order->paid_cash_amount) }}, Online: {{ format_price($order->paid_online_amount) }}
                @else
                    {{ ucwords(str_replace('_', ' ', $order->payment_method ?? '-')) }}
                @endif
            </td>
            <td style="width: 42%; padding: 0;">
                <table class="summary">
                    <tr>
                        <td class="label">Gross Amount</td>
                        <td class="text-right">{{ number_format($grossAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Total Dis Amount</td>
                        <td class="text-right">{{ number_format($totalDiscount, 2) }}</td>
                    </tr>
                    @if($isIntraState)
                        <tr>
                            <td class="label">CGST Amount</td>
                            <td class="text-right">{{ number_format($cgst, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label">SGST Amount</td>
                            <td class="text-right">{{ number_format($sgst, 2) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="label">IGST Amount</td>
                            <td class="text-right">{{ number_format($igst, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Shipping Charges</td>
                        <td class="text-right">{{ number_format($shipping, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Rounded Off</td>
                        <td class="text-right">{{ number_format($roundedOff, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label" style="font-size: 13px;">Net Amount</td>
                        <td class="text-right label" style="font-size: 13px;">{{ number_format((float) $order->final_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="terms" style="width: 58%;">
                <span class="label">Terms and Conditions :</span><br>
                1. Goods once sold will not be taken back.<br>
                2. Subject to Surat jurisdiction.<br>
                3. This is a computer generated invoice.
            </td>
            <td class="sign-box" style="width: 42%;">
                For {{ $companyName }}<br><br><br><br>
                Authorized Signatory
            </td>
        </tr>
    </table>
</div>
</body>
</html>
