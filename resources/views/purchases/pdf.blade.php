<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Purchase Invoice {{ $purchase->invoice_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }

        .page { padding: 30px; }

        /* Header */
        .header { width: 100%; margin-bottom: 30px; border-bottom: 2px solid #7367f0; padding-bottom: 20px; }
        .header table { margin-bottom: 0; }
        .header table td { border: none; padding: 0; vertical-align: top; }
        .header-right { text-align: right; }
        .company-name { font-size: 22px; font-weight: bold; color: #7367f0; }
        .company-sub { font-size: 11px; color: #888; margin-top: 4px; }
        .invoice-title h2 { font-size: 20px; color: #7367f0; text-transform: uppercase; }
        .invoice-title .invoice-no { font-size: 13px; font-weight: bold; margin-top: 4px; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-draft     { background: #e0e0e0; color: #555; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Info section */
        .info-section { width: 100%; margin-bottom: 25px; }
        .info-section table { margin-bottom: 0; }
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
        tfoot td { padding: 8px 10px; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Totals */
        .totals-section { width: 250px; float: right; margin-bottom: 20px; }
        .totals-row { width: 100%; border-bottom: 1px solid #eee; padding: 5px 0; }
        .totals-row table { margin-bottom: 0; }
        .totals-row table td { border: none; padding: 2px 0; }
        .totals-grand { font-size: 14px; font-weight: bold; color: #7367f0; border-top: 2px solid #7367f0; border-bottom: none; padding-top: 8px; }

        /* Allocations */
        .alloc-badge { display: inline-block; background: #e8f4fd; color: #1a73e8; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin: 1px; }

        /* Footer */
        .footer { margin-top: 40px; border-top: 1px solid #eee; padding-top: 15px; text-align: center; color: #aaa; font-size: 10px; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <table style="width:100%;">
            <tr>
                <td>
                    <div class="company-name">Chetan Immitation</div>
                    <div class="company-sub">Purchase Management System</div>
                </td>
                <td class="header-right">
                    <div class="invoice-title">
                        <h2>Purchase Invoice</h2>
                        <div class="invoice-no">{{ $purchase->invoice_no }}</div>
                        <div style="margin-top:6px;">
                            @php
                                $statusClass = ['draft' => 'status-draft', 'confirmed' => 'status-confirmed', 'cancelled' => 'status-cancelled'];
                            @endphp
                            <span class="status-badge {{ $statusClass[$purchase->status] ?? 'status-draft' }}">
                                {{ ucfirst($purchase->status) }}
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
                        <h4>Supplier Details</h4>
                        <p><strong>{{ $purchase->supplier->name ?? '-' }}</strong></p>
                        @if($purchase->supplier?->phone)
                            <p><span class="label">Phone:</span> {{ $purchase->supplier->phone }}</p>
                        @endif
                        @if($purchase->supplier?->address)
                            <p><span class="label">Address:</span> {{ $purchase->supplier->address }}</p>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="info-box-right">
                        <h4>Invoice Details</h4>
                        <p><span class="label">Invoice No:</span> <strong>{{ $purchase->invoice_no }}</strong></p>
                        <p><span class="label">Date:</span> {{ format_date($purchase->created_at) }}</p>
                        <p><span class="label">Created By:</span> {{ $purchase->createdBy->name ?? '-' }}</p>
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
                <th class="text-right">Purchase Price</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Total</th>
                <th>Allocations</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $item->product->name ?? '-' }}</strong></td>
                    <td>{{ $item->product->sku ?? '-' }}</td>
                    <td class="text-right">{{ format_price($item->purchase_price) }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right"><strong>{{ format_price($item->total) }}</strong></td>
                    <td>
                        @foreach($item->allocations as $allocation)
                            <span class="alloc-badge">{{ $allocation->location->name ?? '-' }}: {{ $allocation->quantity }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Grand Total -->
    <div class="totals-section">
        <table style="width:100%; border-top: 2px solid #7367f0; padding-top: 8px;">
            <tr>
                <td style="font-size:14px; font-weight:bold; color:#7367f0; padding: 8px 0;">Grand Total</td>
                <td style="font-size:14px; font-weight:bold; color:#7367f0; text-align:right; padding: 8px 0;">{{ format_price($purchase->total_amount) }}</td>
            </tr>
        </table>
    </div>
    <div style="clear:both;"></div>

    <!-- Footer -->
    <div class="footer">
        <p>Generated on {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; {{ config('app.name') }}</p>
    </div>

</div>
</body>
</html>
