<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Labels</title>
    <style>
        @page {
            size: 232.44pt 34.02pt landscape;
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
            height: 34.02pt !important;
            background: #fff;
            overflow: hidden !important;
        }
        .page-break {
            page-break-before: always !important;
            break-before: page !important;
            clear: both !important;
        }
        .page-wrapper {
            width: 232.44pt !important;
            height: 34.02pt !important;
            position: relative !important;
            overflow: hidden !important;
        }
        .label-table {
            width: 232.44pt !important;
            height: 34.02pt !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .label-table td {
            padding: 0 !important;
            margin: 0 !important;
            vertical-align: middle !important;
            border: none !important;
        }
        .label-table td.zone-front {
            width: 76.54pt !important; /* 2.7cm */
            height: 34.02pt !important;
            padding: 1pt 2pt 0.8pt 2pt !important;
            overflow: hidden !important;
        }
        .label-table td.zone-back {
            width: 76.54pt !important; /* 2.7cm */
            height: 34.02pt !important;
            padding: 1.8pt 2pt 1pt 2pt !important;
            text-align: left !important;
            overflow: hidden !important;
        }
        .label-table td.zone-tail {
            width: 79.36pt !important; /* 2.8cm */
            height: 34.02pt !important;
            overflow: hidden !important;
            vertical-align: middle !important;
        }
        .zone-tail-content {
            width: 100% !important;
            height: 12.76pt !important;
            overflow: hidden !important;
        }
        .code-line {
            font-size: 8.2pt !important;
            font-weight: bold !important;
            line-height: 1.05 !important;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden !important;
            text-overflow: clip !important;
            max-width: 72pt !important;
            text-align: left !important;
            margin-bottom: 1pt !important;
            padding-left: 2pt !important;
        }
        .barcode-container {
            width: 100% !important;
            height: 13.5pt !important;
            overflow: hidden;
            text-align: left;
            padding-left: 5pt !important;
            padding-right: 5pt !important;
        }
        .barcode-img {
            width: 62pt !important;
            height: 13.5pt !important;
            display: block;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            image-rendering: pixelated;
        }
        .category-line {
            font-size: 8.2pt !important;
            font-weight: bold !important;
            line-height: 1.05 !important;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-align: left !important;
            margin-top: 1pt !important;
            padding-left: 2pt !important;
        }
        .variant-line {
            font-size: 7.5pt !important;
            font-weight: bold !important;
            line-height: 1.1 !important;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-align: left !important;
            margin-top: 1pt !important;
            padding-left: 2pt !important;
            letter-spacing: 0.2pt;
        }
        .mrp-line {
            font-size: 11.5pt !important;
            font-weight: bold !important;
            line-height: 1.05 !important;
            white-space: nowrap;
            overflow: hidden;
            text-align: left;
            padding-left: 6pt !important;
            margin-bottom: 1.2pt !important;
        }
        .mrp-code-line {
            font-size: 8.5pt !important;
            font-weight: bold !important;
            line-height: 1.05 !important;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-align: left;
            padding-left: 6pt !important;
            margin-top: 1pt !important;
        }
    </style>
</head>
<body>
    @php
        $counter = 0;
    @endphp
    @foreach($printItems as $item)
        @for($i = 0; $i < $item['qty']; $i++)
            @php $counter++; @endphp
            @if($counter > 1)
                <div class="page-break"></div>
            @endif
            <div class="page-wrapper">
                <table class="label-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <!-- Zone 1: Code + Barcode + Category (Left Section) -->
                        <td class="zone-front">
                            <div class="code-line">{{ trim($item['barcodeText']) }}@if(!empty($item['variantLabel'])) {{ trim($item['variantLabel']) }}@endif</div>
                            <div class="barcode-container">
                                <img class="barcode-img" src="{{ $item['barcodeBase64'] }}" />
                            </div>
                            <div class="category-line" style="font-size: {{ $item['categoryFontSize'] ?? 8.5 }}pt !important;">{{ trim($item['category']) }}</div>
                        </td>
                        <!-- Zone 2: MRP (Center Section) -->
                        <td class="zone-back">
                            <div class="mrp-line">MRP:{{ $item['mrp'] }}</div>
                            <div class="mrp-code-line">{{ $item['productCode'] }}</div>
                            @if(!empty($item['customSizeLabel']))
                                <div class="mrp-code-line">{{ $item['customSizeLabel'] }}</div>
                            @endif
                        </td>
                        <!-- Zone 3: Blank Tail Area (Right Section) - physical tail flap is only ~0.3-0.6cm tall -->
                        <td class="zone-tail"><div class="zone-tail-content"></div></td>
                    </tr>
                </table>
            </div>
        @endfor
    @endforeach
</body>
</html>
