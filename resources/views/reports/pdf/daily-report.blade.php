<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Report - {{ date('d-m-Y', strtotime($date)) }}</title>
    <style>
        @page { size: A4 landscape; margin: 12px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8.5px; color: #222; margin: 0; padding: 0; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 4px; }
        .company-name { font-size: 15px; font-weight: bold; color: #111; letter-spacing: 0.5px; }
        .report-title { font-size: 12px; font-weight: bold; color: #444; text-transform: uppercase; text-align: right; }
        .report-meta { font-size: 8px; color: #555; margin-top: 2px; }
        
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary-box td { border: 1px solid #bbb; padding: 5px 8px; background: #f8f9fa; }
        .summary-label { font-size: 7.5px; color: #555; text-transform: uppercase; font-weight: bold; }
        .summary-value { font-size: 10px; font-weight: bold; color: #111; margin-top: 2px; }
        
        .section-title { font-size: 10px; font-weight: bold; margin-top: 10px; margin-bottom: 4px; text-transform: uppercase; color: #333; border-bottom: 1px solid #7367f0; padding-bottom: 2px; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #999; padding: 4px 5px; text-align: left; font-size: 8px; }
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
                <div class="report-title">DAILY REPORT</div>
                <div class="report-meta">
                    Report Date: {{ date('d-m-Y', strtotime($date)) }}
                    @if($selectedLocation)
                        | Location: {{ $selectedLocation->name }}
                    @endif
                    <br>Generated Date: {{ date('d-m-Y h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td style="width: 25%;">
                <div class="summary-label">Total Sales</div>
                <div class="summary-value" style="color: #28c76f;">{{ format_price($totalSales) }}</div>
                <small style="color: #666;">({{ $totalSalesCount }} transactions)</small>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Purchases</div>
                <div class="summary-value" style="color: #7367f0;">{{ format_price($totalPurchases) }}</div>
                <small style="color: #666;">({{ $totalPurchasesCount }} purchases)</small>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Expenses</div>
                <div class="summary-value" style="color: #ea5455;">{{ format_price($totalExpenses) }}</div>
                <small style="color: #666;">({{ $totalExpensesCount }} expenses)</small>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Transfers</div>
                <div class="summary-value" style="color: #ff9f43;">{{ $totalTransfersCount }} Bills</div>
                <small style="color: #666;">({{ $totalTransfersQty }} items transfered)</small>
            </td>
        </tr>
    </table>

    @if(count($branchRows) > 0)
        <div class="section-title">Branch Overview</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Branch</th>
                    <th style="width: 20%;" class="text-right">Sales Amount</th>
                    <th style="width: 20%;" class="text-right">Purchases Amount</th>
                    <th style="width: 20%;" class="text-right">Expenses Amount</th>
                    <th style="width: 20%;" class="text-right">Transfer Count / Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($branchRows as $row)
                    <tr>
                        <td><strong>{{ $row['location_name'] }}</strong></td>
                        <td class="text-right">{{ number_format($row['sales_amount'], 2) }} <small>({{ $row['sales_count'] }})</small></td>
                        <td class="text-right">{{ number_format($row['purchase_amount'], 2) }} <small>({{ $row['purchase_count'] }})</small></td>
                        <td class="text-right">{{ number_format($row['expense_amount'], 2) }} <small>({{ $row['expense_count'] }})</small></td>
                        <td class="text-right">{{ $row['transfer_count'] }} <small>({{ $row['transfer_qty'] }} pcs)</small></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Sales Transactions ({{ count($salesRows) }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 14%;">Sale No</th>
                <th style="width: 22%;">Customer</th>
                <th style="width: 16%;">Location</th>
                <th style="width: 12%;">Source</th>
                <th style="width: 16%;">Payment Method</th>
                <th style="width: 16%;" class="text-right">Final Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesRows as $sale)
                <tr>
                    <td class="text-center">{{ $sale['index'] }}</td>
                    <td><strong>{{ $sale['sale_no'] }}</strong></td>
                    <td>{{ $sale['customer'] }}</td>
                    <td>{{ $sale['location'] }}</td>
                    <td>{{ $sale['source'] }}</td>
                    <td>{{ $sale['method'] }}</td>
                    <td class="text-right"><strong>{{ number_format($sale['amount'], 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 8px;">No sales transactions found for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Purchase Transactions ({{ count($purchaseRows) }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 20%;">Purchase No</th>
                <th style="width: 40%;">Supplier</th>
                <th style="width: 35%;" class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseRows as $purchase)
                <tr>
                    <td class="text-center">{{ $purchase['index'] }}</td>
                    <td><strong>{{ $purchase['purchase_no'] }}</strong></td>
                    <td>{{ $purchase['supplier'] }}</td>
                    <td class="text-right"><strong>{{ number_format($purchase['total_amount'], 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding: 8px;">No purchase transactions found for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Expenses ({{ count($expenseRows) }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 25%;">Title</th>
                <th style="width: 18%;">Category</th>
                <th style="width: 18%;">Location</th>
                <th style="width: 14%;">Payment Method</th>
                <th style="width: 20%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenseRows as $expense)
                <tr>
                    <td class="text-center">{{ $expense['index'] }}</td>
                    <td><strong>{{ $expense['title'] }}</strong></td>
                    <td>{{ $expense['category'] }}</td>
                    <td>{{ $expense['location'] }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $expense['payment_method'])) }}</td>
                    <td class="text-right"><strong>{{ number_format($expense['amount'], 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 8px;">No expenses found for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Purchase Bills / Transfers ({{ count($purchaseBillRows) }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 18%;">Bill No</th>
                <th style="width: 22%;">Source</th>
                <th style="width: 22%;">Destination</th>
                <th style="width: 13%;" class="text-center">Total Quantity</th>
                <th style="width: 20%;" class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseBillRows as $bill)
                <tr>
                    <td class="text-center">{{ $bill['index'] }}</td>
                    <td><strong>{{ $bill['bill_no'] }}</strong></td>
                    <td>{{ $bill['source'] }}</td>
                    <td>{{ $bill['destination'] }}</td>
                    <td class="text-center">{{ number_format($bill['total_quantity']) }}</td>
                    <td class="text-right"><strong>{{ number_format($bill['amount'], 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 8px;">No purchase bills / transfers found for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
