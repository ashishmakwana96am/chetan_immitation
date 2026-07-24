<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit & Loss Report</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #222; margin: 0; padding: 0; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border-bottom: 2px solid #333; padding-bottom: 6px; }
        .company-name { font-size: 16px; font-weight: bold; color: #111; letter-spacing: 0.5px; }
        .report-title { font-size: 13px; font-weight: bold; color: #444; text-transform: uppercase; text-align: right; }
        .report-meta { font-size: 8.5px; color: #555; margin-top: 3px; }

        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .summary-box td { border: 1px solid #bbb; padding: 8px 10px; background: #f8f9fa; text-align: center; }
        .summary-label { font-size: 8px; color: #555; text-transform: uppercase; font-weight: bold; }
        .summary-value { font-size: 12px; font-weight: bold; color: #111; margin-top: 3px; }
        .text-success { color: #28c76f; }
        .text-danger { color: #ea5455; }
        .text-primary { color: #7367f0; }

        .section-title { font-size: 11px; font-weight: bold; color: #333; margin-bottom: 8px; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 4px; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data-table th, table.data-table td { border: 1px solid #aaa; padding: 5px 7px; text-align: left; font-size: 8.5px; }
        table.data-table th { background-color: #e9ecef; font-weight: bold; text-transform: uppercase; color: #222; border-bottom: 1.5px solid #555; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name">CHETAN IMITATION</div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="report-title">PROFIT & LOSS REPORT</div>
                <div class="report-meta">Generated Date: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td style="width: 25%;">
                <div class="summary-label">Total Revenue</div>
                <div class="summary-value text-success">{{ format_price($totalRevenue) }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Cost (COGS)</div>
                <div class="summary-value text-danger">{{ format_price($totalCogs) }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Expenses</div>
                <div class="summary-value text-danger">{{ format_price($totalExpenses) }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Net Profit / Margin</div>
                <div class="summary-value {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ format_price($netProfit) }} ({{ number_format($profitMargin, 1) }}%)
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Product Profitability Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 45%;">Product Name</th>
                <th style="width: 15%;">Barcode</th>
                <th style="width: 10%;" class="text-center">Qty Sold</th>
                <th style="width: 12%;" class="text-right">Total Revenue</th>
                <th style="width: 13%;" class="text-right">Est. Profit</th>
            </tr>
        </thead>
        <tbody>
            @php $index = 1; @endphp
            @forelse($productProfitability as $prod)
                @php
                    $profit = $prod['total_revenue'] - $prod['total_cost'];
                @endphp
                <tr>
                    <td class="text-center">{{ $index++ }}</td>
                    <td>{{ $prod['name'] }}</td>
                    <td><code>{{ $prod['barcode'] }}</code></td>
                    <td class="text-center">{{ $prod['qty_sold'] }}</td>
                    <td class="text-right">{{ number_format($prod['total_revenue'], 2) }}</td>
                    <td class="text-right {{ $profit >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                        {{ number_format($profit, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 15px;">No sales data available for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
