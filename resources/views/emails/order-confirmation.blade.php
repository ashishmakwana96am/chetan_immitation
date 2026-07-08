<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — {{ $order->order_no }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f1eb;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#2d2d2d;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f1eb;padding:32px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">

                {{-- ── TOP GOLD BAR ────────────────────────── --}}
                <tr>
                    <td style="background:#B4771E;height:5px;font-size:0;line-height:0;">&nbsp;</td>
                </tr>

                {{-- ── HEADER ──────────────────────────────── --}}
                <tr>
                    <td style="background:#B4771E;padding:28px 40px;text-align:center;">
                        <img src="{{ asset('website/assets/images/logo.png') }}" alt="Chetan Imitation" style="max-width:140px;height:auto;display:inline-block;">
                        <p style="margin:10px 0 0;color:rgba(255,255,255,0.85);font-size:13px;letter-spacing:0.5px;">Premium Imitation Jewellery</p>
                    </td>
                </tr>

                {{-- ── HERO TEXT ────────────────────────────── --}}
                <tr>
                    <td style="padding:36px 40px 20px;text-align:center;border-bottom:1px solid #f0ebe2;">
                        <h1 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#B4771E;">Order Confirmed!</h1>
                        <p style="margin:0;color:#555;font-size:14px;line-height:1.6;">
                            Hi <strong>{{ $order->customer->name ?? 'Customer' }}</strong>, thank you for shopping with us.<br>
                            Your order has been placed successfully and is being processed.
                        </p>
                    </td>
                </tr>

                {{-- ── ORDER META ───────────────────────────── --}}
                <tr>
                    <td style="padding:24px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="width:50%;padding:0 8px 0 0;vertical-align:top;">
                                    <div style="background:#fdf9f4;border:1px solid #e8e0d2;border-radius:4px;padding:14px 16px;">
                                        <p style="margin:0 0 4px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#B4771E;">Order Number</p>
                                        <p style="margin:0;font-size:15px;font-weight:700;color:#2d2d2d;">{{ $order->order_no }}</p>
                                    </div>
                                </td>
                                <td style="width:50%;padding:0 0 0 8px;vertical-align:top;">
                                    <div style="background:#fdf9f4;border:1px solid #e8e0d2;border-radius:4px;padding:14px 16px;">
                                        <p style="margin:0 0 4px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#B4771E;">Order Date</p>
                                        <p style="margin:0;font-size:15px;font-weight:700;color:#2d2d2d;">{{ $order->created_at->format('d M Y') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- ── ITEMS ────────────────────────────────── --}}
                <tr>
                    <td style="padding:24px 40px 0;">
                        <p style="margin:0 0 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#B4771E;">Items Ordered</p>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <tr style="background:#B4771E;">
                                <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:0.4px;">Product</th>
                                <th style="padding:8px 12px;text-align:center;font-size:11px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:0.4px;width:50px;">Qty</th>
                                <th style="padding:8px 12px;text-align:right;font-size:11px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:0.4px;width:90px;">Total</th>
                            </tr>
                            @foreach($order->items as $item)
                            <tr style="border-bottom:1px solid #f0ebe2;{{ $loop->even ? 'background:#fdf9f4;' : 'background:#fff;' }}">
                                <td style="padding:10px 12px;font-size:13px;color:#2d2d2d;">{{ $item->product->name ?? 'Product' }}</td>
                                <td style="padding:10px 12px;text-align:center;font-size:13px;color:#555;">{{ $item->quantity }} {{ ($item->pair_type ?? 'single') === 'pair' ? 'Pairs' : 'Pcs' }}</td>
                                <td style="padding:10px 12px;text-align:right;font-size:13px;font-weight:600;color:#2d2d2d;">₹{{ number_format($item->total, 0) }}</td>
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
                                <td colspan="2" style="padding:8px 12px;text-align:right;font-size:12px;color:#555;border-top:1px solid #e8e0d2;">Subtotal</td>
                                <td style="padding:8px 12px;text-align:right;font-size:12px;color:#555;border-top:1px solid #e8e0d2;">₹{{ number_format($itemsGross, 0) }}</td>
                            </tr>
                            {{-- Discount --}}
                            @if($totalDiscount > 0)
                            <tr>
                                <td colspan="2" style="padding:4px 12px;text-align:right;font-size:12px;color:#c62828;">Discount</td>
                                <td style="padding:4px 12px;text-align:right;font-size:12px;color:#c62828;">-₹{{ number_format($totalDiscount, 0) }}</td>
                            </tr>
                            @endif
                            {{-- Shipping --}}
                            <tr>
                                <td colspan="2" style="padding:4px 12px;text-align:right;font-size:12px;color:#555;">Shipping</td>
                                <td style="padding:4px 12px;text-align:right;font-size:12px;color:#555;">{{ $order->shipping_charge > 0 ? '₹' . number_format($order->shipping_charge, 0) : 'Free' }}</td>
                            </tr>
                            {{-- Final --}}
                            <tr style="border-top:2px solid #B4771E;">
                                <td colspan="2" style="padding:12px 12px;text-align:right;font-size:15px;font-weight:700;color:#B4771E;">Total Amount</td>
                                <td style="padding:12px 12px;text-align:right;font-size:15px;font-weight:700;color:#B4771E;">₹{{ number_format($order->final_amount, 0) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- ── PAYMENT & DELIVERY ───────────────────── --}}
                <tr>
                    <td style="padding:24px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                {{-- Payment --}}
                                <td style="width:50%;padding:0 8px 0 0;vertical-align:top;">
                                    <p style="margin:0 0 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#B4771E;">Payment</p>
                                    <p style="margin:0 0 4px;font-size:13px;color:#2d2d2d;">
                                        <span style="color:#888;font-size:11px;">Method: </span>
                                        {{ ucwords(str_replace('_', ' ', $order->payment_method)) }}
                                    </p>
                                    <p style="margin:0;font-size:13px;color:#2d2d2d;">
                                        <span style="color:#888;font-size:11px;">Status: </span>
                                        {{ $order->payment_status == 2 ? 'Paid' : 'Pending' }}
                                    </p>
                                    @if($order->payment?->gateway_payment_id)
                                    <p style="margin:4px 0 0;font-size:11px;color:#888;">
                                        ID: {{ $order->payment->gateway_payment_id }}
                                    </p>
                                    @endif
                                </td>
                                {{-- Delivery --}}
                                @if($order->customerAddress)
                                <td style="width:50%;padding:0 0 0 8px;vertical-align:top;border-left:1px solid #f0ebe2;">
                                    <p style="margin:0 0 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#B4771E;padding-left:12px;">Deliver To</p>
                                    <p style="margin:0;font-size:13px;color:#2d2d2d;line-height:1.6;padding-left:12px;">
                                        <strong>{{ $order->customerAddress->name }}</strong><br>
                                        {{ $order->customerAddress->phone }}<br>
                                        {{ $order->customerAddress->address }},<br>
                                        {{ $order->customerAddress->city }}, {{ $order->customerAddress->state }}{{ $order->customerAddress->pincode ? ' - ' . $order->customerAddress->pincode : '' }}
                                    </p>
                                </td>
                                @endif
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- ── NOTE ────────────────────────────────── --}}
                <tr>
                    <td style="padding:24px 40px;">
                        <div style="background:#fdf9f4;border-left:3px solid #B4771E;padding:12px 16px;border-radius:0 4px 4px 0;">
                            <p style="margin:0;font-size:12px;color:#555;line-height:1.6;">
                                Your invoice is attached to this email as a PDF. You can also download it anytime from your
                                <a href="{{ route('customer.profile') }}" style="color:#B4771E;font-weight:600;text-decoration:none;">My Account</a> page.
                            </p>
                        </div>
                    </td>
                </tr>

                {{-- ── FOOTER ───────────────────────────────── --}}
                <tr>
                    <td style="background:#fdf9f4;border-top:1px solid #e8e0d2;padding:20px 40px;text-align:center;">
                        <p style="margin:0 0 4px;font-size:12px;color:#888;">
                            &copy; {{ date('Y') }} Chetan Imitation. All rights reserved.
                        </p>
                        <p style="margin:0;font-size:11px;color:#aaa;">
                            If you have any questions, please contact our support team.
                        </p>
                    </td>
                </tr>

                {{-- ── BOTTOM GOLD BAR ─────────────────────── --}}
                <tr>
                    <td style="background:#B4771E;height:4px;font-size:0;line-height:0;">&nbsp;</td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
