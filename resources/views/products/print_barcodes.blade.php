<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Labels</title>
    <style>
        @page {
            size: 232.44pt {{ $pdfHeight }}pt;
            margin: 0px !important;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Lucida Console', Monaco, monospace !important;
            color: #000;
            line-height: 1 !important;
        }
        html, body {
            margin: 0px !important;
            padding: 0px !important;
            width: 232.44pt !important;
            height: {{ $pdfHeight }}pt !important;
            background: #fff;
            overflow: hidden !important;
        }
        .labels-container-table {
            width: 232.44pt !important; /* 2.7cm + 2.7cm + 2.8cm = 8.2cm = 232.44pt */
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        .label-row {
            height: 34.02pt !important; /* 1.2cm height */
            max-height: 34.02pt !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .zone-front-td {
            width: 76.54pt !important; /* 2.7cm */
            height: 34.02pt !important;
            padding: 1pt 3pt !important;
            vertical-align: middle;
            border: none !important;
        }
        .zone-back-td {
            width: 76.54pt !important; /* 2.7cm */
            height: 34.02pt !important;
            padding: 1pt 2pt !important;
            text-align: center;
            vertical-align: middle;
            border: none !important;
        }
        .zone-tail-td {
            width: 79.38pt !important; /* 2.8cm */
            height: 34.02pt !important;
            padding: 0 !important;
            border: none !important;
        }
        .mrp-line {
            font-size: 7.5pt !important;
            font-weight: bold !important;
            line-height: 1.1 !important;
            white-space: nowrap;
            overflow: hidden;
            margin-bottom: 2pt !important;
        }
        .code-line {
            font-size: 7.5pt !important;
            font-weight: bold !important;
            line-height: 1.1 !important;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
        }
        .mrp-code-line {
            font-size: 7pt !important;
            font-weight: bold !important;
            line-height: 1.1 !important;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            margin-top: 2pt !important;
        }
        .category-line {
            font-size: 7.5pt !important;
            font-weight: bold !important;
            line-height: 1.1 !important;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            margin-top: 2pt !important;
        }
        .barcode-container {
            width: 100% !important;
            height: 14pt !important;
            overflow: hidden;
            text-align: left;
            padding-left: 2pt;
        }
        .barcode-img {
            width: 100% !important;
            height: 14pt !important;
            display: block;
        }
    </style>
</head>
<body>
    <table class="labels-container-table" cellpadding="0" cellspacing="0">
        @foreach($printItems as $item)
            @for($i = 0; $i < $item['qty']; $i++)
                <tr class="label-row">
                    <!-- Zone 1: Code + Barcode + Category (Left Section) -->
                    <td class="zone-front-td" style="text-align: left;">
                        <div class="code-line" style="text-align: left !important; margin-bottom: 2pt !important; padding-left: 2pt !important;">{{ trim($item['barcodeText']) }}</div>
                        <div class="barcode-container" style="text-align: left !important;">
                            <img class="barcode-img" src="{{ $item['barcodeBase64'] }}" style="margin: 0;" />
                        </div>
                        <div class="category-line" style="text-align: left !important; margin-top: 2pt !important; padding-left: 2pt !important; font-size: {{ $item['categoryFontSize'] ?? 7.5 }}pt !important;">{{ trim($item['category']) }}</div>
                    </td>
                    <!-- Zone 2: MRP (Center Section) -->
                    <td class="zone-back-td">
                        <div class="mrp-line" style="text-align: left; margin: 0; padding-left: 6pt;">MRP:{{ $item['salePrice'] }}</div>
                        <div class="mrp-code-line" style="text-align: left; margin: 0; padding-left: 6pt;">{{ $item['productCode'] }}</div>
                        @if(!empty($item['isPair']))
                            <div class="mrp-code-line" style="text-align: left; margin: 0; padding-left: 6pt; margin-top: 1pt !important;">Pair</div>
                        @elseif(!empty($item['customSizeLabel']))
                            <div class="mrp-code-line" style="text-align: left; margin: 0; padding-left: 6pt; margin-top: 1pt !important;">{{ $item['customSizeLabel'] }}</div>
                        @endif
                    </td>
                    <!-- Zone 3: Blank Tail Area (Right Section) -->
                    <td class="zone-tail-td">
                        <!-- Tail section is completely blank -->
                    </td>
                </tr>
            @endfor
        @endforeach
    </table>
</body>
</html>
