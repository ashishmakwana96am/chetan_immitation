@extends('layouts.app')

@section('title', $product->name)

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/swiper/swiper.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #purchaseHistoryTable tbody tr.group-header td,
        #transferHistoryTable tbody tr.group-header td,
        #saleHistoryTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #purchaseHistoryTable tbody tr.group-header td .group-header-inner,
        #transferHistoryTable tbody tr.group-header td .group-header-inner,
        #saleHistoryTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #purchaseHistoryTable tbody tr.group-header td .group-header-inner i,
        #transferHistoryTable tbody tr.group-header td .group-header-inner i,
        #saleHistoryTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #purchaseHistoryTable tbody tr.group-header td .group-header-inner span,
        #transferHistoryTable tbody tr.group-header td .group-header-inner span,
        #saleHistoryTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
    </style>
    <style>
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 7px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 0.875rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #8592a3; font-size: 0.8rem; flex-shrink: 0; padding-right: 8px; }
        .info-value { font-weight: 500; text-align: right; }
        .card-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #B4771E;
            margin-bottom: 4px;
        }
        .card { border: 1px solid rgba(75,70,92,0.1); border-radius: 0.5rem; }
        .card-header {
            background: #fff;
            border-bottom: 1px solid rgba(75,70,92,0.08);
            padding: 0.9rem 1.25rem;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        .card-header .card-title-icon {
            width: 30px; height: 30px;
            background: rgba(180,119,30,0.1);
            border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .card-header .card-title-icon i { color: #B4771E; font-size: 1rem; }
        .tfoot-label { font-size: 0.82rem; font-weight: 600; color: #5d596c; }
        .tfoot-amount { font-size: 0.82rem; font-weight: 600; }
        .product-main-image { width: 100%; height: 100%; border-radius: 0.375rem; }
        .product-thumb-image { width: 60px; height: 60px; object-fit: cover; border-radius: 0.375rem; }
        .product-thumb-slide { width: auto !important; opacity: 0.6; transition: opacity 0.2s; }
        .product-thumb-slide.swiper-slide-thumb-active { opacity: 1; border: 2px solid #B4771E; border-radius: 0.5rem; }
        .product-no-image { width: 100%; height: 260px; border-radius: 0.375rem; }
        .stock-badge { font-size: 0.82rem; padding: 0.3rem 0.55rem; }
        .price-chip {
            display: flex; flex-direction: column; align-items: center;
            background: #f8f7fa; border-radius: 0.5rem;
            padding: 0.6rem 0.8rem; flex: 1; min-width: 0;
        }
        .price-chip .p-lbl { font-size: 0.68rem; color: #8592a3; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        .price-chip .p-val { font-size: 0.95rem; font-weight: 700; white-space: nowrap; }

        /* Tabbed card — Product Details */
        .card-tabs .card-header {
            padding: 0 !important;
            border-bottom: none !important;
        }
        .card-tabs .card-header .card-title-row {
            margin-bottom: 0;
            padding: 0.9rem 1.25rem 0.75rem;
        }
        .card-tabs .nav-tabs {
            border-bottom: none;
            margin: 0;
            padding-left: 1.25rem;
            padding-right: 1.25rem;
            flex-wrap: nowrap;
        }
        .card-tabs .nav-tabs .nav-link {
            font-size: 0.8rem;
            font-weight: 500;
            color: #8592a3;
            border: none;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            padding: 0.5rem 0.75rem;
            transition: color 0.15s, border-color 0.15s;
            white-space: nowrap;
        }
        .card-tabs .nav-tabs .nav-link:hover {
            color: #B4771E;
            border-bottom-color: rgba(180,119,30,0.4);
        }
        .card-tabs .nav-tabs .nav-link.active {
            color: #B4771E;
            border-bottom-color: #B4771E;
            background: transparent;
            font-weight: 600;
        }
        .card-tabs .tab-divider {
            height: 1px;
            background: rgba(75,70,92,0.08);
            margin: 0;
        }
        .card-tabs .card-body { 
            padding: 1rem 1.25rem !important;
        }
    </style>
@endsection

@section('content')
    @php
        $statusColors = [1 => 'bg-label-success', 2 => 'bg-label-danger'];
        $statusLabels = [1 => 'Active', 2 => 'Inactive'];
        $profit  = $product->sale_price - $product->purchase_price;
        $margin  = $product->sale_price > 0 ? round(($profit / $product->sale_price) * 100, 1) : 0;
    @endphp

    {{-- ── Page header ─────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">{{ $product->name }}</h4>
            <small class="text-muted">
                <code>{{ $product->barcode }}</code> &middot; {{ format_date($product->created_at) }}
                &middot;
                <span class="badge {{ $statusColors[$product->status] ?? 'bg-label-secondary' }}">
                    {{ $statusLabels[$product->status] ?? 'Unknown' }}
                </span>
                @if($product->is_variable)
                    <span class="badge bg-label-info ms-1">Variable</span>
                @endif
                @if($product->pair_product)
                    <span class="badge bg-label-warning ms-1">Pair Product</span>
                @endif
            </small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            @can('edit products')
                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary">
                    <i class="ti ti-pencil me-1"></i> Edit
                </a>
            @endcan
            <a href="{{ route('admin.products.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- ══ ROW 1: Image | Info | Pricing ══ --}}
    <div class="row g-4 mb-4">

        {{-- ── Images ── --}}
        <div class="col-lg-3 col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-photo"></i></span>
                    <h6 class="mb-0 fw-semibold">Images</h6>
                </div>
                <div class="card-body p-3">
                    @if($product->images->count())
                        <div class="swiper product-main-swiper rounded mb-2">
                            <div class="swiper-wrapper">
                                @foreach($product->images->sortByDesc('is_primary') as $image)
                                    <div class="swiper-slide position-relative">
                                        <img src="{{ $image->image_url }}" class="product-main-image rounded" />
                                        @if($image->is_primary)
                                            <span class="badge bg-primary position-absolute top-0 start-0 m-2" style="font-size:0.65rem;">Primary</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                        @if($product->images->count() > 1)
                            <div class="swiper product-thumb-swiper">
                                <div class="swiper-wrapper">
                                    @foreach($product->images->sortByDesc('is_primary') as $image)
                                        <div class="swiper-slide product-thumb-slide">
                                            <img src="{{ $image->image_url }}" class="product-thumb-image rounded border cursor-pointer" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="product-no-image bg-label-secondary rounded d-flex align-items-center justify-content-center">
                            <i class="ti ti-photo text-muted" style="font-size:3rem;"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Product Info ── --}}
        <div class="col-lg-5 col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-info-circle"></i></span>
                    <h6 class="mb-0 fw-semibold">Product Info</h6>
                </div>
                <div class="card-body py-1 px-3">
                    <div class="info-row">
                        <span class="info-label">Barcode</span>
                        <div class="info-value d-flex gap-2 align-items-center">
                            <code>{{ $product->barcode ?? '-' }}</code>
                             @if($product->barcode)
                                <button onclick="viewBarcode('{{ $product->barcode }}', {{ $product->id }}, '{{ addslashes(!empty($product->subCategory->name) ? $product->subCategory->name : ($product->category->name ?? '')) }}', '{{ addslashes($product->variants->map(fn($v) => $v->attributeValue->value ?? '')->filter()->unique()->implode(', ')) }}', '{{ addslashes(format_price($product->sale_price)) }}')" class="btn btn-sm btn-icon btn-label-secondary" title="Print Barcode">
                                    <i class="ti ti-printer"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Category</span>
                        <span class="info-value">
                            <span class="badge bg-label-primary">{{ $product->category->name ?? '-' }}</span>
                            @if($product->subCategory)
                                <span class="badge bg-label-info ms-1">{{ $product->subCategory->name }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Collection</span>
                        <span class="info-value d-flex flex-wrap gap-1 justify-content-end">
                            @if($product->collections->count())
                                @foreach($product->collections as $col)
                                    <span class="badge bg-label-warning">{{ $col->display_name }}</span>
                                @endforeach
                            @elseif($product->collection)
                                <span class="badge bg-label-warning">{{ $product->collection->display_name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Product Code</span>
                        <span class="info-value fw-semibold">{{ number_format($product->product_code ?? 0, 2) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Slug</span>
                        <code class="info-value" style="max-width:65%;word-break:break-all;font-size:0.75rem;">{{ $product->slug }}</code>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created By</span>
                        <span class="info-value">{{ $product->createdBy->name ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created Date</span>
                        <span class="info-value">{{ format_date($product->created_at) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Pricing ── --}}
        <div class="col-lg-4 col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-currency-rupee"></i></span>
                    <h6 class="mb-0 fw-semibold">Pricing</h6>
                </div>
                <div class="card-body p-3 d-flex flex-column gap-3">
                    @php
                        $effectiveCustomSizes = $product->custom_sizes;
                        if (empty($effectiveCustomSizes) && $product->variants->count()) {
                            foreach ($product->variants as $v) {
                                if (!empty($v->custom_sizes)) {
                                    $effectiveCustomSizes = $v->custom_sizes;
                                    break;
                                }
                            }
                        }
                    @endphp

                    @if($product->pair_product && !empty($effectiveCustomSizes))
                        @php
                            $maxSize = (float) (collect($effectiveCustomSizes)->pluck('size')->max() ?: 1);
                            $maxSizeLabel = rtrim(rtrim(number_format($maxSize, 2), '0'), '.');
                        @endphp
                        {{-- Purchase Price (configured for the largest pack size) --}}
                        <div class="info-row">
                            <span class="info-label">Purchase Price <small class="text-muted">({{ $maxSizeLabel }} pcs)</small></span>
                            <span class="info-value text-info fw-bold">{{ format_price($product->purchase_price) }}</span>
                        </div>

                        {{-- Custom size prices & per-size profit --}}
                        @foreach(collect($effectiveCustomSizes)->sortBy('size') as $sizeRow)
                            @php
                                $sSale     = (float) ($sizeRow['sale_price'] ?? 0);
                                $sMrp      = (float) ($sizeRow['mrp'] ?? 0);
                                $sSize     = (float) ($sizeRow['size'] ?? 0);
                                $sPurchase = $maxSize > 0 ? ((float) $product->purchase_price * ($sSize / $maxSize)) : (float) $product->purchase_price;
                                $sProfit   = $sSale - $sPurchase;
                                $sMargin   = $sSale > 0 ? round(($sProfit / $sSale) * 100, 1) : 0;
                            @endphp
                            <div>
                                <p class="card-section-title mb-2">{{ rtrim(rtrim(number_format((float) ($sizeRow['size'] ?? 0), 2), '0'), '.') }} pcs</p>
                                <div class="d-flex gap-2">
                                    <div class="price-chip">
                                        <span class="p-lbl">Sale Price</span>
                                        <span class="p-val text-success">{{ format_price($sSale) }}</span>
                                    </div>
                                    <div class="price-chip">
                                        <span class="p-lbl">MRP</span>
                                        <span class="p-val text-danger">{{ format_price($sMrp) }}</span>
                                    </div>
                                </div>
                                <div class="info-row" style="padding-top:6px;">
                                    <span class="info-label">Profit</span>
                                    <span class="info-value fw-bold {{ $sProfit > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ format_price($sProfit) }}
                                        <small class="text-muted">({{ $sMargin }}%)</small>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @elseif($product->is_variable && $product->variants->count())
                        {{-- Main product pricing (base prices) --}}
                        <div class="info-row">
                            <span class="info-label">Purchase Price</span>
                            <span class="info-value text-info fw-bold">{{ format_price($product->purchase_price) }}</span>
                        </div>

                        <div class="d-flex gap-2">
                            <div class="price-chip">
                                <span class="p-lbl">Sale Price</span>
                                <span class="p-val text-success">{{ format_price($product->display_sale_price) }}</span>
                            </div>
                            <div class="price-chip">
                                <span class="p-lbl">MRP</span>
                                <span class="p-val text-danger">{{ format_price($product->display_mrp) }}</span>
                            </div>
                        </div>

                        <div class="info-row" style="border-top:1px solid rgba(0,0,0,0.05); padding-top:8px;">
                            <span class="info-label">Profit</span>
                            <span class="info-value fw-bold {{ $profit > 0 ? 'text-success' : 'text-danger' }}">
                                {{ format_price($profit) }}
                                <small class="text-muted">({{ $margin }}%)</small>
                            </span>
                        </div>
                    @else
                        {{-- Purchase Price --}}
                        <div class="info-row">
                            <span class="info-label">Purchase Price</span>
                            <span class="info-value text-info fw-bold">{{ format_price($product->purchase_price) }}</span>
                        </div>

                        <div class="d-flex gap-2">
                            <div class="price-chip">
                                <span class="p-lbl">Sale Price</span>
                                <span class="p-val text-success">{{ format_price($product->display_sale_price) }}</span>
                            </div>
                            <div class="price-chip">
                                <span class="p-lbl">MRP</span>
                                <span class="p-val text-danger">{{ format_price($product->display_mrp) }}</span>
                            </div>
                        </div>

                        {{-- Profit --}}
                        <div class="info-row" style="border-top:1px solid rgba(0,0,0,0.05); padding-top:8px;">
                            <span class="info-label">Profit</span>
                            <span class="info-value fw-bold {{ $profit > 0 ? 'text-success' : 'text-danger' }}">
                                {{ format_price($profit) }}
                                <small class="text-muted">({{ $margin }}%)</small>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- end row 1 --}}

    {{-- ══ ROW 2: Stock Details (full width) ══ --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-package"></i></span>
                    <h6 class="mb-0 fw-semibold">Stock Details</h6>
                </div>
                <div class="card-body p-0">
                    @if($product->is_variable)
                        {{-- ── VARIABLE PRODUCT STOCK ── --}}
                        @php
                            $variantStock = $product->getVariantStock(null);
                            $invByLocation = $product->inventories->keyBy('location_id');
                        @endphp
                        @if(count($variantStock))
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Location</th>
                                            @foreach($product->variants as $v)
                                                <th class="text-center">
                                                    <small>{{ $v->attributeValue->attribute->name ?? '' }}: {{ $v->attributeValue->value ?? '' }}</small>
                                                </th>
                                            @endforeach
                                            <th class="text-center">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $grandTotalVariants = [];
                                            foreach($product->variants as $v) { $grandTotalVariants[$v->id] = 0; }
                                            $grandTotalAll = 0;
                                        @endphp
                                        @foreach($variantStock as $locId => $data)
                                            @php
                                                $variantSum = 0;
                                                foreach($data['variants'] as $vId => $qty) { $variantSum += $qty; }
                                                $invRecord  = $invByLocation[$locId] ?? null;
                                                $locTotal   = $variantSum > 0 ? $variantSum : ($invRecord ? $invRecord->quantity : $data['parent']);
                                                $grandTotalAll += $locTotal;
                                                $useParentFallback = ($variantSum === 0);
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold text-heading">{{ $data['location_name'] }}</td>
                                                @foreach($product->variants as $v)
                                                    @php
                                                        $vQty = $data['variants'][$v->id] ?? 0;
                                                        $grandTotalVariants[$v->id] += $useParentFallback ? 0 : $vQty;
                                                    @endphp
                                                    <td class="text-center">
                                                        @if($useParentFallback)
                                                            <span class="badge bg-label-secondary stock-badge" title="Stock not tracked per variant">—</span>
                                                        @else
                                                            {!! $product->renderStockBadge($vQty) !!}
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="text-center">
                                                    {!! $product->renderStockBadge($locTotal) !!}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td>Grand Total</td>
                                            @foreach($product->variants as $v)
                                                <td class="text-center">
                                                    <span class="badge bg-success text-white stock-badge">{!! $product->pair_product ? $product->formatStockDisplay($grandTotalVariants[$v->id] ?? 0) : ($grandTotalVariants[$v->id] ?? 0) !!}</span>
                                                </td>
                                            @endforeach
                                            <td class="text-center">
                                                <span class="badge bg-primary text-white stock-badge fw-bold">{!! $product->pair_product ? $product->formatStockDisplay($grandTotalAll) : $grandTotalAll !!}</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="ti ti-package-off d-block mb-2" style="font-size:2rem;"></i>
                                No stock available for this product.
                            </div>
                        @endif
                    @else
                        {{-- ── NORMAL PRODUCT STOCK ── --}}
                        @if($product->inventories->count())
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Location</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->inventories as $idx => $inventory)
                                            <tr>
                                                <td class="text-muted small">{{ $idx + 1 }}</td>
                                                <td class="fw-semibold">{{ $inventory->location->name ?? '-' }}</td>
                                                <td class="text-end">
                                                    <span class="fw-bold {{ $inventory->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                                        @if($product->pair_product)
                                                            @php
                                                                $pSize = (float) (collect($product->custom_sizes ?? [])->pluck('size')->max() ?: 2);
                                                                $qtyPieces = (int) $inventory->quantity;
                                                                $pCount = $pSize > 0 ? (int) floor($qtyPieces / $pSize) : 0;
                                                                $remPcs = $pSize > 0 ? (int) ($qtyPieces % $pSize) : 0;
                                                                $pParts = [];
                                                                if ($pCount > 0) $pParts[] = number_format($pCount) . ' Pair' . ($pCount > 1 ? 's' : '');
                                                                if ($remPcs > 0) $pParts[] = $remPcs . ' Pcs';
                                                                $pText = count($pParts) > 0 ? implode('<br>', $pParts) : '0';
                                                            @endphp
                                                            {!! $pText !!} <span class="text-muted fs-7">({{ number_format($qtyPieces) }} Pcs)</span>
                                                        @else
                                                            {{ number_format($inventory->quantity) }}
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if($inventory->quantity > 0)
                                                        <span class="badge bg-label-success stock-badge">In Stock</span>
                                                    @else
                                                        <span class="badge bg-label-danger stock-badge">Out of Stock</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="2" class="text-end tfoot-label">Total Stock</td>
                                            <td class="text-end fw-bold text-primary" style="font-size:1rem;">
                                                @if($product->pair_product)
                                                    @php
                                                        $pSize = (float) (collect($product->custom_sizes ?? [])->pluck('size')->max() ?: 2);
                                                        $pTotalPieces = (int) $product->inventories->sum('quantity');
                                                        $pCount = $pSize > 0 ? (int) floor($pTotalPieces / $pSize) : 0;
                                                        $remPcs = $pSize > 0 ? (int) ($pTotalPieces % $pSize) : 0;
                                                        $pParts = [];
                                                        if ($pCount > 0) $pParts[] = number_format($pCount) . ' Pair' . ($pCount > 1 ? 's' : '');
                                                        if ($remPcs > 0) $pParts[] = $remPcs . ' Pcs';
                                                        $pText = count($pParts) > 0 ? implode('<br>', $pParts) : '0';
                                                    @endphp
                                                    {!! $pText !!} <span class="text-muted fs-7">({{ number_format($pTotalPieces) }} Pieces)</span>
                                                @else
                                                    {{ number_format($product->inventories->sum('quantity')) }}
                                                @endif
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="ti ti-package-off d-block mb-2" style="font-size:2rem;"></i>
                                No stock available for this product.
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card card-tabs">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2 card-title-row">
                        <span class="card-title-icon"><i class="ti ti-history"></i></span>
                        <h6 class="mb-0 fw-semibold">Product History</h6>
                    </div>
                    <ul class="nav nav-tabs" style="margin-right: 0px; margin-left: 0px;" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#purchase-history-tab" role="tab">
                                Purchase History <span class="badge bg-label-secondary ms-1">{{ $purchaseCount }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#transfer-history-tab" role="tab">
                                Purchase Bill <span class="badge bg-label-secondary ms-1">{{ $transferCount }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#sale-history-tab" role="tab">
                                Sales History <span class="badge bg-label-secondary ms-1">{{ $saleCount }}</span>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tab-divider"></div>
                <div class="card-body p-0">
                    <div class="tab-content">
                        {{-- ── Purchase History (from suppliers) ── --}}
                        <div class="tab-pane fade show active" id="purchase-history-tab" role="tabpanel">
                            <div class="card-datatable table-responsive">
                                <table class="table border-top" id="purchaseHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice No</th>
                                            <th>Supplier</th>
                                            <th>Location</th>
                                            <th>Variant</th>
                                            @if($product->pair_product)<th>Pair Breakdown</th>@endif
                                            <th class="text-end">Qty (Pcs)</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Status</th>
                                            <th class="d-none">Date Group</th>
                                            <th class="d-none">Date Sort</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="{{ $product->pair_product ? 6 : 5 }}" class="text-end tfoot-label">Total Purchased</th>
                                            <th class="text-end text-primary" id="purchaseTotalQty">0</th>
                                            <th class="text-end text-primary" id="purchaseTotalAmount">{{ format_price(0) }}</th>
                                            <th></th>
                                            <th class="d-none"></th>
                                            <th class="d-none"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- ── Purchase Bill (branch transfer) History ── --}}
                        <div class="tab-pane fade" id="transfer-history-tab" role="tabpanel">
                            <div class="card-datatable table-responsive">
                                <table class="table border-top" id="transferHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Transfer No</th>
                                            <th>From Branch</th>
                                            <th>To Branch</th>
                                            <th>Variant</th>
                                            @if($product->pair_product)<th>Pair Breakdown</th>@endif
                                            <th class="text-end">Qty (Pcs)</th>
                                            <th class="text-center">Status</th>
                                            <th class="d-none">Date Group</th>
                                            <th class="d-none">Date Sort</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="{{ $product->pair_product ? 6 : 5 }}" class="text-end tfoot-label">Total Transferred</th>
                                            <th class="text-end text-primary" id="transferTotalQty">0</th>
                                            <th></th>
                                            <th class="d-none"></th>
                                            <th class="d-none"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- ── Sales History (to customers) ── --}}
                        <div class="tab-pane fade" id="sale-history-tab" role="tabpanel">
                            <div class="card-datatable table-responsive">
                                <table class="table border-top" id="saleHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Order No</th>
                                            <th>Customer</th>
                                            <th>Location</th>
                                            <th>Variant</th>
                                            @if($product->pair_product)<th>Pair Breakdown</th>@endif
                                            <th class="text-end">Qty (Pcs)</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Status</th>
                                            <th class="d-none">Date Group</th>
                                            <th class="d-none">Date Sort</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="{{ $product->pair_product ? 6 : 5 }}" class="text-end tfoot-label">Total Sold</th>
                                            <th class="text-end text-primary" id="saleTotalQty">0</th>
                                            <th class="text-end text-primary" id="saleTotalAmount">{{ format_price(0) }}</th>
                                            <th></th>
                                            <th class="d-none"></th>
                                            <th class="d-none"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>{{-- end row 2b --}}

    {{-- ══ ROW 3: Variants Table + Description side by side ══ --}}
    @if($product->is_variable && $product->variants->count())
        @php
            if (!isset($variantStock)) {
                $variantStock = $product->getVariantStock(null);
            }
            if (!isset($invByLocation)) {
                $invByLocation = $product->inventories->keyBy('location_id');
            }

            $allVariantStockSum = 0;
            $vStockMap = [];
            foreach ($product->variants as $v) {
                $vSum = 0;
                foreach ($variantStock as $locId => $data) {
                    $vSum += ($data['variants'][$v->id] ?? 0);
                }
                $vStockMap[$v->id] = $vSum;
                $allVariantStockSum += $vSum;
            }
            $invTotal = $invByLocation->sum('quantity');
            $isParentOnlyStock = ($allVariantStockSum === 0 && $invTotal > 0);
        @endphp
        <div class="row g-4 mb-4">
            <div class="{{ ($product->description || $product->product_highlights || $product->additional_information) ? 'col-lg-8' : 'col-12' }}">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="card-title-icon"><i class="ti ti-versions"></i></span>
                        <h6 class="mb-0 fw-semibold">Product Variants</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:4%">#</th>
                                    <th>Attribute</th>
                                    <th class="text-end">Purchase Price</th>
                                    <th class="text-end">Sale Price</th>
                                    <th class="text-end">MRP</th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-end">Total Stock</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->variants as $idx => $variant)
                                    @php
                                        $vQty = $vStockMap[$variant->id] ?? 0;
                                    @endphp
                                    <tr>
                                        <td class="text-muted small">{{ $idx + 1 }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $variant->attributeValue->value ?? '-' }}</span>
                                            <small class="text-muted">({{ $variant->attributeValue->attribute->name ?? '-' }})</small>
                                        </td>
                                        <td class="text-end text-nowrap small">{{ format_price($variant->purchase_price) }}</td>
                                        <td class="text-end text-nowrap small text-success fw-semibold">{{ format_price($variant->sale_price) }}</td>
                                        <td class="text-end text-nowrap small text-danger">{{ format_price($variant->mrp ?? $product->mrp) }}</td>
                                        @php
                                            $vProfit = (float)$variant->sale_price - (float)$variant->purchase_price;
                                            $vMargin = (float)$variant->sale_price > 0 ? round(($vProfit / (float)$variant->sale_price) * 100, 1) : 0;
                                        @endphp
                                        <td class="text-end text-nowrap small {{ $vProfit > 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                            {{ format_price($vProfit) }}
                                            <small class="text-muted">({{ $vMargin }}%)</small>
                                        </td>
                                        <td class="text-end">
                                            @if($isParentOnlyStock)
                                                @if($idx === 0)
                                                    <span class="badge bg-label-warning stock-badge" title="Total stock (not split per variant)">{{ $invTotal }} total</span>
                                                @else
                                                    <span class="badge bg-label-secondary stock-badge">—</span>
                                                @endif
                                            @else
                                                @if($product->pair_product && $vQty > 0)
                                                    <span class="badge bg-label-{{ $vQty > 0 ? 'success' : 'secondary' }} stock-badge">
                                                        {!! $product->formatStockDisplay($vQty) !!}
                                                    </span>
                                                @else
                                                    <span class="badge bg-label-{{ $vQty > 0 ? 'success' : ($vQty < 0 ? 'danger' : 'secondary') }} stock-badge">
                                                        {{ number_format($vQty) }}
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center">{!! status_badge($variant->status) !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($product->description || $product->product_highlights || $product->additional_information)
            <div class="col-lg-4">
                <div class="card h-100 card-tabs">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2 card-title-row">
                            <span class="card-title-icon"><i class="ti ti-align-left"></i></span>
                            <h6 class="mb-0 fw-semibold">Product Details</h6>
                        </div>
                        <ul class="nav nav-tabs" style="margin-right: 0px; margin-left: 0px;" role="tablist">
                            @if($product->description)
                                <li class="nav-item">
                                    <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#desc-tab-var" role="tab">Description</button>
                                </li>
                            @endif
                            @if($product->product_highlights)
                                <li class="nav-item">
                                    <button type="button" class="nav-link {{ !$product->description ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#high-tab-var" role="tab">Highlights</button>
                                </li>
                            @endif
                            @if($product->additional_information)
                                <li class="nav-item">
                                    <button type="button" class="nav-link {{ (!$product->description && !$product->product_highlights) ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#add-tab-var" role="tab">Additional Info</button>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <div class="tab-divider"></div>
                    <div class="card-body" style="overflow-y:auto;max-height:320px;">
                        <div class="tab-content">
                            @if($product->description)
                                <div class="tab-pane fade show active" id="desc-tab-var" role="tabpanel">
                                    <div class="text-muted small">{!! $product->description !!}</div>
                                </div>
                            @endif
                            @if($product->product_highlights)
                                <div class="tab-pane fade {{ !$product->description ? 'show active' : '' }}" id="high-tab-var" role="tabpanel">
                                    <div class="text-muted small">{!! $product->product_highlights !!}</div>
                                </div>
                            @endif
                            @if($product->additional_information)
                                <div class="tab-pane fade {{ (!$product->description && !$product->product_highlights) ? 'show active' : '' }}" id="add-tab-var" role="tabpanel">
                                    <div class="text-muted small">{!! $product->additional_information !!}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    @else
        {{-- Non-variable: show description full width if exists --}}
        @if($product->description || $product->product_highlights || $product->additional_information)
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card card-tabs">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2 card-title-row">
                            <span class="card-title-icon"><i class="ti ti-align-left"></i></span>
                            <h6 class="mb-0 fw-semibold">Product Details</h6>
                        </div>
                        <ul class="nav nav-tabs" style="margin-right: 0px; margin-left: 0px;" role="tablist">
                            @if($product->description)
                                <li class="nav-item">
                                    <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#desc-tab" role="tab">Description</button>
                                </li>
                            @endif
                            @if($product->product_highlights)
                                <li class="nav-item">
                                    <button type="button" class="nav-link {{ !$product->description ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#high-tab" role="tab">Highlights</button>
                                </li>
                            @endif
                            @if($product->additional_information)
                                <li class="nav-item">
                                    <button type="button" class="nav-link {{ (!$product->description && !$product->product_highlights) ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#add-tab" role="tab">Additional Info</button>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <div class="tab-divider"></div>
                    <div class="card-body">
                        <div class="tab-content">
                            @if($product->description)
                                <div class="tab-pane fade show active" id="desc-tab" role="tabpanel">
                                    <div class="text-muted small">{!! $product->description !!}</div>
                                </div>
                            @endif
                            @if($product->product_highlights)
                                <div class="tab-pane fade {{ !$product->description ? 'show active' : '' }}" id="high-tab" role="tabpanel">
                                    <div class="text-muted small">{!! $product->product_highlights !!}</div>
                                </div>
                            @endif
                            @if($product->additional_information)
                                <div class="tab-pane fade {{ (!$product->description && !$product->product_highlights) ? 'show active' : '' }}" id="add-tab" role="tabpanel">
                                    <div class="text-muted small">{!! $product->additional_information !!}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endif

@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/swiper/swiper.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            const rowNumberColumn = {
                data: null, orderable: false, searchable: false, width: '3%',
                render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; }
            };

            function formatMoney(n) {
                return '{{ currency_symbol() }} ' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function sumField(api, field) {
                return api.rows({ search: 'applied' }).data().toArray().reduce(function (sum, row) {
                    return sum + Number(row[field] || 0);
                }, 0);
            }

            @php
                $purchaseDateSortIdx = $product->pair_product ? 10 : 9;
                $transferDateSortIdx = $product->pair_product ? 9 : 8;
                $saleDateSortIdx     = $product->pair_product ? 10 : 9;
                $purchaseVisibleCols = $product->pair_product ? 9 : 8;
                $transferVisibleCols = $product->pair_product ? 8 : 7;
                $saleVisibleCols     = $product->pair_product ? 9 : 8;
            @endphp

            const purchaseColumns = [
                rowNumberColumn,
                { data: 'invoice_no' },
                { data: 'supplier' },
                { data: 'location' },
                { data: 'variant' },
                @if($product->pair_product)
                { data: 'breakdown' },
                @endif
                { data: 'qty', className: 'text-end fw-bold' },
                { data: 'amount', className: 'text-end fw-semibold' },
                { data: 'status', orderable: false, className: 'text-center' },
                { data: 'date_group', visible: false },
                { data: 'date_sort', visible: false },
            ];
            const purchaseTable = $('#purchaseHistoryTable').DataTable({
                responsive: false,
                order: [[{{ $purchaseDateSortIdx }}, 'desc']],
                language: { emptyTable: 'No purchase history found for this product.' },
                ajax: { url: '{{ route('admin.products.purchase-history', $product) }}', dataSrc: 'data', cache: false },
                columns: purchaseColumns,
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="{{ $purchaseVisibleCols }}"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' invoice' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
                footerCallback: function () {
                    const api = this.api();
                    $('#purchaseTotalQty').text(sumField(api, 'qty_raw').toLocaleString('en-IN'));
                    $('#purchaseTotalAmount').text(formatMoney(sumField(api, 'amount_raw')));
                }
            });

            const transferColumns = [
                rowNumberColumn,
                { data: 'transfer_no' },
                { data: 'from_branch' },
                { data: 'to_branch' },
                { data: 'variant' },
                @if($product->pair_product)
                { data: 'breakdown' },
                @endif
                { data: 'qty', className: 'text-end fw-bold' },
                { data: 'status', orderable: false, className: 'text-center' },
                { data: 'date_group', visible: false },
                { data: 'date_sort', visible: false },
            ];
            const transferTable = $('#transferHistoryTable').DataTable({
                responsive: false,
                order: [[{{ $transferDateSortIdx }}, 'desc']],
                language: { emptyTable: 'No purchase bill history found for this product.' },
                ajax: { url: '{{ route('admin.products.transfer-history', $product) }}', dataSrc: 'data', cache: false },
                columns: transferColumns,
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="{{ $transferVisibleCols }}"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' transfer' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
                footerCallback: function () {
                    const api = this.api();
                    $('#transferTotalQty').text(sumField(api, 'qty_raw').toLocaleString('en-IN'));
                }
            });

            const saleColumns = [
                rowNumberColumn,
                { data: 'order_no' },
                { data: 'customer' },
                { data: 'location' },
                { data: 'variant' },
                @if($product->pair_product)
                { data: 'breakdown' },
                @endif
                { data: 'qty', className: 'text-end fw-bold' },
                { data: 'amount', className: 'text-end fw-semibold' },
                { data: 'status', orderable: false, className: 'text-center' },
                { data: 'date_group', visible: false },
                { data: 'date_sort', visible: false },
            ];
            const saleTable = $('#saleHistoryTable').DataTable({
                responsive: false,
                order: [[{{ $saleDateSortIdx }}, 'desc']],
                language: { emptyTable: 'No sales history found for this product.' },
                ajax: { url: '{{ route('admin.products.sale-history', $product) }}', dataSrc: 'data', cache: false },
                columns: saleColumns,
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="{{ $saleVisibleCols }}"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' sale' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
                footerCallback: function () {
                    const api = this.api();
                    $('#saleTotalQty').text(sumField(api, 'qty_raw').toLocaleString('en-IN'));
                    $('#saleTotalAmount').text(formatMoney(sumField(api, 'amount_raw')));
                }
            });

            document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#purchase-history-tab"], [data-bs-toggle="tab"][data-bs-target^="#transfer-history-tab"], [data-bs-toggle="tab"][data-bs-target^="#sale-history-tab"]')
                .forEach(function (el) {
                    el.addEventListener('shown.bs.tab', function (e) {
                        const target = e.target.getAttribute('data-bs-target');
                        if (target === '#transfer-history-tab') transferTable.columns.adjust();
                        if (target === '#sale-history-tab') saleTable.columns.adjust();
                        if (target === '#purchase-history-tab') purchaseTable.columns.adjust();
                    });
                });
        });
    </script>
    <script>
        window.buildBarcodeLabelsHtml = function(items) {
            const esc = function(str) {
                return String(str ?? '').replace(/[&<>"']/g, function(c) {
                    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
                });
            };
            let body = '';
            items.forEach(function(item) {
                const qty = item.qty || 1;
                for (let i = 0; i < qty; i++) {
                    body += '<div class="label">';
                    body +=   '<div class="zone-front">';
                    body +=     '<div class="mrp-line">MRP : ' + esc(item.salePrice) + '</div>';
                    body +=     '<div class="code-line">' + esc(item.barcodeText) + '</div>';
                    body +=   '</div>';
                    body +=   '<div class="zone-back">';
                    body +=     '<div class="variations-line">' + esc(item.variations || '&nbsp;') + '</div>';
                    body +=     '<img class="barcode-img" src="' + item.barcodeUrl + '" />';
                    body +=     '<div class="category-line">' + esc(item.category) + '</div>';
                    body +=   '</div>';
                    body +=   '<div class="zone-tab"></div>';
                    body += '</div>';
                }
            });

            return '<!DOCTYPE html><html><head><title>Print Labels</title><style>' +
                '@page{size:82mm;margin:0 !important;}' +
                '*{box-sizing:border-box;}' +
                'html,body{margin:0 !important;padding:0 !important;width:82mm !important;max-width:82mm !important;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;font-family:Arial,Helvetica,sans-serif !important;overflow:hidden !important;}' +
                '.label{width:82mm !important;height:12mm !important;max-height:12mm !important;overflow:hidden !important;display:flex !important;flex-direction:row !important;align-items:center !important;page-break-inside:avoid !important;break-inside:avoid !important;margin:0 !important;padding:0 !important;font-family:Arial,Helvetica,sans-serif !important;color:#000;}' +
                '.zone-front{width:37mm !important;height:100% !important;display:flex !important;flex-direction:column !important;justify-content:space-between !important;padding:0.8mm 1.5mm !important;overflow:hidden !important;border:0.5px solid #000 !important;border-radius:4px !important;}' +
                '.zone-back{width:29mm !important;height:100% !important;display:flex !important;flex-direction:column !important;justify-content:space-between !important;align-items:center !important;overflow:hidden !important;padding:0.8mm 1px !important;border:0.5px solid #000 !important;border-radius:4px !important;}' +
                '.zone-tab{width:16mm !important;height:100% !important;}' +
                '.mrp-line{font-size:10pt !important;font-weight:700 !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                '.category-line{font-size:7pt !important;font-weight:normal !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                '.variations-line{font-size:7pt !important;font-weight:normal !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                '.code-line{font-size:7.5pt !important;font-weight:normal !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                '.barcode-img{width:22mm !important;height:3.5mm !important;object-fit:fill !important;margin:0 !important;display:block;}' +
                '</style>' +
                '<script>window.onload=function(){setTimeout(function(){window.print();window.onafterprint=function(){window.close();};setTimeout(function(){window.close();},500);},500);};<\/script>' +
                '</head><body>' + body + '</body></html>';
        };

        window.viewBarcode = function(barcodeText, productId, category, variations, salePrice) {
            const barcodeUrl = '{{ route('admin.products.barcode', ':id') }}'.replace(':id', productId);
            const customSizes = @json($product->pair_product ? ($product->custom_sizes ?? []) : []);
            const variantsList = @json($product->type === 'variable' ? $product->variants->filter(fn($v) => $v->attributeValue)->map(fn($v) => ['id' => $v->id, 'value' => $v->attributeValue->value])->values()->toArray() : []);
            
            let customSizeSelectHtml = '';
            if (customSizes && customSizes.length > 0) {
                customSizeSelectHtml += '<div class="form-group mb-3 text-start">';
                customSizeSelectHtml += '  <label for="printCustomSize" class="form-label fw-medium text-secondary small">Select Custom Size</label>';
                customSizeSelectHtml += '  <select id="printCustomSize" class="form-select">';
                customSizes.forEach(function(sz) {
                    const sizeVal = typeof sz === 'object' && sz !== null ? sz.size : sz;
                    const formattedSize = String(sizeVal).includes('pcs') ? sizeVal : sizeVal + ' pcs';
                    customSizeSelectHtml += `<option value="${formattedSize}">${formattedSize}</option>`;
                });
                customSizeSelectHtml += '  </select>';
                customSizeSelectHtml += '</div>';
            }

            let variantSelectHtml = '';
            if (variantsList && variantsList.length > 0) {
                variantSelectHtml += '<div class="form-group mb-3 text-start">';
                variantSelectHtml += '  <label for="printVariantSelect" class="form-label fw-medium text-secondary small">Select Variant</label>';
                variantSelectHtml += '  <select id="printVariantSelect" class="form-select">';
                variantSelectHtml += '  <option value="">-- Select Varient --</option>';
                variantsList.forEach(function(v) {
                    variantSelectHtml += `<option value="${v.id}">${v.value}</option>`;
                });
                variantSelectHtml += '  </select>';
                variantSelectHtml += '</div>';
            }

            const modal = `
                <div class="modal fade" id="barcodeModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-semibold">Product Barcode</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center pt-2">
                                <div id="barcodeLoader" class="py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0">Generating barcode...</p>
                                </div>
                                <div id="barcodeContent" class="d-none">
                                    <div class="bg-light p-3 rounded mb-3 d-inline-block w-100">
                                        <div class="mb-2">
                                            <img id="barcodeImage" src="${barcodeUrl}" alt="Barcode" class="img-fluid" style="max-height: 80px;">
                                        </div>
                                        <p class="fw-bold mb-0 text-dark font-monospace fs-5">${barcodeText}</p>
                                    </div>
                                    
                                    ${customSizeSelectHtml}
                                    ${variantSelectHtml}

                                    <div class="form-group mb-3 text-start">
                                        <label for="printQty" class="form-label fw-medium text-secondary small">Print Quantity</label>
                                        <input type="number" id="printQty" class="form-control" value="1" min="1" max="100">
                                    </div>
                                    <button type="button" class="btn btn-primary w-100" id="printBarcodeBtn">
                                        <i class="ti ti-printer me-1"></i> Print Barcode
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            $('#barcodeModal').remove();
            $('body').append(modal);
            const modalEl = new bootstrap.Modal(document.getElementById('barcodeModal'));
            modalEl.show();

            const $loader  = $('#barcodeLoader');
            const $content = $('#barcodeContent');
            const $img     = $('#barcodeImage');
            const $printBtn = $('#printBarcodeBtn');
            const $printQty = $('#printQty');

            $img.on('load', function() {
                $loader.addClass('d-none');
                $content.removeClass('d-none');
            }).on('error', function() {
                $loader.html('<p class="text-danger mb-0">Failed to load barcode image.</p>');
            });

            $printBtn.on('click', function() {
                const qty = parseInt($printQty.val()) || 1;
                const item = { id: productId, qty: qty };
                if ($('#printCustomSize').length > 0) {
                    item.selected_size = $('#printCustomSize').val();
                }
                if ($('#printVariantSelect').length > 0 && $('#printVariantSelect').val()) {
                    item.selected_variant_id = $('#printVariantSelect').val();
                }
                window.startBarcodePrint([item]);
            });

            document.getElementById('barcodeModal').addEventListener('hidden.bs.modal', function () {
                this.remove();
            });
        };

        function initProductSwiper() {
            @if($product->images->count() > 1)
            const thumbSwiper = new Swiper('.product-thumb-swiper', {
                spaceBetween: 6, slidesPerView: 'auto',
                freeMode: true, watchSlidesProgress: true,
                observer: true, observeParents: true,
            });
            new Swiper('.product-main-swiper', {
                spaceBetween: 10,
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                thumbs: { swiper: thumbSwiper },
                observer: true, observeParents: true,
            });
            @else
            new Swiper('.product-main-swiper', {
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                observer: true, observeParents: true,
            });
            @endif
        }

        $(document).ready(function () {
            var $mainImages = $('.product-main-swiper img');
            var loadedCount = 0;
            var totalImages = $mainImages.length;
            if (totalImages === 0) {
                initProductSwiper();
            } else {
                $mainImages.each(function () {
                    if (this.complete) {
                        loadedCount++;
                    } else {
                        $(this).on('load', function () {
                            loadedCount++;
                            if (loadedCount >= totalImages) initProductSwiper();
                        });
                    }
                });
                if (loadedCount >= totalImages) initProductSwiper();
            }
        });
    </script>
@endsection
