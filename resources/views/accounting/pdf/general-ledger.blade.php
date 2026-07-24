<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>General Ledger</title>
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
                <div class="report-title">GENERAL LEDGER</div>
                <div class="report-meta">Generated Date: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td style="width: 50%;">
                <div class="summary-label">Total Credit (In)</div>
                <div class="summary-value">{{ format_price($totalCredit) }}</div>
            </td>
            <td style="width: 50%;">
                <div class="summary-label">Total Debit (Out)</div>
                <div class="summary-value">{{ format_price($totalDebit) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 11%;">Source</th>
                <th style="width: 8%;">Balance Type</th>
                @if(!$isRestricted)
                    <th style="width: 12%;">Location</th>
                @endif
                <th style="width: {{ $isRestricted ? 33 : 21 }}%;">Particulars</th>
                <th style="width: 8%;">Type</th>
                <th style="width: 12%;" class="text-right">Amount</th>
                <th style="width: 12%;">Done By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['date']->format('d/m/Y') }}</td>
                    <td>{{ $row['source'] }}</td>
                    <td>{{ $row['balance_type'] }}</td>
                    @if(!$isRestricted)
                        <td>{{ $row['location'] }}</td>
                    @endif
                    <td>{{ $row['particulars'] }}</td>
                    <td>{{ $row['is_credit'] ? 'Credit' : 'Debit' }}</td>
                    <td class="text-right fw-bold {{ $row['is_credit'] ? 'text-success' : 'text-danger' }}">{{ format_price($row['amount']) }}</td>
                    <td>{{ $row['done_by'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isRestricted ? 8 : 9 }}" class="text-center" style="padding: 15px;">No records found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
