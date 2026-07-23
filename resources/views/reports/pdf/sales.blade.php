<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Register Report</title>
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
                <div class="report-meta">
                    <strong>Report:</strong> Sales Register Statement<br>
                    <strong>Period:</strong> {{ $startDate ? format_date($startDate) : 'All Time' }} to {{ $endDate ? format_date($endDate) : 'Today' }}
                </div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="report-title">SALES REGISTER</div>
                <div class="report-meta">Generated Date: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    @php
        $totalSales = $orders->sum('final_amount');
        $totalOrders = $orders->count();
        $totalTax = 0;
        $totalTaxable = 0;
        foreach($orders as $o) {
            $tax = $o->is_gst ? (float) ($o->tax_amount ?? 0) : 0;
            $taxable = max(0, (float) $o->final_amount - $tax);
            $totalTax += $tax;
            $totalTaxable += $taxable;
        }
    @endphp

    <table class="summary-box">
        <tr>
            <td style="width: 25%;">
                <div class="summary-label">Total Invoices</div>
                <div class="summary-value">{{ $totalOrders }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Taxable Value</div>
                <div class="summary-value">{{ format_price($totalTaxable) }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Tax Amount (GST)</div>
                <div class="summary-value">{{ format_price($totalTax) }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Sales Value</div>
                <div class="summary-value">{{ format_price($totalSales) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 13%;">Invoice No</th>
                <th style="width: 24%;">Customer Name</th>
                <th style="width: 14%;">GSTIN</th>
                <th style="width: 11%;" class="text-right">Taxable Val</th>
                <th style="width: 10%;" class="text-right">GST Tax</th>
                <th style="width: 14%;" class="text-right">Total Invoice</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
                @php
                    $tax = $order->is_gst ? (float) ($order->tax_amount ?? 0) : 0;
                    $taxable = max(0, (float) $order->final_amount - $tax);
                    $gstin = $order->customer?->gst_number ?: ($order->is_gst ? 'URP' : '-');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $order->created_at->format('d/m/Y') }}</td>
                    <td><strong>{{ $order->order_no }}</strong></td>
                    <td>{{ $order->customer?->name ?: 'Walk-in Customer' }}</td>
                    <td><code>{{ $gstin }}</code></td>
                    <td class="text-right">{{ number_format($taxable, 2) }}</td>
                    <td class="text-right">{{ number_format($tax, 2) }}</td>
                    <td class="text-right"><strong>{{ number_format((float) $order->final_amount, 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px;">No sales transactions found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
        @if($orders->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right fw-bold">TOTAL:</td>
                    <td class="text-right fw-bold">{{ number_format($totalTaxable, 2) }}</td>
                    <td class="text-right fw-bold">{{ number_format($totalTax, 2) }}</td>
                    <td class="text-right fw-bold">{{ number_format($totalSales, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
