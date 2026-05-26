<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Sale {{ $order->order_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .page { padding: 30px; }

        /* Header */
        .header { width: 100%; margin-bottom: 30px; border-bottom: 2px solid #7367f0; padding-bottom: 20px; }
        .header table { width: 100%; margin-bottom: 0; }
        .header table td { border: none; padding: 0; vertical-align: top; }
        .header-right { text-align: right; }
        .company-name { font-size: 22px; font-weight: bold; color: #7367f0; }
        .company-sub { font-size: 11px; color: #888; margin-top: 4px; }
        .sale-title h2 { font-size: 20px; color: #7367f0; text-transform: uppercase; }
        .sale-no { font-size: 13px; font-weight: bold; margin-top: 4px; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-pending   { background: #fff3cd; color: #856404; }
        .status-paid      { background: #cff4fc; color: #055160; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Info section */
        .info-section { width: 100%; margin-bottom: 25px; }
        .info-section table { width: 100%; margin-bottom: 0; }
        .info-section table td { border: none; padding: 0; vertical-align: top; width: 50%; }
        .info-box { padding-right: 10px; }
        .info-box-right { padding-left: 10px; text-align: right; }
        .info-box h4, .info-box-right h4 { font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .info-box p, .info-box-right p { margin-bottom: 4px; font-size: 12px; }
        .label { color: #888; font-size: 11px; }

        /* Items table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #7367f0; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
        tbody td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }

        /* Totals */
        .totals-section { width: 250px; float: right; margin-bottom: 20px; }
        .totals-row { width: 100%; }
        .totals-row table { margin-bottom: 0; }
        .totals-row table td { border: none; padding: 4px 0; font-size: 12px; }
        .totals-grand table td { font-size: 14px; font-weight: bold; color: #7367f0; border-top: 2px solid #7367f0; padding-top: 8px; }

        /* Footer */
        .footer { margin-top: 40px; border-top: 1px solid #eee; padding-top: 15px; text-align: center; color: #aaa; font-size: 10px; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="company-name">Chetan Immitation</div>
                    <div class="company-sub">Sales Management System</div>
                </td>
                <td class="header-right">
                    <div class="sale-title">
                        <h2>Sale Invoice</h2>
                        <div class="sale-no">{{ $order->order_no }}</div>
                        <div style="margin-top:6px;">
                            @php
                                $statusClass = [
                                    'pending'   => 'status-pending',
                                    'paid'      => 'status-paid',
                                    'completed' => 'status-completed',
                                    'cancelled' => 'status-cancelled',
                                ];
                            @endphp
                            <span class="status-badge {{ $statusClass[$order->status] ?? 'status-pending' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <table>
            <tr>
                <td>
                    <div class="info-box">
                        <h4>Customer Details</h4>
                        <p><strong>{{ $order->customer->name ?? 'Walk-in Customer' }}</strong></p>
                        @if($order->customer?->phone)
                            <p><span class="label">Phone:</span> {{ $order->customer->phone }}</p>
                        @endif
                        @if($order->customer?->email)
                            <p><span class="label">Email:</span> {{ $order->customer->email }}</p>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="info-box-right">
                        <h4>Sale Details</h4>
                        <p><span class="label">Sale No:</span> <strong>{{ $order->order_no }}</strong></p>
                        <p><span class="label">Date:</span> {{ format_date($order->created_at) }}</p>
                        <p><span class="label">Location:</span> {{ $order->location->name ?? '-' }}</p>
                        <p><span class="label">Served By:</span> {{ $order->user->name ?? '-' }}</p>
                        <p><span class="label">Payment:</span> {{ ucfirst($order->payment_method) }}</p>
                        <p><span class="label">Payment Status:</span> {{ ucfirst($order->payment_status) }}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-right">Price</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $item->product->name ?? '-' }}</strong></td>
                    <td>{{ $item->product->sku ?? '-' }}</td>
                    <td class="text-right">{{ format_price($item->price) }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ $item->discount > 0 ? format_price($item->discount) : '-' }}</td>
                    <td class="text-right"><strong>{{ format_price($item->total) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-section">
        <div class="totals-row">
            <table>
                <tr>
                    <td>Items Total</td>
                    <td class="text-right">{{ format_price($order->total_amount) }}</td>
                </tr>
                @if($order->discount > 0)
                    <tr>
                        <td>Discount</td>
                        <td class="text-right" style="color:#ea5455;">- {{ format_price($order->discount) }}</td>
                    </tr>
                @endif
            </table>
        </div>
        <div class="totals-grand">
            <table>
                <tr>
                    <td>Final Amount</td>
                    <td class="text-right">{{ format_price($order->final_amount) }}</td>
                </tr>
            </table>
        </div>
    </div>
    <div style="clear:both;"></div>

    <!-- Footer -->
    <div class="footer">
        <p>Generated on {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; {{ config('app.name') }}</p>
    </div>

</div>
</body>
</html>
