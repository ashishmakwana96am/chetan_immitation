<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cash Ledger</title>
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
        .text-success { color: #28c76f; }
        .text-danger { color: #ea5455; }

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
                <div class="report-title">CASH LEDGER</div>
                <div class="report-meta">Generated Date: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td style="width: 33.33%;">
                <div class="summary-label">Total Sale (In)</div>
                <div class="summary-value">{{ format_price($totalIn) }}</div>
            </td>
            <td style="width: 33.33%;">
                <div class="summary-label">Total Expense (Out)</div>
                <div class="summary-value">{{ format_price($totalOut) }}</div>
            </td>
            <td style="width: 33.33%;">
                <div class="summary-label">Current Cash Balance</div>
                <div class="summary-value">{{ format_price($currentBalance) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 15%;">Date</th>
                <th style="width: 20%;" class="text-right">Opening</th>
                <th style="width: 20%;" class="text-right">Sale</th>
                <th style="width: 20%;" class="text-right">Expense</th>
                <th style="width: 20%;" class="text-right">Closing</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                    <td class="text-right {{ $row['opening'] < 0 ? 'text-danger fw-bold' : '' }}">{{ format_price($row['opening']) }}</td>
                    <td class="text-right text-success fw-bold">{{ format_price($row['in']) }}</td>
                    <td class="text-right text-danger fw-bold">{{ format_price($row['out']) }}</td>
                    <td class="text-right {{ $row['closing'] < 0 ? 'text-danger fw-bold' : '' }}">{{ format_price($row['closing']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 15px;">No records found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right fw-bold">TOTAL:</td>
                    <td class="text-right fw-bold">{{ format_price($totalIn) }}</td>
                    <td class="text-right fw-bold">{{ format_price($totalOut) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
