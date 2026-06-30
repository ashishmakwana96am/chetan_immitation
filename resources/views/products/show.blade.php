@extends('layouts.app')

@section('title', $product->name)

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/swiper/swiper.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">{{ $product->name }}</h4>
        <div class="d-flex gap-2">
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

    <div class="row g-4">

        <!-- Left: Image Slider -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body p-3">
                    @if($product->images->count())

                        <!-- Main Swiper -->
                        <div class="swiper product-main-swiper rounded mb-2">
                            <div class="swiper-wrapper">
                                @foreach($product->images->sortByDesc('is_primary') as $image)
                                    <div class="swiper-slide position-relative">
                                        <img src="{{ $image->image_url }}"
                                            class="product-main-image rounded" />
                                        @if($image->is_primary)
                                            <span class="badge bg-primary position-absolute top-0 start-0 m-2">Primary</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>

                        <!-- Thumbnail Swiper -->
                        @if($product->images->count() > 1)
                            <div class="swiper product-thumb-swiper">
                                <div class="swiper-wrapper">
                                    @foreach($product->images->sortByDesc('is_primary') as $image)
                                        <div class="swiper-slide product-thumb-slide">
                                            <img src="{{ $image->image_url }}"
                                                class="product-thumb-image rounded border cursor-pointer" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    @else
                        <div class="product-no-image bg-label-secondary rounded d-flex align-items-center justify-content-center">
                            <i class="ti ti-photo text-muted product-no-image-icon"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Product Details</h5>
                    {!! status_badge($product->status) !!}
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Name</p>
                            <p class="fw-semibold mb-0">{{ $product->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">SKU</p>
                            <p class="mb-0"><code>{{ $product->sku }}</code></p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Barcode</p>
                            <div class="d-flex align-items-center gap-2">
                                <p class="mb-0"><code>{{ $product->barcode ?? '-' }}</code></p>
                                @if($product->barcode)
                                <button onclick="viewBarcode('{{ $product->barcode }}', {{ $product->id }})" class="btn btn-sm btn-icon btn-label-secondary" title="Print Barcode">
                                    <i class="ti ti-printer"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Category</p>
                            <p class="mb-0">
                                <span class="badge bg-label-primary">{{ $product->category->name ?? '-' }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Type</p>
                            <p class="mb-0">
                                @if($product->is_variable)
                                    <span class="badge bg-label-info">Variable</span>
                                @else
                                    <span class="badge bg-label-secondary">Regular</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Slug</p>
                            <p class="mb-0"><code>{{ $product->slug }}</code></p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Purchase Price</p>
                            <p class="fw-semibold mb-0 text-info">{{ format_price($product->purchase_price) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Sale Price</p>
                            <p class="fw-semibold mb-0 text-success">{{ format_price($product->sale_price) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">MRP</p>
                            <p class="fw-semibold mb-0 text-danger">{{ format_price($product->mrp) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Created Date</p>
                            <p class="mb-0">{{ format_date($product->created_at) }}</p>
                        </div>
                        @if($product->description)
                            <div class="col-12">
                                <p class="text-muted small mb-1">Description</p>
                                <div class="mb-0">{!! $product->description !!}</div>
                            </div>
                        @endif
                        @if($product->additional_information)
                            <div class="col-12">
                                <p class="text-muted small mb-1">Additional Information</p>
                                <div class="mb-0">{!! $product->additional_information !!}</div>
                            </div>
                        @endif
                        @if($product->product_highlights)
                            <div class="col-12">
                                <p class="text-muted small mb-1">Product Highlights</p>
                                <div class="mb-0">{!! $product->product_highlights !!}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Stock Details -->
            <div class="card">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">Stock Details</h5>
                </div>
                <div class="card-body pt-4">
                    @if($product->is_variable)
                        @php
                            $variantStock = $product->getVariantStock();
                        @endphp
                        @if(count($variantStock))
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Location</th>
                                            @foreach($product->variants as $v)
                                                <th class="text-center">
                                                    {{ $v->attributeValue->attribute->name ?? '' }}: {{ $v->attributeValue->value ?? '' }}
                                                </th>
                                            @endforeach
                                            <th class="text-center">Total Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $grandTotalVariants = [];
                                            foreach($product->variants as $v) {
                                                $grandTotalVariants[$v->id] = 0;
                                            }
                                            $grandTotalAll = 0;
                                        @endphp
                                        @foreach($variantStock as $locId => $data)
                                            @php
                                                $locTotal = 0;
                                                foreach($data['variants'] as $vId => $qty) {
                                                    $locTotal += $qty;
                                                }
                                                $grandTotalAll += $locTotal;
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold text-heading">{{ $data['location_name'] }}</td>
                                                @foreach($product->variants as $v)
                                                    @php
                                                        $vQty = $data['variants'][$v->id] ?? 0;
                                                        $grandTotalVariants[$v->id] += $vQty;
                                                    @endphp
                                                    <td class="text-center">
                                                        <span class="badge bg-label-{{ $vQty > 0 ? 'success' : ($vQty < 0 ? 'danger' : 'secondary') }} fs-6">
                                                            {{ $vQty }}
                                                        </span>
                                                    </td>
                                                @endforeach
                                                <td class="text-center">
                                                    <span class="badge bg-label-primary fs-6 fw-bold">{{ $locTotal }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td>Grand Total</td>
                                            @foreach($product->variants as $v)
                                                <td class="text-center text-success">
                                                    <span class="badge bg-success text-white fs-6">{{ $grandTotalVariants[$v->id] ?? 0 }}</span>
                                                </td>
                                            @endforeach
                                            <td class="text-center text-primary">
                                                <span class="badge bg-primary text-white fs-6 fw-bold">{{ $grandTotalAll }}</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No stock available for this product.</p>
                        @endif
                    @else
                        @if($product->inventories->count())
                            <div class="row g-3">
                                @foreach($product->inventories as $inventory)
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center justify-content-between p-3 rounded border">
                                            <div>
                                                <p class="text-muted small mb-1">{{ $inventory->location->name ?? '-' }}</p>
                                                <h5 class="mb-0 {{ $inventory->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $inventory->quantity }}
                                                </h5>
                                            </div>
                                            <span class="badge bg-label-{{ $inventory->quantity > 0 ? 'success' : 'danger' }} rounded p-2">
                                                <i class="ti ti-package ti-sm"></i>
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-between p-3 rounded border border-primary">
                                        <div>
                                            <p class="text-muted small mb-1">Total Stock</p>
                                            <h5 class="mb-0 text-primary">{{ $product->inventories->sum('quantity') }}</h5>
                                        </div>
                                        <span class="badge bg-label-primary rounded p-2">
                                            <i class="ti ti-stack ti-sm"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-muted mb-0">No stock available for this product.</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>

    </div>

    @if($product->is_variable && $product->variants->count())
        @php
            if (!isset($variantStock)) {
                $variantStock = $product->getVariantStock();
            }
        @endphp
        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">Product Variants</h5></div>
            <div class="card-datatable table-responsive">
                <table class="table border-top table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Attribute Values</th>
                            <th>Purchase Price</th>
                            <th>Sale Price</th>
                            <th>Total Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->variants as $idx => $variant)
                            @php
                                $totalVQty = 0;
                                foreach ($variantStock as $locId => $data) {
                                    $totalVQty += ($data['variants'][$variant->id] ?? 0);
                                }
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $variant->attributeValue->value ?? '-' }} ({{ $variant->attributeValue->attribute->name ?? '-' }})</td>
                                <td>{{ format_price($variant->purchase_price) }}</td>
                                <td>{{ format_price($variant->sale_price) }}</td>
                                <td>
                                    <span class="badge bg-label-{{ $totalVQty > 0 ? 'success' : ($totalVQty < 0 ? 'danger' : 'secondary') }} fs-6">
                                        {{ $totalVQty }}
                                    </span>
                                </td>
                                <td>{!! status_badge($variant->status) !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
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
                                <!-- Spinner Loader -->
                                <div id="barcodeLoader" class="py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0">Generating barcode...</p>
                                </div>
                                
                                <!-- Barcode Content -->
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
                </div>
            `;
            $('#barcodeModal').remove();
            $('body').append(modal);
            const modalEl = new bootstrap.Modal(document.getElementById('barcodeModal'));
            modalEl.show();
            
            const $loader = $('#barcodeLoader');
            const $content = $('#barcodeContent');
            const $img = $('#barcodeImage');
            const $printBtn = $('#printBarcodeBtn');
            const $printQty = $('#printQty');
            
            // Show content when image loads
            $img.on('load', function() {
                $loader.addClass('d-none');
                $content.removeClass('d-none');
            }).on('error', function() {
                $loader.html('<p class="text-danger mb-0">Failed to load barcode image.</p>');
            });
            
            // Handle printing
            $printBtn.on('click', function() {
                const qty = parseInt($printQty.val()) || 1;
                const printWindow = window.open('', '_blank');
                let html = '<!DOCTYPE html><html><head><title>Print Barcodes</title>';
                html += '<style>';
                html += 'body { font-family: Arial, sans-serif; margin: 0; padding: 10px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; }';
                html += '.barcode-label { border: 1px dashed #999; padding: 15px; text-align: center; width: 220px; page-break-inside: avoid; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 4px; }';
                html += '.barcode-value { font-size: 14px; margin-bottom: 8px; font-family: monospace; font-weight: bold; }';
                html += '.barcode-image { max-width: 100%; height: auto; }';
                html += '@media print { body { padding: 0; } .barcode-label { border: 1px solid #000; page-break-inside: avoid; } }';
                html += '</style>';
                html += '</head><body>';
                
                for (let i = 0; i < qty; i++) {
                    html += '<div class="barcode-label">';
                    html += '<div class="barcode-value">' + barcode + '</div>';
                    html += '<img src="' + barcodeUrl + '" class="barcode-image" />';
                    html += '</div>';
                }
                
                html += '<script>';
                html += 'window.onload = function() {';
                html += '    setTimeout(function() {';
                html += '        window.print();';
                html += '        window.onafterprint = function() { window.close(); };';
                html += '        setTimeout(function() { window.close(); }, 500);';
                html += '    }, 500);';
                html += '};';
                html += '<\/script>';
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
                spaceBetween        : 8,
                slidesPerView       : 'auto',
                freeMode            : true,
                watchSlidesProgress : true,
                observer            : true,
                observeParents      : true,
            });

            new Swiper('.product-main-swiper', {
                spaceBetween : 10,
                navigation   : {
                    nextEl : '.swiper-button-next',
                    prevEl : '.swiper-button-prev',
                },
                thumbs        : { swiper : thumbSwiper },
                observer      : true,
                observeParents: true,
            });
            @else
            new Swiper('.product-main-swiper', {
                navigation   : {
                    nextEl : '.swiper-button-next',
                    prevEl : '.swiper-button-prev',
                },
                observer      : true,
                observeParents: true,
            });
            @endif
        }

        // Initialize after document ready
        $(document).ready(function () {
            // Check if images are already loaded
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
                            if (loadedCount >= totalImages) {
                                initProductSwiper();
                            }
                        });
                    }
                });
                // If all images were already complete
                if (loadedCount >= totalImages) {
                    initProductSwiper();
                }
            }
        });
    </script>
@endsection
