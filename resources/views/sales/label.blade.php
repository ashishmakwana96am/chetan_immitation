<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Shipping Label - {{ $order->order_no }}</title>
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
            font-size: 7px;
        }

        .label-container {
            width: 200pt;
            position: relative;
        }

        .fw-bold { font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .uppercase { text-transform: uppercase; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            vertical-align: top;
        }

        .divider-dotted {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .divider-solid {
            border-top: 1px solid #000;
            margin: 5px 0;
        }

        .doc-title {
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title {
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .person-name {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .address-text {
            font-size: 7px;
            line-height: 1.3;
        }

        .phone-text {
            font-size: 7px;
            font-weight: bold;
            margin-top: 3px;
        }

        .address-block {
            margin-bottom: 6px;
        }

        .product-title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .product-table {
            font-size: 6.8px;
            line-height: 1.3;
        }

        .product-table th {
            padding: 2px 1px;
            border-bottom: 1px solid #000;
            font-weight: bold;
            text-align: center;
        }

        .product-table td {
            padding: 2.5px 1px;
            text-align: center;
        }

        .invoice-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 6.8px;
            line-height: 1.35;
        }

        .invoice-info-row .label {
            font-weight: bold;
        }

        .items-table {
            font-size: 6.8px;
            line-height: 1.3;
            margin-top: 3px;
        }

        .items-table th {
            padding: 2px 1px;
            border-bottom: 1px solid #000;
            font-weight: bold;
            text-align: center;
        }

        .items-table td {
            padding: 2.5px 1px;
            vertical-align: top;
        }

        .item-note {
            font-size: 6px;
            color: #333;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 7px;
            padding: 1.5px 0;
        }

        .summary-row.total {
            font-weight: bold;
            border-top: 1px solid #000;
            margin-top: 2px;
            padding-top: 3px;
        }

        .invoice-footer {
            font-size: 5.8px;
            line-height: 1.35;
            color: #333;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<div class="label-container">
    @php
        $addr = $order->customerAddress;
        $placeOfSupply = $addr->state ?? '';
        $custName = $addr->name ?? ($order->customer->name ?? 'Walk-in Customer');
    @endphp

    {{-- ================= SHIPPING LABEL ================= --}}
    <div class="doc-title">Shipping Label</div>
    <div class="divider-solid"></div>

    <div class="address-block">
        <div class="section-title">To</div>
        <div class="person-name">{{ $custName }}</div>
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
    </div>

    <div class="address-block">
        <div class="section-title">From</div>
        <div class="person-name">CHETAN IMITATION{{ $order->location?->name ? ' - ' . $order->location->name : '' }}</div>
        <div class="address-text">
            @if($order->location?->address)
                {{ $order->location->address }}
            @else
                Surat Retail Outlet<br>
                10, Commercial Plaza, Main Road, Surat, Gujarat - 395006
            @endif
        </div>
        <div class="phone-text">Phone: {{ $order->location?->phone ?? '+91 77259 78871' }}</div>
    </div>

    <div class="divider-dotted"></div>

    <div class="product-title">Product Details</div>
    <table class="product-table">
        <thead>
            <tr>
                <th style="width: 32%; text-align: left;">Barcode</th>
                <th style="width: 16%;">Size</th>
                <th style="width: 16%;">Color</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 26%;">Order No.</th>
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
                    <td style="text-align: left;">{{ $item->product->barcode ?? '-' }}</td>
                    <td>{{ $size }}</td>
                    <td>{{ $color }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $order->order_no }}{{ count($order->items) > 1 ? '_' . ($idx + 1) : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider-solid" style="margin-top: 8px;"></div>

    {{-- ================= TAX INVOICE ================= --}}
    <div class="doc-title">Tax Invoice</div>
    <div class="text-center" style="font-size: 6px; margin-bottom: 3px;">Original For Recipient</div>
    <div class="divider-dotted"></div>

    <div class="address-block">
        <div class="section-title">Bill To</div>
        <div class="fw-bold address-text">{{ $custName }}</div>
        <div class="address-text">
            @if($order->customerAddress)
                {{ $addr->address }}<br>
                {{ $addr->city }}, {{ $addr->state }}, {{ $addr->pincode }}
            @else
                No billing address provided.
            @endif
        </div>
        <div class="fw-bold address-text" style="margin-top: 2px;">Place of Supply: {{ $placeOfSupply }}</div>
    </div>

    <div class="address-block">
        <div class="section-title">Ship To</div>
        <div class="fw-bold address-text">{{ $custName }}</div>
        <div class="address-text">
            @if($order->customerAddress)
                {{ $addr->address }}<br>
                {{ $addr->city }}, {{ $addr->state }}, {{ $addr->pincode }}
            @else
                No shipping address provided.
            @endif
        </div>
    </div>

    <div class="address-block">
        <div class="section-title">Sold By</div>
        <div class="address-text">
            CHETAN IMITATION{{ $order->location?->name ? ' - ' . $order->location->name : '' }}, {{ $order->location?->address ?? 'Surat, Gujarat, 395006' }}
            @if((($order->source ?? 'POS') !== 'ONLINE') && $order->location?->gst_number)
                <br><strong>GSTIN:</strong> {{ $order->location->gst_number }}
            @endif
        </div>
    </div>

    <table style="width: 100%; font-size: 6.8px; line-height: 1.35; margin-bottom: 2px; border-collapse: collapse;">
        <tr>
            <td style="font-weight: bold; text-align: left; padding: 1px 0;">Purchase Order No.</td>
            <td style="text-align: right; padding: 1px 0;">{{ $order->order_no }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; text-align: left; padding: 1px 0;">Order Date</td>
            <td style="text-align: right; padding: 1px 0;">{{ format_date($order->created_at, 'd.m.Y') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; text-align: left; padding: 1px 0;">Invoice Date</td>
            <td style="text-align: right; padding: 1px 0;">{{ format_date($order->created_at, 'd.m.Y') }}</td>
        </tr>
    </table>

    <div class="divider-dotted"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 46%; text-align: left;">Description</th>
                <th style="width: 12%;">Qty</th>
                <th style="width: 21%; text-align: right;">Price</th>
                <th style="width: 21%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $gstRate = $order->is_gst ? (float) \App\Models\Setting::getValue('purchase_gst_rate', 3) : 0;
                $customerState = strtolower(trim($order->customerAddress->state ?? ''));
                $storeState = strtolower(trim(\App\Models\Setting::getValue('store_state', 'gujarat')));
                $isGujarat = ($customerState === '' || $customerState === $storeState);

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
                    
                    // Inclusive GST Calculation
                    $itemTax = $itemFinal * ($gstRate / (100 + $gstRate));
                    $itemTaxable = $itemFinal - $itemTax;

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
                    <td style="text-align: left;">
                        {{ $item->product->name }}{{ $size }}
                        @if($itemDiscount > 0)
                            <div class="item-note">Discount: Rs.{{ number_format($itemDiscount, 2) }}@if($gstRate > 0) | GST @{{ $gstRate }}%@endif</div>
                        @elseif($gstRate > 0)
                            <div class="item-note">GST @{{ $gstRate }}%</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rs.{{ number_format($item->price, 2) }}</td>
                    <td class="text-right fw-bold">Rs.{{ number_format($itemFinal, 2) }}</td>
                </tr>
            @endforeach

            @if($order->shipping_charge > 0)
                @php
                    $shipGross = (float)$order->shipping_charge;
                    $totalGross += $shipGross;
                    $totalTaxable += $shipGross;
                    $totalFinal += $shipGross;
                @endphp
                <tr>
                    <td style="text-align: left;">
                        Shipping Charge
                        @if($gstRate > 0)
                        <div class="item-note">GST @0%</div>
                        @endif
                    </td>
                    <td class="text-center">-</td>
                    <td class="text-right">Rs.{{ number_format($shipGross, 2) }}</td>
                    <td class="text-right fw-bold">Rs.{{ number_format($shipGross, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="divider-dotted"></div>

    <table style="width: 100%; font-size: 7px; line-height: 1.4; border-collapse: collapse; margin-top: 3px;">
        <tr>
            <td style="text-align: left; padding: 1.5px 0;">Gross Amount</td>
            <td style="text-align: right; padding: 1.5px 0;">Rs.{{ number_format($totalGross, 2) }}</td>
        </tr>
        <tr>
            <td style="text-align: left; padding: 1.5px 0;">Discount</td>
            <td style="text-align: right; padding: 1.5px 0;">Rs.{{ number_format($totalDiscount, 2) }}</td>
        </tr>
        @if($gstRate > 0)
        <tr>
            <td style="text-align: left; padding: 1.5px 0;">Taxable Value</td>
            <td style="text-align: right; padding: 1.5px 0;">Rs.{{ number_format($totalTaxable, 2) }}</td>
        </tr>
        @if($isGujarat)
            <tr>
                <td style="text-align: left; padding: 1.5px 0;">CGST ({{ $gstRate / 2 }}%)</td>
                <td style="text-align: right; padding: 1.5px 0;">Rs.{{ number_format($totalTaxes / 2, 2) }}</td>
            </tr>
            <tr>
                <td style="text-align: left; padding: 1.5px 0;">SGST ({{ $gstRate / 2 }}%)</td>
                <td style="text-align: right; padding: 1.5px 0;">Rs.{{ number_format($totalTaxes / 2, 2) }}</td>
            </tr>
        @else
            <tr>
                <td style="text-align: left; padding: 1.5px 0;">IGST ({{ $gstRate }}%)</td>
                <td style="text-align: right; padding: 1.5px 0;">Rs.{{ number_format($totalTaxes, 2) }}</td>
            </tr>
        @endif
        @endif
        <tr style="font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000;">
            <td style="text-align: left; padding: 3px 0; font-size: 8px;">Total</td>
            <td style="text-align: right; padding: 3px 0; font-size: 8px;">Rs.{{ number_format($totalFinal, 2) }}</td>
        </tr>
    </table>

    <div class="divider-dotted"></div>

    <div class="invoice-footer">
        Tax is not payable on reverse charge basis. This is a computer generated invoice and does not require signature. Other charges are charges that are applicable to your order and include charges for logistics fee (where applicable). Includes discounts for your city and/or for online payments (as applicable).
    </div>
</div>

</body>
</html>
