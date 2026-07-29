<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Report</title>
    <style>
        @page { size: A4 landscape; margin: 12px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #222; margin: 0; padding: 0; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border-bottom: 2px solid #333; padding-bottom: 6px; }
        .company-name { font-size: 16px; font-weight: bold; color: #111; letter-spacing: 0.5px; }
        .report-title { font-size: 13px; font-weight: bold; color: #444; text-transform: uppercase; text-align: right; }
        .report-meta { font-size: 8.5px; color: #555; margin-top: 3px; }
        
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary-box td { border: 1px solid #bbb; padding: 6px 10px; background: #f8f9fa; }
        .summary-label { font-size: 8px; color: #555; text-transform: uppercase; font-weight: bold; }
        .summary-value { font-size: 11px; font-weight: bold; color: #111; margin-top: 2px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #999; padding: 5px 6px; text-align: left; font-size: 8.5px; }
        table.data-table th { background-color: #e9ecef; font-weight: bold; text-transform: uppercase; color: #222; border-bottom: 2px solid #666; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        
        tfoot tr td { background-color: #e9ecef; font-weight: bold; border-top: 2px solid #333; border-bottom: 2px solid #333; font-size: 9px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name">CHETAN IMITATION</div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="report-title">PAYMENT REPORT</div>
                <div class="report-meta">Generated Date: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td style="width: 25%;">
                <div class="summary-label">Total Transactions</div>
                <div class="summary-value">{{ $totalCount }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Sales Amount</div>
                <div class="summary-value">{{ format_price($totalAmount) }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Pending Amount</div>
                <div class="summary-value">{{ format_price($pendingAmount) }} ({{ $pendingCount }})</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Refunded Amount</div>
                <div class="summary-value">{{ format_price($refundAmount) }} ({{ $refundCount }})</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 13%;">Invoice No</th>
                <th style="width: 23%;">Customer Name</th>
                <th style="width: 12%;">Source</th>
                <th style="width: 12%;">Method</th>
                <th style="width: 12%;">Payment Status</th>
                <th style="width: 14%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
                @php
                    $method = $order->payment_method === 'online_cash'
                        ? 'Cash + Online'
                        : strtoupper($order->payment_method ?? '-');
                    $source = strtoupper($order->source ?? 'POS');
                    $cancellation = $order->cancellationRequest;
                    $isRefunded = $cancellation
                        && $cancellation->status === \App\Models\OrderCancellationRequest::STATUS_APPROVED
                        && (float) $cancellation->refund_amount > 0;
                    
                    if ($isRefunded) {
                        $status = 'Refunded';
                    } elseif ($order->payment && $order->payment->status === 'captured') {
                        $status = 'Paid';
                    } elseif ((int)$order->payment_status === \App\Models\Order::PAYMENT_STATUS_PAID) {
                        $status = 'Paid';
                    } else {
                        $status = 'Pending';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $order->created_at->format('d/m/Y') }}</td>
                    <td><strong>{{ $order->order_no }}</strong></td>
                    <td>{{ $order->customer?->name ?: 'Walk-in Customer' }}</td>
                    <td>{{ $source }}</td>
                    <td>{{ $method }}</td>
                    <td>{{ $status }}</td>
                    <td class="text-right"><strong>{{ number_format((float) $order->final_amount, 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px;">No payment transactions found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
        @if($orders->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="7" class="text-right fw-bold">TOTAL:</td>
                    <td class="text-right fw-bold">{{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
