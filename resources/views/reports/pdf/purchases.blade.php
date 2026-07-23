<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Register Report</title>
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
                    <strong>Report:</strong> Purchase Register Statement<br>
                    <strong>Period:</strong> {{ $startDate ? format_date($startDate) : 'All Time' }} to {{ $endDate ? format_date($endDate) : 'Today' }}
                </div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="report-title">PURCHASE REGISTER</div>
                <div class="report-meta">Generated Date: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    @php
        $totalPurchases = $invoices->sum('total_amount');
        $totalPaid = $invoices->sum('paid_amount');
        $totalDue = max(0.0, $totalPurchases - $totalPaid);
        $totalCount = $invoices->count();
        $totalTax = 0;
        $totalTaxable = 0;
        foreach($invoices as $inv) {
            $tax = $inv->is_gst ? (float) ($inv->tax_amount ?? 0) : 0;
            $taxable = max(0, (float) $inv->total_amount - $tax);
            $totalTax += $tax;
            $totalTaxable += $taxable;
        }
    @endphp

    <table class="summary-box">
        <tr>
            <td style="width: 16%;">
                <div class="summary-label">Total Invoices</div>
                <div class="summary-value">{{ $totalCount }}</div>
            </td>
            <td style="width: 21%;">
                <div class="summary-label">Total Taxable Value</div>
                <div class="summary-value">{{ format_price($totalTaxable) }}</div>
            </td>
            <td style="width: 21%;">
                <div class="summary-label">Total Tax Amount (GST)</div>
                <div class="summary-value">{{ format_price($totalTax) }}</div>
            </td>
            <td style="width: 21%;">
                <div class="summary-label">Total Purchase Value</div>
                <div class="summary-value">{{ format_price($totalPurchases) }}</div>
            </td>
            <td style="width: 21%;">
                <div class="summary-label">Total Paid / Outstanding</div>
                <div class="summary-value">{{ format_price($totalPaid) }} / {{ format_price($totalDue) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 9%;">Date</th>
                <th style="width: 12%;">Invoice No</th>
                <th style="width: 22%;">Supplier Name</th>
                <th style="width: 13%;">GSTIN</th>
                <th style="width: 10%;" class="text-right">Taxable Val</th>
                <th style="width: 9%;" class="text-right">GST Tax</th>
                <th style="width: 11%;" class="text-right">Total Invoice</th>
                <th style="width: 10%;" class="text-right">Paid Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $index => $invoice)
                @php
                    $tax = $invoice->is_gst ? (float) ($invoice->tax_amount ?? 0) : 0;
                    $taxable = max(0, (float) $invoice->total_amount - $tax);
                    $gstin = $invoice->supplier?->gst_number ?: ($invoice->is_gst ? 'URP' : '-');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                    <td><strong>{{ $invoice->invoice_no }}</strong></td>
                    <td>{{ $invoice->supplier?->name ?: '-' }}</td>
                    <td><code>{{ $gstin }}</code></td>
                    <td class="text-right">{{ number_format($taxable, 2) }}</td>
                    <td class="text-right">{{ number_format($tax, 2) }}</td>
                    <td class="text-right"><strong>{{ number_format((float) $invoice->total_amount, 2) }}</strong></td>
                    <td class="text-right">{{ number_format((float) $invoice->paid_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 15px;">No purchase transactions found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
        @if($invoices->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right fw-bold">TOTAL:</td>
                    <td class="text-right fw-bold">{{ number_format($totalTaxable, 2) }}</td>
                    <td class="text-right fw-bold">{{ number_format($totalTax, 2) }}</td>
                    <td class="text-right fw-bold">{{ number_format($totalPurchases, 2) }}</td>
                    <td class="text-right fw-bold">{{ number_format($totalPaid, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
