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
            font-size: 12px;
            font-weight: bold;
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
            border-top: 1.5px dashed #000;
            margin: 6px 0;
        }

        .divider-solid {
            border-top: 1.5px solid #000;
            margin: 6px 0;
        }

        .doc-title {
            font-size: 17px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title {
            font-size: 13.5px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .person-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .address-text {
            font-size: 13.5px;
            line-height: 1.35;
            font-weight: bold;
        }

        .phone-text {
            font-size: 13.5px;
            font-weight: bold;
            margin-top: 4px;
        }

        .address-block {
            margin-bottom: 8px;
            border-bottom: 1.5px solid #000;
            padding-bottom: 8px;
        }

        /* TO section - bigger for delivery boy */
        .to-section-title {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .to-person-name {
            font-size: 21px;
            font-weight: bold;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        .to-address-text {
            font-size: 18px;
            line-height: 1.4;
            font-weight: bold;
        }
        .to-phone-text {
            font-size: 17px;
            font-weight: bold;
            margin-top: 5px;
        }

        .product-title {
            font-size: 14.5px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .product-table {
            font-size: 12.5px;
            line-height: 1.3;
            font-weight: bold;
        }

        .product-table th {
            padding: 3px 2px;
            border-bottom: 1.5px solid #000;
            font-weight: bold;
            text-align: center;
        }

        .product-table td {
            padding: 4.5px 2px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="label-container">
    @php
        $addr = $order->customerAddress;
        $custName = $addr->name ?? ($order->customer->name ?? 'Walk-in Customer');
    @endphp

    {{-- ================= SHIPPING LABEL ================= --}}
    <div class="doc-title">Shipping Label</div>
    <div class="divider-solid"></div>

    <div class="address-block">
        <div class="to-section-title">TO</div>
        <div class="to-person-name">{{ $custName }}</div>
        <div class="to-address-text">
            @if($order->customerAddress)
                {{ $addr->address }}<br>
                {{ $addr->city }}, {{ $addr->state }}, {{ $addr->pincode }}
            @else
                No shipping address provided.
            @endif
        </div>
        <div class="to-phone-text">
            Phone: {{ $addr->phone ?? ($order->customer->phone ?? '-') }}
            @if(!empty($addr->alternate_phone))
                / {{ $addr->alternate_phone }}
            @endif
        </div>
    </div>

    <div class="address-block">
        <div class="section-title">From</div>
        <div class="person-name">CHETAN IMITATION{{ $order->location?->name ? ' - ' . $order->location->name : '' }}</div>
        <div class="phone-text">Phone: {{ $order->location?->phone ?? '+91 77259 78871' }}</div>
    </div>

    <div class="product-title" style="margin-top: 10px;">Product Details</div>
    <table class="product-table">
        <thead>
            <tr>
                <th style="width: 45%; text-align: left;">Barcode</th>
                <th style="width: 20%;">Qty</th>
                <th style="width: 35%;">Order No.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
                <tr>
                    <td style="text-align: left;">{{ $item->product->barcode ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $order->order_no }}{{ count($order->items) > 1 ? '_' . ($idx + 1) : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

</body>
</html>
