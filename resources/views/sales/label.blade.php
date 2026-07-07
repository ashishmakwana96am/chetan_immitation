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
        }

        html, body {
            width: 288pt;
            height: 432pt;
            overflow: hidden;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
        }

        body {
            padding: 5pt;
        }

        .label-container {
            width: 265pt;
            height: 405pt;
            border: 2px solid #000;
            padding: 8px;
            position: relative;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

<div class="label-container">
    @php
        $addr = $order->customerAddress;
        $isCod = strtolower($order->payment_method) === 'cod';
        $totalQty = $order->items->sum('quantity');
    @endphp

    {{-- Top Header Row --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 2px;">
        <tr>
            <td style="font-size: 11px; font-weight: bold; border: none; padding: 0;">Order No: {{ $order->order_no }}</td>
            <td style="font-size: 11px; font-weight: bold; border: none; padding: 0; text-align: right;">Date: {{ format_date($order->created_at, 'd M Y') }}</td>
        </tr>
    </table>
    <div style="border-bottom: 2px solid #000; margin-bottom: 8px;"></div>

    {{-- TO Section (SHIP TO) --}}
    <div style="margin-bottom: 6px;">
        <div style="font-size: 9.5px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; color: #444;">Ship To:</div>
        <div style="font-size: 14px; font-weight: bold; margin-bottom: 2px;">
            {{ $addr->name ?? ($order->customer->name ?? 'Walk-in Customer') }}
        </div>
        <div style="font-size: 10.5px; line-height: 1.4; margin-bottom: 4px;">
            @if($order->customerAddress)
                {{ $addr->address }}<br>
                {{ $addr->city }}, {{ $addr->state }} - {{ $addr->pincode }}
            @else
                No shipping address provided.
            @endif
        </div>
        <div style="font-size: 11px; font-weight: bold; margin-bottom: 6px;">
            Phone: {{ $addr->phone ?? ($order->customer->phone ?? '-') }}
            @if(!empty($addr->alternate_phone))
                &nbsp;/&nbsp; {{ $addr->alternate_phone }}
            @endif
        </div>
        @if(!empty($addr->pincode))
            <div style="border: 1px solid #000; padding: 4px 8px; font-size: 13px; font-weight: bold; display: inline-block; letter-spacing: 0.5px;">
                PIN: {{ $addr->pincode }}
            </div>
        @endif
    </div>

    <div style="border-top: 1px dashed #000; margin: 8px 0;"></div>

    {{-- Middle Section (SHIP FROM & Payment Box) --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px;">
        <tr>
            <td style="width: 50%; vertical-align: top; border: none; padding: 0;">
                <div style="font-size: 9.5px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; color: #444;">Ship From:</div>
                <div style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">Chetan Imitation</div>
                <div style="font-size: 9.5px; line-height: 1.3; color: #333; margin-bottom: 4px;">
                    @if($order->location)
                        {{ $order->location->name }}<br>
                        @if($order->location->address)
                            {{ $order->location->address }}
                        @endif
                    @else
                        Surat Retail Outlet<br>
                        10, Commercial Plaza, Main Road, Surat, Gujarat
                    @endif
                </div>
                <div style="font-size: 9.5px; color: #444; line-height: 1.3;">
                    Phone: {{ $order->location?->phone ?? '+91 77259 78871' }}<br>
                    Email: support@chetanimitation.com
                </div>
            </td>
            <td style="width: 50%; vertical-align: middle; text-align: right; border: none; padding: 0;">
                <div style="border: 1px solid #000; padding: 6px; width: 140px; float: right; text-align: center; margin-right: 2px;">
                    <div style="font-size: 12px; font-weight: bold; letter-spacing: 0.5px; height: 18px; line-height: 18px;">
                        🚚 &nbsp;{{ $isCod ? 'COD' : 'PREPAID' }}
                    </div>
                    <div style="font-size: 13px; font-weight: bold; border-top: 1px solid #000; padding-top: 4px; margin-top: 4px; margin-bottom: 2px;">
                        ₹{{ number_format($order->final_amount, 2) }}
                    </div>
                    <div style="font-size: 8px; color: #444;">
                        {{ $isCod ? '(Collect Cash)' : '(Do Not Collect Cash)' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div style="border-top: 1px dashed #000; margin: 8px 0;"></div>

    {{-- Package Summary Row --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 6px; text-align: center;">
        <tr>
            <td style="width: 50%; vertical-align: middle; border: none; padding: 2px 0;">
                <div style="font-size: 9px; font-weight: bold; text-transform: uppercase; color: #444; height: 18px; line-height: 18px;">
                    📦 &nbsp;Total Items
                </div>
                <div style="font-size: 13px; font-weight: bold; margin-top: 3px;">{{ $totalQty }}</div>
            </td>
            <td style="width: 50%; vertical-align: middle; border-left: 1px solid #ccc; padding: 2px 0;">
                <div style="font-size: 9px; font-weight: bold; text-transform: uppercase; color: #444; height: 18px; line-height: 18px;">
                    💳 &nbsp;Payment
                </div>
                <div style="font-size: 13px; font-weight: bold; margin-top: 3px; text-transform: capitalize;">
                    {{ $order->payment_method === 'cod' ? 'C.O.D.' : 'Prepaid' }}
                </div>
            </td>
        </tr>
    </table>

    <div style="border-top: 1px dashed #000; margin: 8px 0;"></div>

    {{-- Contents --}}
    <div style="margin-bottom: 4px;">
        <span style="font-size: 9.5px; font-weight: bold; text-transform: uppercase; color: #444;">Contents:</span>
        <div style="font-size: 11px; margin-top: 2px;">Jewellery Items</div>
    </div>

    {{-- Footer --}}
    <div style="position: absolute; bottom: 8px; left: 8px; right: 8px;">
        <div style="border-top: 1px solid #000; margin-bottom: 4px;"></div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="font-size: 8.5px; border: none; padding: 0; color: #555;">Thank you for shopping with us!</td>
                <td style="font-size: 8.5px; border: none; padding: 0; color: #555; text-align: right;">Handle with care.</td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>
