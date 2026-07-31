<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Report</title>
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
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name">CHETAN IMITATION</div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="report-title">CUSTOMER REPORT</div>
                <div class="report-meta">Generated Date: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td style="width: 25%;">
                <div class="summary-label">Total Customers</div>
                <div class="summary-value">{{ $totalCustomers }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Credit Customers</div>
                <div class="summary-value">{{ $totalCreditCustomers }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Balance</div>
                <div class="summary-value">{{ format_price($totalWalletBalance) }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Credit / Debit</div>
                <div class="summary-value">{{ format_price($totalCredit) }} / {{ format_price($totalDebit) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 24%;">Name</th>
                <th style="width: 16%;">Phone</th>
                <th style="width: 14%;">Type</th>
                <th style="width: 14%;" class="text-right">Total Credit</th>
                <th style="width: 14%;" class="text-right">Total Debit</th>
                <th style="width: 14%;" class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $index => $customer)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->phone ?? '-' }}</td>
                    <td>{{ $customer->is_credit_customer ? 'Credit Customer' : 'Regular' }}</td>
                    <td class="text-right">{{ $customer->is_credit_customer ? number_format((float) $customer->period_credit, 2) : '-' }}</td>
                    <td class="text-right">{{ $customer->is_credit_customer ? number_format((float) $customer->period_debit, 2) : '-' }}</td>
                    <td class="text-right fw-bold">{{ $customer->is_credit_customer ? number_format((float) $customer->balance, 2) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px;">No customers found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
