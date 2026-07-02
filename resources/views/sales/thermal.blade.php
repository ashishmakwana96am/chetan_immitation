<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Sale - {{ $order->order_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace; /* Classic receipt font */
        }
        body {
            background: #fff;
            color: #000;
            font-size: 12px;
            line-height: 1.4;
            width: 80mm; /* Standard receipt width */
            padding: 4mm;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .header {
            margin-bottom: 8px;
        }
        .store-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .store-tagline {
            font-size: 10px;
            color: #333;
            margin-bottom: 4px;
        }
        .store-info {
            font-size: 11px;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        
        .details-table {
            width: 100%;
            font-size: 11px;
            margin-bottom: 6px;
        }
        .details-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        .items-table th {
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            padding-bottom: 4px;
        }
        .items-table td {
            font-size: 11px;
            padding: 3px 0;
            vertical-align: top;
        }
        
        .totals-table {
            width: 100%;
            margin-top: 4px;
        }
        .totals-table td {
            padding: 2px 0;
            font-size: 11px;
        }
        .grand-total {
            font-size: 13px;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 15px;
            font-size: 10px;
        }
        
        @media print {
            body {
                width: 80mm;
                padding: 2mm;
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
        
        /* Float Button for Manual Trigger if browser blocks auto-print */
        .print-btn-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
        .print-btn {
            background: #B4771E;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
            font-family: sans-serif;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @php
        // Resolve totals
        $totalItemDiscount = $order->items->sum('discount_amount');
        $itemsGross        = $order->items->sum(fn($i) => (float)$i->price * (float)$i->quantity);
        $subtotal          = $itemsGross;
        
        $couponDiscount = 0;
        if ($order->coupon_id && $order->coupon) {
            $couponDiscount = max(0, round($subtotal - $totalItemDiscount - (float)$order->final_amount, 2));
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

    <div class="print-btn-container no-print">
        <button class="print-btn" onclick="window.print();">Print Invoice</button>
    </div>

    <div class="header text-center">
        <div class="store-name">Chetan Imitation</div>
        <div class="store-tagline">Jewellery Manufacturer</div>
        <div class="store-info">
            @if($order->location)
                {{ $order->location->name }}<br>
                @if($order->location->address)
                    {{ $order->location->address }}<br>
                @endif
                @if($order->location->phone)
                    Phone: {{ $order->location->phone }}
                @endif
            @else
                Surat Retail Outlet
            @endif
        </div>
    </div>
    
    <div class="divider"></div>
    
    <table class="details-table">
        <tr>
            <td style="width: 35%;">Order No:</td>
            <td><strong>{{ $order->order_no }}</strong></td>
        </tr>
        <tr>
            <td>Date:</td>
            <td>{{ format_date($order->created_at) }}</td>
        </tr>
        <tr>
            <td>Customer:</td>
            <td>{{ $order->customer ? $order->customer->name : 'Walk-in Customer' }}</td>
        </tr>
        @if($order->customer && $order->customer->phone && $order->customer->phone !== '-')
        <tr>
            <td>Phone:</td>
            <td>{{ $order->customer->phone }}</td>
        </tr>
        @endif

    </table>
    
    <div class="divider"></div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Item</th>
                <th style="width: 15%; text-align: right;">Qty</th>
                <th style="width: 15%; text-align: right;">Price</th>
                <th style="width: 20%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td style="padding: 4px 0; font-weight: bold; vertical-align: top;">
                        {{ $item->product->name ?? '-' }}
                        @if($item->variant && $item->variant->attributeValue)
                            <span style="font-size: 10px; font-weight: normal; color: #555; display: block; margin-top: 2px;">({{ $item->variant->attributeValue->attribute->name ?? '' }}: {{ $item->variant->attributeValue->value }})</span>
                        @endif
                    </td>
                    <td class="text-right" style="padding: 4px 0; vertical-align: top;">{{ $item->quantity }}</td>
                    <td class="text-right" style="padding: 4px 0; vertical-align: top;">₹{{ number_format($item->price, 0) }}</td>
                    <td class="text-right" style="padding: 4px 0; vertical-align: top;"><strong>₹{{ number_format($item->total, 0) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="divider"></div>
    
    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">₹{{ number_format($subtotal, 2) }}</td>
        </tr>
        @if($totalDiscount > 0)
        <tr>
            <td style="color: #000;">Total Discount:</td>
            <td class="text-right" style="color: #000;">-₹{{ number_format($totalDiscount, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td style="padding-top: 4px;">Grand Total:</td>
            <td class="text-right" style="padding-top: 4px;">₹{{ number_format($order->final_amount, 2) }}</td>
        </tr>
    </table>
    
    <div class="divider"></div>
    
    <div class="footer text-center">
        <p style="font-weight: bold; margin-bottom: 4px;">Thank you for shopping with us!</p>
        <p>Goods once sold will not be taken back or exchanged.</p>
        <p style="margin-top: 6px; font-size: 9px;">Generated on {{ now()->timezone('Asia/Kolkata')->format('d-m-Y h:i A') }}</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
            // Automatically close window after print dialog is closed
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>
</html>
