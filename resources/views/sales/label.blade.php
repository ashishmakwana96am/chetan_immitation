<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Shipping Label - {{ $order->order_no }}</title>
    <style>
        @page {
            size: 288pt 432pt;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 288pt;
            height: 432pt;
            overflow: hidden;
            font-family: Arial, sans-serif;
            font-size: 6px;
            color: #000;
            background: #fff;
        }

        body {
            padding: 5pt 6pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            vertical-align: top;
        }

        .label-container {
            width: 276pt;
            height: 422pt;
            position: relative;
            background: #fff;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .shipping-label-card {
            width: 100%;
            border: 1px solid #000;
        }

        .address-table {
            table-layout: fixed;
            border-bottom: 1px solid #000;
        }

        .address-table td {
            width: 50%;
            padding: 6px 8px 8px;
            line-height: 1.2;
        }

        .address-table td:first-child {
            border-right: 1px solid #000;
        }

        .section-title {
            margin-bottom: 6px;
            font-size: 6px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .person-name {
            margin-bottom: 5px;
            font-size: 7.5px;
            font-weight: bold;
        }

        .address-text {
            font-size: 6px;
            line-height: 1.2;
        }

        .phone-text {
            margin-top: 6px;
            font-size: 6px;
            font-weight: bold;
        }

        .product-section {
            padding: 7px 7px 5px;
        }

        .product-title {
            margin-bottom: 5px;
            font-size: 7.2px;
            font-weight: bold;
        }

        .product-table {
            font-size: 6.2px;
            line-height: 1.25;
            table-layout: fixed;
        }

        .product-table th {
            padding: 0 2px 3px;
            border-bottom: 1px dashed #000;
            font-weight: bold;
            text-align: left;
        }

        .product-table td {
            padding: 2.5px 2px;
            font-family: Arial, sans-serif;
        }

        .fold-here {
            width: 100%;
            height: 13pt;
            padding-top: 6pt;
            text-align: center;
            color: #555;
            font-size: 5px;
            line-height: 1;
        }

        .fold-here-line {
            width: 100%;
            border-top: 1px dotted #777;
        }

        .fold-here-text {
            position: relative;
            top: -4px;
            display: inline-block;
            padding: 0 7px;
            background: #fff;
        }

        .tax-invoice-card {
            width: 100%;
            border: 1px solid #000;
        }

        .invoice-header {
            position: relative;
            height: 10pt;
            border-bottom: 1px solid #000;
            line-height: 10pt;
        }

        .invoice-title {
            width: 100%;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
        }

        .invoice-copy {
            position: absolute;
            top: 0;
            right: 4px;
            font-size: 4.8px;
            font-weight: bold;
            color: #333;
            text-align: right;
        }

        .metadata-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 5.3px;
        }

        .metadata-table td {
            height: 39pt;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px;
            line-height: 1.16;
        }

        .metadata-table td:last-child {
            border-right: 0;
        }

        .metadata-title {
            margin-bottom: 2px;
            font-size: 5.8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .invoice-info-table {
            margin-top: 2px;
            table-layout: fixed;
            font-size: 4.6px;
            line-height: 1.08;
        }

        .invoice-info-table td {
            height: auto;
            border: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .invoice-items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 4.75px;
            line-height: 1.12;
        }

        .invoice-items-table th,
        .invoice-items-table td {
            border: 1px solid #000;
            border-top: 0;
            padding: 1.8px;
            vertical-align: middle;
        }

        .invoice-items-table th:first-child,
        .invoice-items-table td:first-child {
            border-left: 0;
        }

        .invoice-items-table th:last-child,
        .invoice-items-table td:last-child {
            border-right: 0;
        }

        .invoice-items-table th {
            background: #fff;
            font-weight: bold;
        }

        .total-row td {
            border-bottom: 1px solid #000;
            background: #fff;
            font-weight: bold;
        }

        .invoice-footer {
            padding: 4px;
            color: #333;
            font-size: 4.8px;
            line-height: 1.2;
        }
    </style>
</head>
<body>

<div class="label-container">
    @php
        $addr = $order->customerAddress;
        $totalQty = $order->items->sum('quantity');

        $placeOfSupply = $addr->state ?? '';
    @endphp

    {{-- TOP SHIPPING LABEL --}}
    <div class="shipping-label-card">
        <table class="address-table">
            <tr>
                <td>
                    <div class="section-title">TO</div>
                    <div class="person-name">{{ $addr->name ?? ($order->customer->name ?? 'Walk-in Customer') }}</div>
                    <div class="address-text">
                        @if($order->customerAddress)
                            {{ $addr->address }}<br>
                            {{ $addr->city }}, {{ $addr->state }}, {{ $addr->pincode }}
                        @else
                            No shipping address provided.
                        @endif
                    </div>
                    <div class="phone-text">
                        Phone: {{ $addr->phone ?? ($order->customer->phone ?? '-') }}
                        @if(!empty($addr->alternate_phone))
                            / {{ $addr->alternate_phone }}
                        @endif
                    </div>
                </td>
                <td>
                    <div class="section-title">FROM</div>
                    <div class="person-name">{{ $order->location?->name ?? 'Chetan Imitation' }}</div>
                    <div class="address-text">
                        @if($order->location?->address)
                            {{ $order->location->address }}
                        @else
                            Surat Retail Outlet<br>
                            10, Commercial Plaza, Main Road, Surat, Gujarat - 395006
                        @endif
                    </div>
                    <div class="phone-text">Phone: {{ $order->location?->phone ?? '+91 77259 78871' }}</div>
                </td>
            </tr>
        </table>

        <div class="product-section">
            <div class="product-title">Product Details</div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th style="width: 35%;">SKU</th>
                        <th style="width: 16%;">Size</th>
                        <th style="width: 10%;" class="text-center">Qty</th>
                        <th style="width: 15%;">Color</th>
                        <th style="width: 24%;" class="text-right">Order No.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $idx => $item)
                        @php
                            $size = '-';
                            $color = '-';
                            if ($item->variant && $item->variant->attributeValue) {
                                $attrVal = $item->variant->attributeValue->value;
                                $attrName = $item->variant->attributeValue->attribute->name ?? '';
                                if (str_contains($attrVal, ' - ')) {
                                    $parts = explode(' - ', $attrVal);
                                    $size = trim($parts[0]);
                                    $color = trim($parts[1] ?? '-');
                                } elseif (strtolower($attrName) === 'size') {
                                    $size = $attrVal;
                                } elseif (strtolower($attrName) === 'colour' || strtolower($attrName) === 'color') {
                                    $color = $attrVal;
                                } else {
                                    $size = $attrVal;
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $item->product->sku ?? '-' }}</td>
                            <td>{{ $size }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td>{{ $color }}</td>
                            <td class="text-right">{{ $order->order_no }}{{ count($order->items) > 1 ? '_' . ($idx + 1) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Fold separator --}}
    <div class="fold-here">
        <div class="fold-here-line">
            <span class="fold-here-text">Fold Here</span>
        </div>
    </div>

    {{-- BOTTOM TAX INVOICE --}}
    <div class="tax-invoice-card">
        <div class="invoice-header">
            <div class="invoice-title">TAX INVOICE</div>
            <div class="invoice-copy">Original For Recipient</div>
        </div>

        {{-- Address cards: BILL TO, SHIP TO, Sold By --}}
        <table class="metadata-table">
            <tr>
                {{-- BILL TO --}}
                <td style="width: 32%;">
                    <div class="metadata-title">BILL TO</div>
                    <div class="fw-bold">{{ $addr->name ?? ($order->customer->name ?? 'Walk-in Customer') }}</div>
                    <div>
                        @if($order->customerAddress)
                            {{ $addr->address }}<br>
                            {{ $addr->city }}, {{ $addr->state }}, {{ $addr->pincode }}
                        @else
                            No billing address provided.
                        @endif
                    </div>
                    <div class="fw-bold" style="margin-top: 2px;">Place of Supply: {{ $placeOfSupply }}</div>
                </td>

                {{-- SHIP TO --}}
                <td style="width: 32%;">
                    <div class="metadata-title">SHIP TO</div>
                    <div class="fw-bold">{{ $addr->name ?? ($order->customer->name ?? 'Walk-in Customer') }}</div>
                    <div>
                        @if($order->customerAddress)
                            {{ $addr->address }}<br>
                            {{ $addr->city }}, {{ $addr->state }}, {{ $addr->pincode }}
                        @else
                            No shipping address provided.
                        @endif
                    </div>
                </td>

                {{-- Sold by and invoice info --}}
                <td style="width: 36%;">
                    <div>
                        <strong>Sold by :</strong> {{ $order->location?->name ?? 'SHIV SHARMA' }}, {{ $order->location?->address ?? 'surat, Gujarat, 395006' }}<br>
                        <strong>GSTIN -</strong> 24FDGPS3370P1ZW
                    </div>
                    <table class="invoice-info-table">
                        <tr>
                            <td class="fw-bold" style="width: 50%;">Purchase Order No.</td>
                            <td class="fw-bold" style="width: 50%;">Invoice No.</td>
                        </tr>
                        <tr>
                            <td>{{ $order->order_no }}</td>
                            <td>gemt{{ $order->id + 9242800 }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Order Date</td>
                            <td class="fw-bold">Invoice Date</td>
                        </tr>
                        <tr>
                            <td>{{ format_date($order->created_at, 'd.m.Y') }}</td>
                            <td>{{ format_date($order->created_at, 'd.m.Y') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Invoice items table --}}
        <table class="invoice-items-table">
            <thead>
                <tr>
                    <th style="width: 31%; text-align: left;">Description</th>
                    <th style="width: 7%;" class="text-center">HSN</th>
                    <th style="width: 5%;" class="text-center">Qty</th>
                    <th style="width: 12%;" class="text-right">Gross Amount</th>
                    <th style="width: 9%;" class="text-right">Discount</th>
                    <th style="width: 13%;" class="text-right">Taxable Value</th>
                    <th style="width: 11%;" class="text-right">Taxes</th>
                    <th style="width: 12%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $taxRate = 0.00; // 0% GST
                    $totalTaxable = 0;
                    $totalTaxes = 0;
                    $totalGross = 0;
                    $totalDiscount = 0;
                    $totalFinal = 0;
                @endphp
                @foreach($order->items as $item)
                    @php
                        $itemGross = (float)$item->price * (float)$item->quantity;
                        $itemDiscount = (float)$item->discount_amount;
                        $itemFinal = (float)$item->total;

                        $itemTaxable = $itemFinal; // Since tax is 0%, taxable value is equal to final total
                        $itemTax = 0.00;

                        $totalGross += $itemGross;
                        $totalDiscount += $itemDiscount;
                        $totalTaxable += $itemTaxable;
                        $totalTaxes += $itemTax;
                        $totalFinal += $itemFinal;

                        $size = '';
                        if ($item->variant && $item->variant->attributeValue) {
                            $attrVal = $item->variant->attributeValue->value;
                            if (str_contains($attrVal, ' - ')) {
                                $parts = explode(' - ', $attrVal);
                                $size = ' - ' . trim($parts[0]);
                            } else {
                                $size = ' - ' . $attrVal;
                            }
                        }
                    @endphp
                    <tr>
                        <td style="text-align: left;">{{ $item->product->name }}{{ $size }}</td>
                        <td class="text-center">6211</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">Rs.{{ number_format($itemGross, 2) }}</td>
                        <td class="text-right">Rs.{{ number_format($itemDiscount, 2) }}</td>
                        <td class="text-right">Rs.{{ number_format($itemTaxable, 2) }}</td>
                        <td class="text-right">
                            GST @0%<br>Rs.0.00
                        </td>
                        <td class="text-right fw-bold">Rs.{{ number_format($itemFinal, 2) }}</td>
                    </tr>
                @endforeach

                {{-- Other / Shipping Charges --}}
                @if($order->shipping_charge > 0)
                    @php
                        $shipGross = (float)$order->shipping_charge;
                        $shipTaxable = $shipGross; // Since tax is 0%
                        $shipTax = 0.00;

                        $totalGross += $shipGross;
                        $totalTaxable += $shipTaxable;
                        $totalTaxes += $shipTax;
                        $totalFinal += $shipGross;
                    @endphp
                    <tr>
                        <td style="text-align: left;">Other Charges</td>
                        <td class="text-center">6211</td>
                        <td class="text-center">NA</td>
                        <td class="text-right">Rs.{{ number_format($shipGross, 2) }}</td>
                        <td class="text-right">Rs.0.00</td>
                        <td class="text-right">Rs.{{ number_format($shipTaxable, 2) }}</td>
                        <td class="text-right">
                            GST @0%<br>Rs.0.00
                        </td>
                        <td class="text-right fw-bold">Rs.{{ number_format($shipGross, 2) }}</td>
                    </tr>
                @endif

                {{-- Total row --}}
                <tr class="total-row">
                    <td colspan="3" style="text-align: left;">Total</td>
                    <td class="text-right">Rs.{{ number_format($totalGross, 2) }}</td>
                    <td class="text-right">Rs.{{ number_format($totalDiscount, 2) }}</td>
                    <td class="text-right">Rs.{{ number_format($totalTaxable, 2) }}</td>
                    <td class="text-right">Rs.{{ number_format($totalTaxes, 2) }}</td>
                    <td class="text-right">Rs.{{ number_format($totalFinal, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Disclaimer footer --}}
        <div class="invoice-footer">
            Tax is not payable on reverse charge basis. This is a computer generated invoice and does not require signature. Other charges are charges that are applicable to your order and include charges for logistics fee (where applicable). Includes discounts for your city and/or for online payments (as applicable)
        </div>
    </div>
</div>

</body>
</html>
