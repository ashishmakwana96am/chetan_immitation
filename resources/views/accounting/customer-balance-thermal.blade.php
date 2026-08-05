<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Balance Receipt #{{ $transaction->id }}</title>
    <style>
        @page {
            margin: 0px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
            color: #000;
            font-weight: bold;
        }

        html, body {
            width: 216pt;
            @if(!empty($pdfHeight))
            height: {{ $pdfHeight }}pt;
            overflow: hidden;
            @endif
            background: #fff;
        }

        body {
            padding: 8pt;
        }

        .receipt-container {
            width: 200pt;
            @if(!empty($pdfHeight))
            height: {{ $pdfHeight - 16 }}pt;
            @endif
            position: relative;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }

        .store-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .gstin-label {
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px;
        }

        .divider-dotted {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .divider-solid {
            border-top: 1px solid #000;
            margin: 5px 0;
        }

        .row-line {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            line-height: 1.4;
        }

        .row-line td {
            border: none;
            padding: 2px 0;
        }

        .amount-box {
            text-align: center;
            font-size: 16px;
            margin: 6px 0;
        }
    </style>
</head>
<body>

    @php
        $isCredit = $transaction->type === \App\Models\CustomerBalanceTransaction::TYPE_CREDIT;
        $location = $transaction->customer->location ?? null;
    @endphp

    <div class="receipt-container">

        <div class="text-center">
            <div class="store-title">Chetan Imitation</div>
            @if($location?->gst_number)
                <div class="gstin-label">GSTIN: {{ $location->gst_number }}</div>
            @endif
        </div>

        <div class="divider-dotted" style="margin-top: 3px; margin-bottom: 3px;"></div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2px;">
            <tr>
                <td style="width: 65px; vertical-align: middle; border: none; padding: 0;">
                    <img src="{{ public_path('assets/img/thermal-logo.png') }}" style="width: 58px; height: 58px; object-fit: contain;" alt="Logo" />
                </td>
                <td style="vertical-align: middle; border: none; padding-left: 8px; text-align: left; font-size: 11px; font-weight: bold; line-height: 1.35;">
                    @php
                        $locations = \App\Models\Location::orderByRaw('id = ? DESC', [$location->id ?? 0])->limit(3)->get();
                    @endphp
                    @foreach($locations as $loc)
                        <div>{{ strtoupper($loc->name) }} - {{ $loc->phone ?: '7725978871' }}</div>
                    @endforeach
                </td>
            </tr>
        </table>

        <div class="divider-dotted" style="margin-top: 3px; margin-bottom: 3px;"></div>

        <div class="text-center" style="font-size: 13px;">CUSTOMER BALANCE RECEIPT</div>

        <div class="divider-dotted"></div>

        <table class="row-line">
            <tr>
                <td style="width: 50%;">RECEIPT NO : {{ $transaction->id }}</td>
                <td style="width: 50%; text-align: right;">DATE : {{ $transaction->created_at->format('d/m/y') }}</td>
            </tr>
            <tr>
                <td style="width: 50%;">TIME : {{ $transaction->created_at->format('h:i:s A') }}</td>
                <td style="width: 50%; text-align: right;"></td>
            </tr>
        </table>

        <div class="divider-solid"></div>

        <table class="row-line">
            <tr>
                <td colspan="2" style="text-transform: uppercase;">NAME : {{ $transaction->customer->name ?? '-' }}</td>
            </tr>
            @if($transaction->customer?->phone)
            <tr>
                <td colspan="2">PH : {{ $transaction->customer->phone }}</td>
            </tr>
            @endif
        </table>

        <div class="divider-dotted"></div>

        <table class="row-line">
            <tr>
                <td style="width: 50%;">TYPE : {{ $isCredit ? 'CREDIT' : 'DEBIT' }}</td>
                <td style="width: 50%; text-align: right;">MODE : {{ strtoupper($transaction->source) }}</td>
            </tr>
        </table>

        <div class="amount-box">
            {{ number_format((float) $transaction->amount, 2) }}
        </div>

        <div class="divider-dotted"></div>

        <table class="row-line">
            <tr>
                <td style="width: 60%;">CASH BALANCE</td>
                <td style="width: 40%; text-align: right;">{{ number_format((float) $transaction->customer->cash_balance, 2) }}</td>
            </tr>
            <tr>
                <td style="width: 60%;">BANK BALANCE</td>
                <td style="width: 40%; text-align: right;">{{ number_format((float) $transaction->customer->bank_balance, 2) }}</td>
            </tr>
            <tr style="border-top: 1px dashed #000;">
                <td style="width: 60%; padding-top: 3px;">TOTAL BALANCE</td>
                <td style="width: 40%; text-align: right; padding-top: 3px;">{{ number_format((float) $transaction->customer->balance, 2) }}</td>
            </tr>
        </table>

        @if(!empty($transaction->notes) && $transaction->notes !== 'Manual Customer Balance Adjustment')
        <div class="divider-dotted"></div>
        <div style="font-size: 11px;">NOTE : {{ $transaction->notes }}</div>
        @endif

        <div class="divider-dotted"></div>

        <table class="row-line">
            <tr>
                <td>DONE BY : {{ $transaction->createdBy->name ?? '-' }}</td>
            </tr>
        </table>

        <div class="divider-dotted" style="margin-top: 6px; margin-bottom: 6px;"></div>

        <div class="text-center" style="font-size: 11px; font-family: Arial, sans-serif;">
            <div>Thank you for shopping by chetan imitation!</div>
        </div>

    </div>

</body>
</html>
