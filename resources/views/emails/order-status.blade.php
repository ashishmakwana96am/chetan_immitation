<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update — Chetan Imitation</title>
</head>
<body style="margin:0;padding:0;font-family:'Nunito',Arial,sans-serif;background-color:#f4f4f4;">
@php
    $statusLabels = [
        1 => 'Pending',
        2 => 'Approved',
        3 => 'Shipped',
        4 => 'Out for delivery',
        5 => 'Delivered',
        6 => 'Cancelled',
    ];
    $statusName = $statusLabels[$order->status] ?? 'Updated';
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
<td align="center" style="padding:30px 15px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    <!-- Header -->
    <tr>
        <td style="background-color:#131615;padding:28px 30px;text-align:center;">
            <img src="https://royalgujarati.com/chetan-imitation/website/assets/images/logo.png" alt="Chetan Imitation" style="max-width:150px;height:auto;display:inline-block;">
        </td>
    </tr>

    <!-- Gold bar -->
    <tr>
        <td style="background-color:#B4771E;height:5px;font-size:0;line-height:0;">&nbsp;</td>
    </tr>

    <!-- Body -->
    <tr>
        <td style="padding:40px 35px 30px;">

            <h1 style="color:#131615;font-size:24px;font-weight:700;margin:0 0 16px;">Order Status Updated</h1>
            <p style="color:#3D403F;font-size:16px;line-height:1.7;margin:0 0 12px;">
                Dear {{ $order->customer->name ?? 'Customer' }},
            </p>
            <p style="color:#3D403F;font-size:15px;line-height:1.7;margin:0 0 24px;">
                We are writing to let you know that the status of your order <strong>#{{ $order->order_no }}</strong> has been updated to <strong>{{ $statusName }}</strong>.
            </p>

            <!-- Order Details Box -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f7f7f7;border-radius:6px;border-left:4px solid #B4771E;padding:0;margin-bottom:28px;">
                <tr>
                    <td style="padding:20px 22px;">
                        <h3 style="margin:0 0 12px;color:#131615;font-size:16px;font-weight:700;">Order Information</h3>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:4px 0;width:120px;">Order ID</td>
                                <td style="color:#131615;font-size:14px;padding:4px 0;font-weight:600;">{{ $order->order_no }}</td>
                            </tr>
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:4px 0;">Order Date</td>
                                <td style="color:#131615;font-size:14px;padding:4px 0;font-weight:600;">{{ $order->created_at->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:4px 0;">Current Status</td>
                                <td style="font-size:14px;padding:4px 0;font-weight:700;">
                                    @if($order->status == 5)
                                        <span style="color: green;">Delivered</span>
                                    @elseif($order->status == 6)
                                        <span style="color: red;">Cancelled</span>
                                    @elseif($order->status == 3 || $order->status == 4)
                                        <span style="color: #0d6efd;">{{ $statusName }}</span>
                                    @else
                                        <span style="color: #ffc107;">{{ $statusName }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            @if($order->shipped_client_url || $order->tracking_id)
                <!-- Shipping Details Box -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#fdf9f4;border-radius:6px;border-left:4px solid #B4771E;padding:0;margin-bottom:28px;">
                    <tr>
                        <td style="padding:16px 20px;">
                            <h4 style="margin:0 0 10px;color:#B4771E;font-size:15px;font-weight:700;">Shipping & Tracking Information</h4>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                @if($order->shipped_client_url)
                                <tr>
                                    <td style="color:#757575;font-size:14px;padding:4px 0;width:120px;">Track Order</td>
                                    <td style="font-size:14px;padding:4px 0;">
                                        <a href="{{ $order->shipped_client_url }}" target="_blank" style="color:#B4771E;font-weight:700;text-decoration:underline;">Click Here to Track</a>
                                    </td>
                                </tr>
                                @endif
                                @if($order->tracking_id)
                                <tr>
                                    <td style="color:#757575;font-size:14px;padding:4px 0;">Tracking ID</td>
                                    <td style="color:#131615;font-size:14px;padding:4px 0;font-weight:700;font-family:monospace;background:#e9ecef;padding:2px 6px;border-radius:3px;display:inline-block;">{{ $order->tracking_id }}</td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>
                </table>
            @endif

            <!-- Order Items Box -->
            <h3 style="margin:0 0 12px;color:#131615;font-size:16px;font-weight:700;">Items Ordered</h3>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;margin-bottom:28px;">
                <tr style="background:#B4771E;">
                    <th style="padding:10px 12px;text-align:left;font-size:12px;font-weight:700;color:#ffffff;text-transform:uppercase;letter-spacing:0.4px;">Product</th>
                    <th style="padding:10px 12px;text-align:center;font-size:12px;font-weight:700;color:#ffffff;text-transform:uppercase;letter-spacing:0.4px;width:70px;">Qty</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;font-weight:700;color:#ffffff;text-transform:uppercase;letter-spacing:0.4px;width:100px;">Total</th>
                </tr>
                @foreach($order->items as $item)
                <tr style="border-bottom:1px solid #e8e8e8;background:#ffffff;">
                    <td style="padding:12px 12px;font-size:14px;color:#131615;">{{ $item->product->name }}</td>
                    <td style="padding:12px 12px;text-align:center;font-size:14px;color:#3D403F;">
                        {{ $item->quantity }}
                        @if($item->product && $item->product->pair_product && $item->product->pair_mode === 'custom_size' && $item->custom_size_value)
                            {{ rtrim(rtrim(number_format((float) $item->custom_size_value, 2), '0'), '.') }} pcs Pair
                        @else
                            {{ ($item->pair_type ?? 'single') === 'pair' ? 'Pairs' : 'Pcs' }}
                        @endif
                    </td>
                    <td style="padding:12px 12px;text-align:right;font-size:14px;font-weight:600;color:#131615;">₹{{ number_format($item->total, 0) }}</td>
                </tr>
                @endforeach

                @php
                    $itemsGross   = $order->items->sum(fn($i) => (float)$i->price * (float)$i->quantity);
                    $itemDiscount = $order->items->sum('discount_amount');
                    $orderDiscountAmount = 0.0;
                    if ($order->order_discount_value > 0) {
                        $itemsTotal = $itemsGross - $itemDiscount;
                        if ($order->order_discount_type === 'flat') {
                            $orderDiscountAmount = (float)$order->order_discount_value;
                        } else if ($order->order_discount_type === 'percentage') {
                            $orderDiscountAmount = $itemsTotal * ((float)$order->order_discount_value / 100);
                        }
                        $orderDiscountAmount = min($orderDiscountAmount, $itemsTotal);
                    }
                    $totalDiscount = $itemDiscount + $orderDiscountAmount;
                @endphp

                {{-- Subtotal --}}
                <tr>
                    <td colspan="2" style="padding:10px 12px 6px;text-align:right;font-size:13px;color:#757575;">Subtotal</td>
                    <td style="padding:10px 12px 6px;text-align:right;font-size:13px;color:#131615;font-weight:600;">₹{{ number_format($itemsGross, 0) }}</td>
                </tr>
                {{-- Discount --}}
                @if($totalDiscount > 0)
                <tr>
                    <td colspan="2" style="padding:4px 12px;text-align:right;font-size:13px;color:#c62828;">Discount</td>
                    <td style="padding:4px 12px;text-align:right;font-size:13px;color:#c62828;font-weight:600;">-₹{{ number_format($totalDiscount, 0) }}</td>
                </tr>
                @endif
                {{-- Final --}}
                <tr style="border-top:2px solid #B4771E;">
                    <td colspan="2" style="padding:14px 12px;text-align:right;font-size:16px;font-weight:700;color:#B4771E;">Total Amount</td>
                    <td style="padding:14px 12px;text-align:right;font-size:16px;font-weight:700;color:#B4771E;">₹{{ number_format($order->final_amount, 0) }}</td>
                </tr>
            </table>

            <p style="color:#3D403F;font-size:15px;line-height:1.7;margin:0 0 28px;text-align:center;">
                Thank you for choosing Chetan Imitation for your jewellery collection.
            </p>

            <!-- CTA Button -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 28px;">
                <tr>
                    <td style="background-color:#B4771E;border-radius:4px;">
                        <a href="{{ route('home') }}" style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:16px;font-weight:600;text-decoration:none;letter-spacing:0.3px;">
                            View My Orders
                        </a>
                    </td>
                </tr>
            </table>

        </td>
    </tr>

    <!-- Divider -->
    <tr>
        <td style="padding:0 35px;"><hr style="border:none;border-top:1px solid #e8e8e8;margin:0;"></td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background-color:#131615;padding:22px 30px;text-align:center;">
            <p style="color:#D5D5D5;font-size:13px;margin:0 0 6px;">
                &copy; {{ date('Y') }} Chetan Imitation. All Rights Reserved.
            </p>
            <p style="color:#757575;font-size:12px;margin:0;">
                Developed by <a href="https://risingstarinfotech.com/" target="_blank" style="color:#B4771E;text-decoration:none;">Rising Star Infotech</a>
            </p>
        </td>
    </tr>

</table>
</td>
</tr>
</table>
</body>
</html>
