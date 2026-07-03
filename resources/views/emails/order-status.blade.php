<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #B4771E; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ddd; }
        .order-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .order-item:last-child { border-bottom: none; }
        .total-row { font-weight: bold; font-size: 1.1em; color: #B4771E; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; }
        .btn { display: inline-block; background: #B4771E; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
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
    <div class="header">
        <h1>Order Status Updated</h1>
        <p>Your order status is now: <strong>{{ $statusName }}</strong></p>
    </div>
    
    <div class="content">
        <p>Dear {{ $order->customer->name ?? 'Customer' }},</p>
        
        <p>We are writing to let you know that the status of your order <strong>#{{ $order->order_no }}</strong> has been updated to <strong>{{ $statusName }}</strong>.</p>
        
        <div class="order-details">
            <h3>Order Information</h3>
            <p><strong>Order ID:</strong> {{ $order->order_no }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('d M Y') }}</p>
            <p><strong>Current Status:</strong> 
                @if($order->status == 5)
                    <span style="color: green; font-weight: bold;">Delivered</span>
                @elseif($order->status == 6)
                    <span style="color: red; font-weight: bold;">Cancelled</span>
                @elseif($order->status == 3 || $order->status == 4)
                    <span style="color: blue; font-weight: bold;">{{ $statusName }}</span>
                @else
                    <span style="color: orange; font-weight: bold;">{{ $statusName }}</span>
                @endif
            </p>

            @if($order->shipped_client_url || $order->tracking_id)
                <div style="background: #fdf6ed; border-left: 4px solid #B4771E; padding: 12px; margin: 15px 0; border-radius: 4px; font-size: 0.95em;">
                    <h4 style="margin: 0 0 8px 0; color: #B4771E;">Shipping & Tracking Information</h4>
                    @if($order->shipped_client_url)
                        <p style="margin: 4px 0;"><strong>Track Order Link:</strong> <a href="{{ $order->shipped_client_url }}" target="_blank" style="color: #B4771E; font-weight: bold; text-decoration: underline;">Click Here to Track</a></p>
                    @endif
                    @if($order->tracking_id)
                        <p style="margin: 4px 0;"><strong>Tracking ID:</strong> <span style="font-weight: bold; font-family: monospace; background: #eaeaea; padding: 2px 6px; border-radius: 3px;">{{ $order->tracking_id }}</span></p>
                    @endif
                </div>
            @endif
            
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

            @foreach($order->items as $item)
                <div class="order-item">
                    <span>{{ $item->product->name }} x {{ $item->quantity }} {{ ($item->pair_type ?? 'single') === 'pair' ? 'Pairs' : 'Pcs' }}</span>
                    <span>₹{{ number_format($item->total, 0) }}</span>
                </div>
            @endforeach

            <div class="order-item" style="border-top:1px solid #eee; padding-top: 10px;">
                <span>Subtotal</span>
                <span>₹{{ number_format($itemsGross, 0) }}</span>
            </div>

            @if($totalDiscount > 0)
                <div class="order-item" style="color: #c62828;">
                    <span>Discount</span>
                    <span>-₹{{ number_format($totalDiscount, 0) }}</span>
                </div>
            @endif
            
            <div class="order-item total-row">
                <span>Total Amount</span>
                <span>₹{{ number_format($order->final_amount, 0) }}</span>
            </div>
        </div>
        
        <p>Thank you for choosing Chetan Imitation for your jewellery collection.</p>
        
        <a href="{{ route('home') }}" class="btn">View My Orders</a>
        
        <div class="footer">
            <p>If you have any questions, please feel free to reach out to us.</p>
            <p>&copy; {{ date('Y') }} Chetan Imitation. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
