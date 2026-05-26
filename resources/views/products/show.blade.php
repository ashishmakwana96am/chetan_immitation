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
                                        <img src="{{ asset('storage/' . $image->image_path) }}"
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
                                            <img src="{{ asset('storage/' . $image->image_path) }}"
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
                            <p class="text-muted small mb-1">Category</p>
                            <p class="mb-0">
                                <span class="badge bg-label-primary">{{ $product->category->name ?? '-' }}</span>
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
                            <p class="text-muted small mb-1">Created By</p>
                            <p class="mb-0">{{ $product->createdBy->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Created Date</p>
                            <p class="mb-0">{{ format_date($product->created_at) }}</p>
                        </div>
                        @if($product->description)
                            <div class="col-12">
                                <p class="text-muted small mb-1">Description</p>
                                <p class="mb-0">{{ $product->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Stock Details -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stock Details</h5>
                </div>
                <div class="card-body">
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
                </div>
            </div>
        </div>

    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/swiper/swiper.js') }}"></script>
    <script>
        $(document).ready(function () {

            @if($product->images->count() > 1)
            const thumbSwiper = new Swiper('.product-thumb-swiper', {
                spaceBetween        : 8,
                slidesPerView       : 'auto',
                freeMode            : true,
                watchSlidesProgress : true,
            });

            new Swiper('.product-main-swiper', {
                spaceBetween : 10,
                navigation   : {
                    nextEl : '.swiper-button-next',
                    prevEl : '.swiper-button-prev',
                },
                thumbs : { swiper : thumbSwiper },
            });
            @else
            new Swiper('.product-main-swiper', {
                navigation : {
                    nextEl : '.swiper-button-next',
                    prevEl : '.swiper-button-prev',
                },
            });
            @endif

        });
    </script>
@endsection
