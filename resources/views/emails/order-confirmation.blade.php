<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #B4771E; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ddd; }
        .order-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .order-item:last-child { border-bottom: none; }
        .total-row { font-weight: bold; font-size: 1.1em; color: #B4771E; }
        .address-info { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; }
        .btn { display: inline-block; background: #B4771E; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Confirmed!</h1>
        <p>Thank you for shopping with Chetan Imitation</p>
    </div>
    
    <div class="content">
        <p>Dear {{ $order->customer->name }},</p>
        
        <p>Your order has been successfully placed and is now being processed. Here are your order details:</p>
        
        <div class="order-details">
            <h3>Order Summary</h3>
            <p><strong>Order ID:</strong> {{ $order->order_no }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('d M Y') }}</p>
            <p><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
            <p><strong>Payment Status:</strong> 
                @if($order->payment_status == 2)
                    <span style="color: green;">Paid</span>
                @else
                    <span style="color: orange;">Pending</span>
                @endif
            </p>
            <p><strong>Order Status:</strong> 
                @if($order->status == 1)
                    <span style="color: orange;">Pending</span>
                @elseif($order->status == 2)
                    <span style="color: green;">Approved</span>
                @else
                    <span style="color: red;">Declined</span>
                @endif
            </p>
            
            <h4>Items Ordered</h4>
            @foreach($order->items as $item)
                <div class="order-item">
                    <span>{{ $item->product->name }} x {{ $item->quantity }}</span>
                    <span>₹{{ number_format($item->total, 0) }}</span>
                </div>
            @endforeach
            
            <div class="order-item total-row">
                <span>Total Amount</span>
                <span>₹{{ number_format($order->final_amount, 0) }}</span>
            </div>
        </div>
        
        @if($order->customerAddress)
        <div class="address-info">
            <h4>Shipping Address</h4>
            <p>{{ $order->customerAddress->name }}<br>
            {{ $order->customerAddress->phone }}<br>
            {{ $order->customerAddress->address }}, {{ $order->customerAddress->city }}, {{ $order->customerAddress->state }}</p>
        </div>
        @endif
        
        <p>Your order will be processed shortly. For online payments, the inventory will be reserved once our admin team approves the order. For Cash on Delivery orders, the order will be confirmed upon delivery.</p>
        
        <a href="{{ route('home') }}" class="btn">View My Orders</a>
        
        <div class="footer">
            <p>If you have any questions, please contact our support team.</p>
            <p>&copy; {{ date('Y') }} Chetan Imitation. All rights reserved.</p>
        </div>
    </div>
</body>
</html>