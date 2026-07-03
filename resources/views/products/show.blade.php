@extends('layouts.app')

@section('title', $product->name)

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/swiper/swiper.css') }}" />
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
        .product-main-image { width: 100%; height: 260px; object-fit: cover; border-radius: 0.375rem; }
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
                <code>{{ $product->sku }}</code> &middot; {{ format_date($product->created_at) }}
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
                        <span class="info-label">SKU</span>
                        <code class="info-value">{{ $product->sku }}</code>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Barcode</span>
                        <div class="info-value d-flex gap-2 align-items-center">
                            <code>{{ $product->barcode ?? '-' }}</code>
                            @if($product->barcode)
                                <button onclick="viewBarcode('{{ $product->barcode }}', {{ $product->id }})" class="btn btn-sm btn-icon btn-label-secondary" title="Print Barcode">
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

                    {{-- Purchase Price --}}
                    <div class="info-row">
                        <span class="info-label">Purchase Price</span>
                        <span class="info-value text-info fw-bold">{{ format_price($product->purchase_price) }}</span>
                    </div>

                    @if($product->pair_product)
                        {{-- Piece prices --}}
                        <div>
                            <p class="card-section-title mb-2">Piece</p>
                            <div class="d-flex gap-2">
                                <div class="price-chip">
                                    <span class="p-lbl">Sale Price</span>
                                    <span class="p-val text-success">{{ format_price($product->sale_price) }}</span>
                                </div>
                                <div class="price-chip">
                                    <span class="p-lbl">MRP</span>
                                    <span class="p-val text-danger">{{ format_price($product->mrp) }}</span>
                                </div>
                            </div>
                        </div>
                        {{-- Pair prices --}}
                        <div>
                            <p class="card-section-title mb-2">Pair</p>
                            <div class="d-flex gap-2">
                                <div class="price-chip">
                                    <span class="p-lbl">Sale Price</span>
                                    <span class="p-val text-success">{{ format_price($product->pair_sale_price) }}</span>
                                </div>
                                <div class="price-chip">
                                    <span class="p-lbl">MRP</span>
                                    <span class="p-val text-danger">{{ format_price($product->pair_mrp) }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="d-flex gap-2">
                            <div class="price-chip">
                                <span class="p-lbl">Sale Price</span>
                                <span class="p-val text-success">{{ format_price($product->sale_price) }}</span>
                            </div>
                            <div class="price-chip">
                                <span class="p-lbl">MRP</span>
                                <span class="p-val text-danger">{{ format_price($product->mrp) }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- Profit --}}
                    <div class="info-row" style="border-top:1px solid rgba(0,0,0,0.05); padding-top:8px;">
                        <span class="info-label">Profit</span>
                        <span class="info-value fw-bold {{ $profit > 0 ? 'text-success' : 'text-danger' }}">
                            {{ format_price($profit) }}
                            <small class="text-muted">({{ $margin }}%)</small>
                        </span>
                    </div>
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
                            $variantStock = $product->getVariantStock($locationId ?? null);
                            if ($locationId && !is_null($variantStock) && isset($variantStock['location_name'])) {
                                $variantStock = [$locationId => $variantStock];
                            } elseif ($locationId && is_null($variantStock)) {
                                $variantStock = [];
                            }
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
                                                $locTotal   = $invRecord ? $invRecord->quantity : ($variantSum > 0 ? $variantSum : $data['parent']);
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
                                                            <span class="badge bg-label-{{ $vQty > 0 ? 'success' : ($vQty < 0 ? 'danger' : 'secondary') }} stock-badge">{{ $vQty }}</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="text-center">
                                                    <span class="badge bg-label-primary stock-badge fw-bold">{{ $locTotal }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if(!$isRestricted)
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td>Grand Total</td>
                                            @foreach($product->variants as $v)
                                                <td class="text-center">
                                                    <span class="badge bg-success text-white stock-badge">{{ $grandTotalVariants[$v->id] ?? 0 }}</span>
                                                </td>
                                            @endforeach
                                            <td class="text-center">
                                                <span class="badge bg-primary text-white stock-badge fw-bold">{{ $grandTotalAll }}</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                    @endif
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
                                                        {{ number_format($inventory->quantity) }}
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
                                                {{ number_format($product->inventories->sum('quantity')) }}
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
    </div>{{-- end row 2 --}}

    {{-- ══ ROW 3: Variants Table + Description side by side ══ --}}
    @if($product->is_variable && $product->variants->count())
        @php
            if (!isset($variantStock)) {
                $variantStock = $product->getVariantStock($locationId ?? null);
                if (($locationId ?? null) && !is_null($variantStock) && isset($variantStock['location_name'])) {
                    $variantStock = [($locationId) => $variantStock];
                } elseif (($locationId ?? null) && is_null($variantStock)) {
                    $variantStock = [];
                }
            }
            if (!isset($invByLocation)) {
                $invByLocation = $product->inventories->keyBy('location_id');
            }
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
                                    <th class="text-end">Total Stock</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->variants as $idx => $variant)
                                    @php
                                        $totalVQty   = 0;
                                        foreach ($variantStock as $locId => $data) {
                                            $totalVQty += ($data['variants'][$variant->id] ?? 0);
                                        }
                                        $invTotal     = $invByLocation->sum('quantity');
                                        $displayQty   = ($totalVQty > 0) ? $totalVQty : $invTotal;
                                        $isParentOnly = ($totalVQty === 0);
                                    @endphp
                                    <tr>
                                        <td class="text-muted small">{{ $idx + 1 }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $variant->attributeValue->value ?? '-' }}</span>
                                            <small class="text-muted">({{ $variant->attributeValue->attribute->name ?? '-' }})</small>
                                        </td>
                                        <td class="text-end text-nowrap small">{{ format_price($variant->purchase_price) }}</td>
                                        <td class="text-end text-nowrap small">{{ format_price($variant->sale_price) }}</td>
                                        <td class="text-end">
                                            @if($isParentOnly)
                                                @if($idx === 0)
                                                    <span class="badge bg-label-warning stock-badge" title="Total stock (not split per variant)">{{ $displayQty }} total</span>
                                                @else
                                                    <span class="badge bg-label-secondary stock-badge" title="Stock not tracked per variant">—</span>
                                                @endif
                                            @else
                                                <span class="badge bg-label-{{ $displayQty > 0 ? 'success' : ($displayQty < 0 ? 'danger' : 'secondary') }} stock-badge">{{ $displayQty }}</span>
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
    <script>
        window.viewBarcode = function(barcode, productId) {
            const barcodeUrl = '{{ route('admin.products.barcode', ':id') }}'.replace(':id', productId);
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
                                        <p class="fw-bold mb-0 text-dark font-monospace fs-5">${barcode}</p>
                                    </div>
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
                const printWindow = window.open('', '_blank');
                let html = '<!DOCTYPE html><html><head><title>Print Barcodes</title>';
                html += '<style>';
                html += 'body{font-family:Arial,sans-serif;margin:0;padding:10px;display:flex;flex-wrap:wrap;gap:15px;justify-content:center;}';
                html += '.barcode-label{border:1px dashed #999;padding:15px;text-align:center;width:220px;page-break-inside:avoid;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:4px;}';
                html += '.barcode-value{font-size:14px;margin-bottom:8px;font-family:monospace;font-weight:bold;}';
                html += '.barcode-image{max-width:100%;height:auto;}';
                html += '@media print{body{padding:0;}.barcode-label{border:1px solid #000;page-break-inside:avoid;}}';
                html += '</style></head><body>';
                for (let i = 0; i < qty; i++) {
                    html += '<div class="barcode-label">';
                    html += '<div class="barcode-value">' + barcode + '</div>';
                    html += '<img src="' + barcodeUrl + '" class="barcode-image" />';
                    html += '</div>';
                }
                html += '<script>window.onload=function(){setTimeout(function(){window.print();window.onafterprint=function(){window.close();};setTimeout(function(){window.close();},500);},500);};<\/script>';
                html += '</body></html>';
                printWindow.document.write(html);
                printWindow.document.close();
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
