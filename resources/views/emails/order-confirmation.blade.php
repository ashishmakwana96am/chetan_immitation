<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — {{ $order->order_no }}</title>
</head>
<body style="margin:0;padding:0;font-family:'Nunito',Arial,sans-serif;background-color:#f4f4f4;">
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

            <h1 style="color:#B4771E;font-size:26px;font-weight:700;margin:0 0 8px;text-align:center;">Order Confirmed!</h1>
            <p style="color:#3D403F;font-size:15px;line-height:1.7;margin:0 0 28px;text-align:center;">
                Hi <strong>{{ $order->customer->name ?? 'Customer' }}</strong>, thank you for shopping with us.<br>
                Your order has been placed successfully and is being processed.
            </p>

            <!-- Order Meta Block -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:24px;">
                <tr>
                    <td style="width:50%;padding:0 8px 0 0;vertical-align:top;">
                        <div style="background:#fdf9f4;border:1px solid #e8e0d2;border-radius:6px;padding:14px 16px;">
                            <p style="margin:0 0 4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#B4771E;">Order Number</p>
                            <p style="margin:0;font-size:15px;font-weight:700;color:#131615;">{{ $order->order_no }}</p>
                        </div>
                    </td>
                    <td style="width:50%;padding:0 0 0 8px;vertical-align:top;">
                        <div style="background:#fdf9f4;border:1px solid #e8e0d2;border-radius:6px;padding:14px 16px;">
                            <p style="margin:0 0 4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#B4771E;">Order Date</p>
                            <p style="margin:0;font-size:15px;font-weight:700;color:#131615;">{{ $order->created_at->format('d M Y') }}</p>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Order Items Box -->
            <h3 style="margin:0 0 12px;color:#131615;font-size:16px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">Items Ordered</h3>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;margin-bottom:28px;">
                <tr style="background:#B4771E;">
                    <th style="padding:10px 12px;text-align:left;font-size:12px;font-weight:700;color:#ffffff;text-transform:uppercase;letter-spacing:0.4px;">Product</th>
                    <th style="padding:10px 12px;text-align:center;font-size:12px;font-weight:700;color:#ffffff;text-transform:uppercase;letter-spacing:0.4px;width:70px;">Qty</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;font-weight:700;color:#ffffff;text-transform:uppercase;letter-spacing:0.4px;width:100px;">Total</th>
                </tr>
                @foreach($order->items as $item)
                <tr style="border-bottom:1px solid #e8e8e8;background:#ffffff;">
                    <td style="padding:12px 12px;font-size:14px;color:#131615;">{{ $item->product->name ?? 'Product' }}</td>
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
                    $couponDisc   = 0;
                    if ($order->coupon_id && $order->coupon) {
                        $couponDisc = max(0, round($itemsGross - $itemDiscount - ((float)$order->final_amount - (float)$order->shipping_charge), 2));
                    }
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
                    $totalDiscount = $itemDiscount + $orderDiscountAmount + $couponDisc;
                @endphp

                {{-- Subtotal --}}
                <tr>
                    <td colspan="2" style="padding:10px 12px 4px;text-align:right;font-size:13px;color:#757575;">Subtotal</td>
                    <td style="padding:10px 12px 4px;text-align:right;font-size:13px;color:#131615;font-weight:600;">₹{{ number_format($itemsGross, 0) }}</td>
                </tr>
                {{-- Discount --}}
                @if($totalDiscount > 0)
                <tr>
                    <td colspan="2" style="padding:4px 12px;text-align:right;font-size:13px;color:#c62828;">Discount</td>
                    <td style="padding:4px 12px;text-align:right;font-size:13px;color:#c62828;font-weight:600;">-₹{{ number_format($totalDiscount, 0) }}</td>
                </tr>
                @endif
                {{-- Shipping --}}
                <tr>
                    <td colspan="2" style="padding:4px 12px;text-align:right;font-size:13px;color:#757575;">Shipping</td>
                    <td style="padding:4px 12px;text-align:right;font-size:13px;color:#131615;font-weight:600;">{{ $order->shipping_charge > 0 ? '₹' . number_format($order->shipping_charge, 0) : 'Free' }}</td>
                </tr>
                {{-- Final --}}
                <tr style="border-top:2px solid #B4771E;">
                    <td colspan="2" style="padding:14px 12px;text-align:right;font-size:16px;font-weight:700;color:#B4771E;">Total Amount</td>
                    <td style="padding:14px 12px;text-align:right;font-size:16px;font-weight:700;color:#B4771E;">₹{{ number_format($order->final_amount, 0) }}</td>
                </tr>
            </table>

            <!-- Payment & Delivery Info Box -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:28px;">
                <tr>
                    {{-- Payment Details --}}
                    <td style="width:50%;padding:0 8px 0 0;vertical-align:top;">
                        <p style="margin:0 0 8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#B4771E;">Payment Info</p>
                        <p style="margin:0 0 4px;font-size:13px;color:#131615;">
                            <span style="color:#757575;font-size:12px;">Method: </span>
                            {{ ucwords(str_replace('_', ' ', $order->payment_method)) }}
                        </p>
                        <p style="margin:0;font-size:13px;color:#131615;">
                            <span style="color:#757575;font-size:12px;">Status: </span>
                            {{ $order->payment_status == 2 ? 'Paid' : 'Pending' }}
                        </p>
                        @if($order->payment?->gateway_payment_id)
                        <p style="margin:6px 0 0;font-size:11px;color:#757575;font-family:monospace;">
                            ID: {{ $order->payment->gateway_payment_id }}
                        </p>
                        @endif
                    </td>
                    {{-- Delivery Details --}}
                    @if($order->customerAddress)
                    <td style="width:50%;padding:0 0 0 12px;vertical-align:top;border-left:1px solid #f0ebe2;">
                        <p style="margin:0 0 8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#B4771E;">Deliver To</p>
                        <p style="margin:0;font-size:13px;color:#131615;line-height:1.6;">
                            <strong>{{ $order->customerAddress->name }}</strong><br>
                            {{ $order->customerAddress->phone }}<br>
                            {{ $order->customerAddress->address }},<br>
                            {{ $order->customerAddress->city }}, {{ $order->customerAddress->state }}{{ $order->customerAddress->pincode ? ' - ' . $order->customerAddress->pincode : '' }}
                        </p>
                    </td>
                    @endif
                </tr>
            </table>

            <div style="background:#fdf9f4;border-left:4px solid #B4771E;padding:16px 20px;border-radius:0 6px 6px 0;margin-bottom:28px;">
                <p style="margin:0;font-size:13px;color:#555555;line-height:1.6;">
                    Your invoice is attached to this email as a PDF. You can also download it anytime from your
                    <a href="{{ route('customer.profile') }}" style="color:#B4771E;font-weight:600;text-decoration:none;">My Account</a> page.
                </p>
            </div>

            <!-- CTA Button -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                <tr>
                    <td style="background-color:#B4771E;border-radius:4px;">
                        <a href="{{ route('customer.profile') }}" style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:16px;font-weight:600;text-decoration:none;letter-spacing:0.3px;">
                            Go to My Account
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
