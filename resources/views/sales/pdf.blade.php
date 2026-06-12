<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Sale {{ $order->order_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .page { padding: 30px; }

        /* Header */
        .header { width: 100%; margin-bottom: 30px; border-bottom: 2px solid #B4771E; padding-bottom: 20px; }
        .header table { width: 100%; margin-bottom: 0; }
        .header table td { border: none; padding: 0; vertical-align: top; }
        .header-right { text-align: right; }
        .company-name { font-size: 22px; font-weight: bold; color: #B4771E; }
        .company-sub { font-size: 11px; color: #888; margin-top: 4px; }
        .sale-title h2 { font-size: 20px; color: #B4771E; text-transform: uppercase; }
        .sale-no { font-size: 13px; font-weight: bold; margin-top: 4px; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-pending   { background: #e0e0e0; color: #555; }
        .status-approve   { background: #d4edda; color: #155724; }
        .status-decline   { background: #f8d7da; color: #721c24; }

        /* Info section */
        .info-section { width: 100%; margin-bottom: 25px; }
        .info-section table { width: 100%; margin-bottom: 0; }
        .info-section table td { border: none; padding: 0; vertical-align: top; width: 50%; }
        .info-box { padding-right: 10px; }
        .info-box-right { padding-left: 10px; text-align: right; }
        .info-box h4, .info-box-right h4 { font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .info-box p, .info-box-right p { margin-bottom: 4px; font-size: 12px; }
        .label { color: #888; font-size: 11px; }

        /* Items table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #B4771E; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
        tbody td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }

        /* Totals */
        .totals-section { width: 250px; float: right; margin-bottom: 20px; }
        .totals-row { width: 100%; }
        .totals-row table { margin-bottom: 0; }
        .totals-row table td { border: none; padding: 4px 0; font-size: 12px; }
        .totals-grand table td { font-size: 14px; font-weight: bold; color: #B4771E; border-top: 2px solid #B4771E; padding-top: 8px; }

        /* Footer */
        .footer { margin-top: 40px; border-top: 1px solid #eee; padding-top: 15px; text-align: center; color: #aaa; font-size: 10px; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="company-name">Chetan Imitation</div>
                    <div class="company-sub">Sales Management System</div>
                </td>
                <td class="header-right">
                    <div class="sale-title">
                        <h2>Sale Invoice</h2>
                        <div class="sale-no">{{ $order->order_no }}</div>
                        <div style="margin-top:6px;">
                            @php
                                $statusClass = [
                                    1 => 'status-pending',
                                    2 => 'status-approve',
                                    3 => 'status-decline',
                                ];
                                $statusLabels = [
                                    1 => 'Pending',
                                    2 => 'Approve',
                                    3 => 'Decline',
                                ];
                            @endphp
                            <span class="status-badge {{ $statusClass[$order->status] ?? 'status-pending' }}">
                                {{ $statusLabels[$order->status] ?? 'Pending' }}
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
                        <h4>Customer Details</h4>
                        <p><strong>{{ $order->customer->name ?? 'Walk-in Customer' }}</strong></p>
                        @if($order->customer?->phone)
                            <p><span class="label">Phone:</span> {{ $order->customer->phone }}</p>
                        @endif
                        @if($order->customer?->email)
                            <p><span class="label">Email:</span> {{ $order->customer->email }}</p>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="info-box-right">
                        <h4>Sale Details</h4>
                        <p><span class="label">Sale No:</span> <strong>{{ $order->order_no }}</strong></p>
                        <p><span class="label">Date:</span> {{ format_date($order->created_at) }}</p>
                        <p><span class="label">Location:</span> {{ $order->location->name ?? '-' }}</p>
                        <p><span class="label">Served By:</span> {{ $order->user->name ?? '-' }}</p>
                        <p><span class="label">Payment:</span> {{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</p>
                        @php
                            $payLabels = [1 => 'Pending', 2 => 'Paid'];
                        @endphp
                        <p><span class="label">Payment Status:</span> {{ $payLabels[$order->payment_status ?? 1] ?? 'Pending' }}</p>
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
                <th class="text-right">Price</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $preparedItems = collect();
                $groupedByProduct = $order->items->groupBy('product_id');

                foreach ($groupedByProduct as $productId => $siblings) {
                    $siblings = $siblings->sortBy('id')->values();
                    $firstItem = $siblings->first();
                    $product = $firstItem->product ?? null;
                    
                    if ($product && $product->type === 'variable') {
                        $parentItem = $firstItem;
                        $parentItem->is_parent = true;
                        $parentItem->resolved_variant_name = null;
                        
                        $variantItems = $siblings->slice(1)->values();
                        $variants = $product->variants ?? collect();
                        
                        $matchedMap = [];
                        $unmatchedSiblings = $variantItems->all();
                        
                        foreach ($variants as $v) {
                            $matchedIdx = -1;
                            foreach ($unmatchedSiblings as $idx => $sibling) {
                                if (isset($sibling) && (float)$sibling->price === (float)$v->sale_price) {
                                    $matchedIdx = $idx;
                                    break;
                                }
                            }
                            if ($matchedIdx !== -1) {
                                $matchedSibling = $unmatchedSiblings[$matchedIdx];
                                $variantName = null;
                                if ($v->attributeValue) {
                                    $variantName = ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '');
                                }
                                $matchedSibling->resolved_variant_name = $variantName;
                                $matchedSibling->is_parent = false;
                                $matchedMap[$matchedSibling->id] = $matchedSibling;
                                unset($unmatchedSiblings[$matchedIdx]);
                            }
                        }
                        
                        $unmatchedSiblings = array_values($unmatchedSiblings);
                        $unmatchedVariants = [];
                        foreach ($variants as $v) {
                            $variantName = null;
                            if ($v->attributeValue) {
                                $variantName = ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '');
                            }
                            
                            $alreadyMatched = false;
                            foreach ($matchedMap as $ms) {
                                if ($ms->resolved_variant_name === $variantName) {
                                    $alreadyMatched = true;
                                    break;
                                }
                            }
                            
                            if (!$alreadyMatched) {
                                $unmatchedVariants[] = $v;
                            }
                        }
                        
                        foreach ($unmatchedSiblings as $idx => $sibling) {
                            if (isset($unmatchedVariants[$idx])) {
                                $v = $unmatchedVariants[$idx];
                                $variantName = null;
                                if ($v->attributeValue) {
                                    $variantName = ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '');
                                }
                                $sibling->resolved_variant_name = $variantName;
                            } else {
                                $sibling->resolved_variant_name = null;
                            }
                            $sibling->is_parent = false;
                            $matchedMap[$sibling->id] = $sibling;
                        }
                        
                        $preparedItems->push($parentItem);
                        foreach ($variantItems as $vItem) {
                            $preparedItems->push($matchedMap[$vItem->id] ?? $vItem);
                        }
                    } else {
                        foreach ($siblings as $sibling) {
                            $sibling->is_parent = true;
                            $sibling->resolved_variant_name = null;
                            $preparedItems->push($sibling);
                        }
                    }
                }
            @endphp

            @foreach($preparedItems as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td @if(!$item->is_parent) style="padding-left: 35px;" @endif>
                        @if(!$item->is_parent)
                            <span style="color: #888; font-weight: bold; margin-right: 5px;">↳</span>
                            <span style="font-size: 11px; color: #666;">{{ $item->resolved_variant_name }}</span>
                        @else
                            <strong>{{ $item->product->name ?? '-' }}</strong>
                        @endif
                    </td>
                    <td>
                        @if($item->is_parent)
                            {{ $item->product->sku ?? '-' }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ format_price($item->price) }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">
                        @if($item->discount_amount > 0)
                            @if($item->discount_type === 'percentage')
                                {{ number_format($item->discount_value, 2) }}% (-{{ format_price($item->discount_amount) }})
                            @else
                                -{{ format_price($item->discount_amount) }}
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right"><strong>{{ format_price($item->total) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $totalItemDiscount = $order->items->sum('discount_amount');
        $subtotal = $order->final_amount + $totalItemDiscount;
    @endphp
    <!-- Totals -->
    <div class="totals-section">
        <div class="totals-row">
            <table>
                <tr>
                    <td>Items Total</td>
                    <td class="text-right">{{ format_price($subtotal) }}</td>
                </tr>
                @if($totalItemDiscount > 0)
                <tr>
                    <td style="color:#ea5455;">Discount</td>
                    <td class="text-right" style="color:#ea5455;">-{{ format_price($totalItemDiscount) }}</td>
                </tr>
                @endif
            </table>
        </div>
        <div class="totals-grand">
            <table>
                <tr>
                    <td>Final Amount</td>
                    <td class="text-right">{{ format_price($order->final_amount) }}</td>
                </tr>
            </table>
        </div>
    </div>
    <div style="clear:both;"></div>

    <!-- Footer -->
    <div class="footer">
        <p>Generated on {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; {{ config('app.name') }}</p>
    </div>

</div>
</body>
</html>
