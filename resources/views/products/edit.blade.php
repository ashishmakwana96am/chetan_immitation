@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Edit Product</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-label-secondary">
                <i class="ti ti-eye me-1"></i> View
            </a>
            <a href="{{ route('admin.products.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <form id="productForm" action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row g-4">

            <!-- Main Details -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Product Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    placeholder="Enter Product Name" value="{{ $product->name }}" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Barcode <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <input type="text" name="barcode" class="form-control"
                                        placeholder="Enter Barcode" value="{{ $product->barcode ?? '' }}" />
                                     @if($product->barcode)
                                    <button type="button" onclick="viewBarcode('{{ $product->barcode }}', {{ $product->id }}, '{{ addslashes(!empty($product->subCategory->name) ? $product->subCategory->name : ($product->category->name ?? '')) }}', '{{ addslashes($product->variants->map(fn($v) => $v->attributeValue->value ?? '')->filter()->unique()->implode(', ')) }}', '{{ addslashes(format_price($product->sale_price)) }}')" class="btn btn-icon btn-label-secondary" title="View Barcode">
                                        <i class="ti ti-barcode"></i>
                                    </button>
                                    @endif
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                             <div class="col-md-6">
                                 <label class="form-label">Category <span class="text-danger">*</span></label>
                                 <select name="category_id" id="productCategory" class="form-select">
                                     <option value="">Select Category</option>
                                     @foreach($categories as $category)
                                         <option value="{{ $category->id }}" {{ $product->category_id === $category->id ? 'selected' : '' }}>
                                             {{ $category->name }}
                                         </option>
                                     @endforeach
                                 </select>
                                 <div class="invalid-feedback"></div>
                             </div>
                              <div class="col-md-6">
                                  <label class="form-label">Sub Category</label>
                                  <select name="sub_category_id" id="productSubCategory" class="form-select" {{ empty($subCategories) ? 'disabled' : '' }}>
                                      <option value="">Select Sub Category</option>
                                      @if(!empty($subCategories))
                                          @foreach($subCategories as $subCategory)
                                              <option value="{{ $subCategory->id }}" {{ $product->sub_category_id === $subCategory->id ? 'selected' : '' }}>
                                                  {{ $subCategory->name }}
                                              </option>
                                          @endforeach
                                      @endif
                                  </select>
                                  <div class="invalid-feedback"></div>
                              </div>
                              <div class="col-md-6">
                                  <label class="form-label">Product Code <span class="text-danger">*</span></label>
                                  <input type="number" name="product_code" id="productCodeInput" class="form-control"
                                      placeholder="Enter Product Code" step="0.01" min="0.01" value="{{ $product->product_code }}" required />
                                  <div class="invalid-feedback"></div>
                              </div>
                              <div class="col-md-4">
                                  <label class="form-label">Purchase Multiplier <span class="text-danger">*</span></label>
                                  <input type="number" name="purchase_multiplier" id="purchaseMultiplierInput" class="form-control" placeholder="Enter Purchase Multiplier" step="0.001" min="0" value="{{ $product->purchase_multiplier }}" required />
                                  <div class="invalid-feedback"></div>
                              </div>
                              <div class="col-md-4">
                                  <label class="form-label">Sale Multiplier <span class="text-danger">*</span></label>
                                  <input type="number" name="sale_multiplier" id="saleMultiplierInput" class="form-control" placeholder="Enter Sale Multiplier" step="0.001" min="0" value="{{ $product->sale_multiplier }}" required />
                                  <div class="invalid-feedback"></div>
                              </div>
                              <div class="col-md-4">
                                  <label class="form-label">MRP Multiplier <span class="text-danger">*</span></label>
                                  <input type="number" name="mrp_multiplier" id="mrpMultiplierInput" class="form-control" placeholder="Enter MRP Multiplier" step="0.001" min="0" value="{{ $product->mrp_multiplier }}" required />
                                  <div class="invalid-feedback"></div>
                              </div>
                              <div class="col-md-6" id="purchasePriceCol">
                                  <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                                  <div class="input-group has-validation">
                                      <span class="input-group-text">{{ currency_symbol() }}</span>
                                      <input type="number" name="purchase_price" id="purchasePriceInput" class="form-control"
                                          placeholder="Enter Purchase Price" step="0.01" min="0" value="{{ $product->purchase_price }}" />
                                      <div class="invalid-feedback"></div>
                                  </div>
                              </div>
                              <div class="col-md-6 base-price-field">
                                  <label class="form-label" id="salePriceLabel">Sale Price <span class="text-danger">*</span></label>
                                  <div class="input-group has-validation">
                                      <span class="input-group-text">{{ currency_symbol() }}</span>
                                      <input type="number" name="sale_price" id="salePriceInput" class="form-control"
                                          placeholder="Enter Sale Price" step="0.01" min="0" value="{{ $product->sale_price }}" />
                                      <div class="invalid-feedback"></div>
                                  </div>
                              </div>
                              <div class="col-md-6 base-price-field">
                                  <label class="form-label" id="mrpLabel">MRP <span class="text-danger">*</span></label>
                                  <div class="input-group has-validation">
                                      <span class="input-group-text">{{ currency_symbol() }}</span>
                                      <input type="number" name="mrp" id="mrpInput" class="form-control"
                                          placeholder="Enter MRP" step="0.01" min="0" value="{{ $product->mrp }}" />
                                      <div class="invalid-feedback"></div>
                                  </div>
                              </div>

                              {{-- Pair Product pricing fields (shown only when pair_product is enabled) --}}
                              @php
                                  $productPairMode = $product->pair_mode ?: 'pieces_pair';
                              @endphp
                              <div class="col-md-6 pair-pricing-field pair-mode-pieces-field {{ $product->pair_product && $productPairMode !== 'custom_size' ? '' : 'd-none' }}">
                                  <label class="form-label">Sale Price (Pair) <span class="text-danger">*</span></label>
                                  <div class="input-group has-validation">
                                      <span class="input-group-text">{{ currency_symbol() }}</span>
                                      <input type="number" name="pair_sale_price" id="pairSalePriceInput" class="form-control"
                                          placeholder="Enter Pair Sale Price" step="0.01" min="0" value="{{ $product->pair_sale_price }}" />
                                      <div class="invalid-feedback"></div>
                                  </div>
                              </div>
                              <div class="col-md-6 pair-pricing-field pair-mode-pieces-field {{ $product->pair_product && $productPairMode !== 'custom_size' ? '' : 'd-none' }}">
                                  <label class="form-label">MRP (Pair) <span class="text-danger">*</span></label>
                                  <div class="input-group has-validation">
                                      <span class="input-group-text">{{ currency_symbol() }}</span>
                                      <input type="number" name="pair_mrp" id="pairMrpInput" class="form-control"
                                          placeholder="MRP (Pair)" step="0.01" min="0" value="{{ $product->pair_mrp }}" />
                                      <div class="invalid-feedback"></div>
                                  </div>
                              </div>
                              <div class="col-12 pair-pricing-field pair-mode-custom-field {{ $product->pair_product && $productPairMode === 'custom_size' ? '' : 'd-none' }}" id="customSizeSection">
                                  <div class="row g-3" id="customSizeRows"></div>
                              </div>
                             <div class="col-md-6" id="productTypeCol">
                                 <label class="form-label">Product Type <span class="text-danger">*</span></label>
                                 <select name="type" id="productType" class="form-select no-select2">
                                     <option value="normal" {{ $product->type === 'variable' ? '' : 'selected' }}>Normal Product</option>
                                     <option value="variable" {{ $product->type === 'variable' ? 'selected' : '' }}>Variable Product</option>
                                 </select>
                                <div class="invalid-feedback"></div>
                              </div>
                              <div id="variableSection" class="{{ $product->type === 'variable' ? '' : 'd-none' }}">
                                @php
                                    $normalStock = $product->type === 'normal' ? (int)$product->totalAvailableStock() : 0;
                                @endphp
                                @if($product->type === 'normal' && $normalStock > 0)
                                    <div id="stockMigrationCard" class="card border border-warning shadow-sm mb-3" style="border-left: 3px solid #ffab00 !important; background-color: #fffdf5;">
                                        <div class="card-body py-3">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="ti ti-box text-warning fs-4"></i>
                                                    <h6 class="mb-0 text-warning fw-bold">Distribute Existing Normal Product Stock</h6>
                                                </div>
                                                <span class="badge bg-warning text-dark fs-6" id="stockMigrationTotalBadge" data-total="{{ $normalStock }}">Total Available: {{ $normalStock }} Pcs</span>
                                            </div>
                                            <p class="small text-muted mb-2">
                                                This Normal Product currently has <strong>{{ $normalStock }} Pcs</strong> of stock. Enter how much stock to allocate to each variation below:
                                            </p>
                                            <div id="stockMigrationInputsContainer" class="row g-2 pt-1">
                                                <div class="text-muted small">Select attributes below to display variants for stock allocation.</div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                                <span class="small fw-semibold text-secondary" id="stockMigrationAllocatedSummary">Allocated: 0 / {{ $normalStock }} Pcs</span>
                                                <span class="small fw-semibold text-success" id="stockMigrationRemainingSummary">Remaining: {{ $normalStock }} Pcs</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="card border shadow-sm mb-3" style="border-left: 3px solid #B4771E !important;">
                                    <div class="card-header py-3 bg-white">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <i class="ti ti-adjustments-horizontal me-1" style="color:#B4771E;"></i>
                                                <h6 class="mb-0 d-inline">Select Attributes</h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div id="attributesCheckboxes" class="d-flex flex-wrap gap-2 pt-3">
                                            @foreach($attributes as $attr)
                                                @php
                                                    $isSelected = $product->variants->pluck('attributeValue.attribute_id')->unique()->contains($attr->id);
                                                @endphp
                                                <label class="attribute-chip btn btn-sm d-inline-flex align-items-center gap-2 px-3 py-2 cursor-pointer mb-0 {{ $isSelected ? 'active' : '' }}" for="attr_{{ $attr->id }}" style="transition:all .2s;user-select:none;">
                                                    <input class="form-check-input attribute-select m-0 d-none" type="checkbox" data-attribute-id="{{ $attr->id }}" id="attr_{{ $attr->id }}" {{ $isSelected ? 'checked' : '' }} style="cursor:pointer;" />
                                                    <span class="fw-medium" style="font-size:.85rem;">{{ $attr->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @if($attributes->count() === 0)
                                            <div class="text-muted small mt-2" id="noAttributesMsg">No attributes available. Please create attributes first.</div>
                                        @endif
                                    </div>
                                </div>
                                <div id="variantsContainer">
                                    <label class="form-label">Variants</label>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0" id="variantsTable">
                                            <thead class="table-light">
                                                <tr id="variantsHeader">
                                                    <th style="width:50px">#</th>
                                                    <th>Attribute Values</th>
                                                    <th style="width:150px">Purchase Price <span class="text-danger">*</span></th>
                                                    <th style="width:150px">Sale Price <span class="text-danger">*</span></th>
                                                    <th style="width:60px">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="variantsBody"></tbody>
                                        </table>
                                    </div>
                                    <input type="hidden" name="variants_json" id="variantsJson" value="" />
                                    <div class="invalid-feedback d-block text-danger mt-1"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <div id="description-editor">{!! $product->description !!}</div>
                                <textarea name="description" id="description-textarea" class="d-none">{{ $product->description }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Additional Information</label>
                                <div id="information-editor">{!! $product->additional_information !!}</div>
                                <textarea name="additional_information" id="information-textarea" class="d-none">{{ $product->additional_information }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Product Highlights</label>
                                <div id="highlights-editor">{!! $product->product_highlights !!}</div>
                                <textarea name="product_highlights" id="highlights-textarea" class="d-none">{{ $product->product_highlights }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">

                <!-- Status & Sale -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Status & Sale</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="productStatus" name="status" value="1"
                                {{ $product->status == 1 ? 'checked' : '' }} />
                            <label class="form-check-label" for="productStatus">Active</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="productSale" name="sale" value="1"
                                {{ $product->sale == 1 ? 'checked' : '' }} />
                            <label class="form-check-label" for="productSale">Sale</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="productPair" name="pair_product" value="1"
                                {{ $product->pair_product == 1 ? 'checked' : '' }} />
                            <label class="form-check-label" for="productPair">Pair Product</label>
                        </div>

                        <div class="pair-pricing-field mb-3 {{ $product->pair_product ? '' : 'd-none' }}">
                            <label class="form-label d-block">Pair Pricing Mode</label>
                            <div class="pair-mode-toggle" id="pairModeToggle">
                                <button type="button" class="pair-mode-btn {{ $productPairMode === 'custom_size' ? '' : 'active' }}" data-value="pieces_pair">Pieces + Pair</button>
                                <button type="button" class="pair-mode-btn {{ $productPairMode === 'custom_size' ? 'active' : '' }}" data-value="custom_size">Custom Size</button>
                            </div>
                            <input type="hidden" name="pair_mode" id="pairModeInput" value="{{ $productPairMode }}" />
                        </div>

                        <div class="pair-pricing-field pair-mode-custom-field mb-3 {{ $product->pair_product && $productPairMode === 'custom_size' ? '' : 'd-none' }}">
                            <label class="form-label">Custom Sizes <span class="text-danger">*</span></label>
                            <div class="custom-sizes-tag-input form-control d-flex flex-wrap align-items-center gap-1" id="customSizesTagWrapper" style="cursor: text;">
                                <div id="customSizeBadges" class="d-flex flex-wrap gap-1"></div>
                                <input type="text" id="customSizesInput" inputmode="numeric" class="border-0 flex-grow-1 p-0" style="outline: none; min-width: 60px; box-shadow: none;" placeholder="Type size & press Enter" />
                            </div>
                            <input type="hidden" name="custom_sizes_json" id="customSizesJson" />
                            <div class="invalid-feedback d-block" id="customSizesError"></div>
                        </div>

                        @if(auth()->user()->hasRole('super-admin'))
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="productBypassMinPrice" name="bypass_min_price" value="1"
                                    {{ $product->bypass_min_price == 1 ? 'checked' : '' }} />
                                <label class="form-check-label" for="productBypassMinPrice">Allow Below Cost Price on Sale</label>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Primary Image -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Primary Image</h5></div>
                    <div class="card-body">
                        <input type="hidden" name="primary_image_base64" id="primaryImageBase64" />
                        <input type="hidden" name="remove_primary_image" id="removePrimaryImageVal" value="0" />
                        
                        @php $primaryImage = $product->images->firstWhere('is_primary', true); @endphp
                        <div id="existingPrimaryContainer" class="mb-3 {{ $primaryImage ? '' : 'd-none' }}">
                            <p class="text-muted small mb-1 text-start">Current primary image:</p>
                            <div class="position-relative d-inline-block">
                                <img src="{{ $primaryImage ? $primaryImage->image_url : '' }}"
                                    id="existingPrimaryImg" width="120" height="120" class="rounded object-fit-cover border border-primary border-2" />
                                <button type="button" id="removeExistingPrimaryBtn" class="btn btn-danger btn-icon btn-sm position-absolute top-0 end-0 m-1 rounded-circle" style="padding: 0; width: 24px; height: 24px; min-width: 24px;" title="Remove Current Image">
                                    <i class="ti ti-x" style="font-size: 0.8rem;"></i>
                                </button>
                            </div>
                        </div>

                        <div id="primaryDropZone" class="border border-2 rounded-3 p-4 text-center cursor-pointer position-relative" style="border-style: dashed !important; border-color: #cbd5e1 !important; background-color: #f8fafc; transition: all 0.2s ease; min-height: 150px; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden;">
                            <input type="file" id="primaryImageInput" class="position-absolute" style="position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; opacity: 0 !important; cursor: pointer !important; z-index: 10 !important; margin: 0 !important; padding: 0 !important;" accept="image/*" />
                            <div class="dz-message py-2" style="pointer-events: none; position: relative; z-index: 1;">
                                <i class="ti ti-cloud-upload text-muted mb-2" style="font-size: 2.5rem !important;"></i>
                                <p class="fw-semibold mb-1" style="font-size: 0.95rem; color: #4b4b4b;">Drag & drop your image here or click to browse</p>
                                <span class="text-muted small">Supports: JPG, JPEG, PNG, WEBP (Max 50MB)</span>
                            </div>
                        </div>
                        <div class="invalid-feedback d-block text-danger mt-1" id="primaryImageError"></div>
                        <div id="primaryImagePreview" class="mt-3 d-none text-center">
                            <div class="position-relative d-inline-block">
                                <img id="primaryImageThumb" src="" width="120" height="120" class="rounded object-fit-cover border border-primary border-2" />
                                <button type="button" id="removePrimaryImageBtn" class="btn btn-danger btn-icon btn-sm position-absolute top-0 end-0 m-1 rounded-circle" style="padding: 0; width: 24px; height: 24px; min-width: 24px;" title="Remove New Image">
                                    <i class="ti ti-x" style="font-size: 0.8rem;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Images -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Additional Images</h5></div>
                    <div class="card-body">
                        @php $additionalImages = $product->images->where('is_primary', false); @endphp
                        <div class="d-flex flex-wrap gap-2 mb-3 {{ $additionalImages->count() ? '' : 'd-none' }}" id="existingImages">
                            @foreach($additionalImages as $image)
                                <div class="position-relative" id="img-{{ $image->id }}">
                                    <img src="{{ $image->image_url }}"
                                        width="80" height="80" class="rounded object-fit-cover border" />
                                    <button type="button"
                                        class="btn btn-danger btn-icon btn-sm position-absolute top-0 end-0 m-1 rounded-circle btn-delete-image"
                                        data-url="{{ route('admin.products.images.destroy', $image) }}"
                                        data-id="{{ $image->id }}" style="padding: 0; width: 20px; height: 20px; min-width: 20px;" title="Remove">
                                        <i class="ti ti-x" style="font-size: 0.7rem;"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <div id="additionalDropZone" class="border border-2 rounded-3 p-4 text-center cursor-pointer position-relative" style="border-style: dashed !important; border-color: #cbd5e1 !important; background-color: #f8fafc; transition: all 0.2s ease; min-height: 150px; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden;">
                            <input type="file" id="additionalImagesInput" class="position-absolute" style="position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; opacity: 0 !important; cursor: pointer !important; z-index: 10 !important; margin: 0 !important; padding: 0 !important;" multiple accept="image/*" />
                            <div class="dz-message py-2" style="pointer-events: none; position: relative; z-index: 1;">
                                <i class="ti ti-cloud-upload text-muted mb-2" style="font-size: 2.5rem !important;"></i>
                                <p class="fw-semibold mb-1" style="font-size: 0.95rem; color: #4b4b4b;">Drag & drop your image here or click to browse</p>
                                <span class="text-muted small">Supports: JPG, JPEG, PNG, WEBP (Max 50MB)</span>
                            </div>
                        </div>
                        <div class="invalid-feedback d-block text-danger mt-1" id="additionalImagesError"></div>
                        <div id="additionalPreview" class="d-flex flex-wrap gap-2 mt-3"></div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ti ti-device-floppy me-1"></i> Update Product
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-label-secondary">Cancel</a>
                </div>
            </div>

        </div>
    </form>

    <!-- Crop Image Modal -->
    <div class="modal fade" id="cropImageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cropModalTitle" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: calc(100% - 30px);">Crop Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="img-container" style="max-height: 500px; overflow: hidden; background: #fff; display: flex; justify-content: center; align-items: center;">
                        <img id="imageToCrop" src="" style="max-width: 100%; max-height: 500px; display: block;" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="cropSaveBtn">Crop & Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/typography.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/katex.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    <style>
        .attribute-chip { background:#f5f5f5; border:1px solid #e0e0e0; border-radius:20px; font-size:.82rem; }
        .attribute-chip:hover { border-color:#B4771E; background:#fcf6ed; }
        .attribute-chip.active { background:#B4771E !important; border-color:#B4771E !important; color:#fff !important; box-shadow:0 2px 6px rgba(180,119,30,.3); }

        /* Pair pricing mode toggle */
        .pair-mode-toggle { display: flex; border-radius: 6px; overflow: hidden; border: 1.5px solid #B4771E; }
        .pair-mode-toggle .pair-mode-btn {
            flex: 1; padding: 6px 14px; font-size: .85rem; font-weight: 600; border: none; cursor: pointer;
            background: #fff; color: #B4771E; transition: background .15s, color .15s;
        }
        .pair-mode-toggle .pair-mode-btn + .pair-mode-btn { border-left: 1.5px solid #B4771E; }
        .pair-mode-toggle .pair-mode-btn.active { background: #B4771E; color: #fff; }
    </style>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/quill/katex.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        $(document).ready(function () {



            // Initialize Quill Editor for Description
            const descriptionQuill = new Quill('#description-editor', {
                theme: 'snow',
                placeholder: 'Enter product description...'
            });
            
            descriptionQuill.on('text-change', function() {
                $('#description-textarea').val(descriptionQuill.root.innerHTML === '<p><br></p>' ? '' : descriptionQuill.root.innerHTML).trigger('input');
            });

            // Initialize Quill Editor for Additional Information
            const infoQuill = new Quill('#information-editor', {
                theme: 'snow',
                placeholder: 'Enter additional information...'
            });
            
            infoQuill.on('text-change', function() {
                $('#information-textarea').val(infoQuill.root.innerHTML === '<p><br></p>' ? '' : infoQuill.root.innerHTML).trigger('input');
            });

            // Initialize Quill Editor for Product Highlights
            const highlightsQuill = new Quill('#highlights-editor', {
                theme: 'snow',
                placeholder: 'Enter product highlights...'
            });

            highlightsQuill.on('text-change', function() {
                $('#highlights-textarea').val(highlightsQuill.root.innerHTML === '<p><br></p>' ? '' : highlightsQuill.root.innerHTML).trigger('input');
            });

            // Dynamic Sub Categories load
            $('#productCategory').on('change', function () {
                const categoryId = $(this).val();
                const subCategorySelect = $('#productSubCategory');

                subCategorySelect.empty().append('<option value="">Select Sub Category</option>');

                if (!categoryId) {
                    subCategorySelect.prop('disabled', true);
                    return;
                }

                $.ajax({
                    url: '{{ route('admin.products.sub-categories') }}',
                    type: 'GET',
                    data: { category_id: categoryId },
                    success: function (res) {
                        if (res && res.length > 0) {
                            $.each(res, function (i, subCat) {
                                subCategorySelect.append('<option value="' + subCat.id + '">' + subCat.name + '</option>');
                            });
                            subCategorySelect.prop('disabled', false);
                        } else {
                            subCategorySelect.prop('disabled', true);
                        }
                    },
                    error: function () {
                        toastr.error('Failed to load sub categories.');
                        subCategorySelect.prop('disabled', true);
                    }
                });
            });

            // Image cropping state variables
            let cropper = null;
            let currentCroppingField = null;
            let additionalFilesQueue = [];
            let cropSaved = false;

            const $cropModal = $('#cropImageModal');
            const $cropImage = $('#imageToCrop');
            const $cropSaveBtn = $('#cropSaveBtn');

            // Move modal to body to avoid offcanvas constraint issues
            if ($cropModal.length && !$cropModal.parent().is('body')) {
                $cropModal.appendTo('body');
            }

            // Setup hover styling for dropzones
            function setupDropZoneEvents(fileInputId, dropZoneId) {
                const $fileInput = $('#' + fileInputId);
                const $dropZone = $('#' + dropZoneId);

                $fileInput.on('dragenter dragover', function () {
                    $dropZone.css({
                        'border-color': '#B4771E',
                        'background-color': '#f1f0ff'
                    });
                });

                $fileInput.on('dragleave drop', function () {
                    $dropZone.css({
                        'border-color': '#cbd5e1',
                        'background-color': '#f8fafc'
                    });
                });
            }

            setupDropZoneEvents('primaryImageInput', 'primaryDropZone');
            setupDropZoneEvents('additionalImagesInput', 'additionalDropZone');

            // 1. Primary Image select & crop triggering
            $('#primaryImageInput').on('change', function () {
                const file = this.files[0];
                if (!file) return;

                if (file.size > 50 * 1024 * 1024) {
                    toastr.error('Primary image must be less than 50 MB.');
                    this.value = '';
                    return;
                }

                currentCroppingField = 'primary';
                cropSaved = false;

                const reader = new FileReader();
                reader.onload = function (e) {
                    openCropModal(e.target.result, 'Crop Primary Image');
                };
                reader.readAsDataURL(file);
            });

            // 2. Additional Images select & crop triggering (sequential queue)
            $('#additionalImagesInput').on('change', function () {
                const files = this.files;
                if (!files || files.length === 0) return;

                additionalFilesQueue = Array.from(files).filter(file => {
                    if (file.size > 50 * 1024 * 1024) {
                        toastr.error(`File ${file.name} exceeds 50 MB limit and was skipped.`);
                        return false;
                    }
                    return true;
                });

                if (additionalFilesQueue.length === 0) {
                    this.value = '';
                    return;
                }

                currentCroppingField = 'additional';
                cropSaved = false;
                this.value = ''; // clear input so change event can fire again
                processNextAdditionalFile();
            });

            function processNextAdditionalFile() {
                if (additionalFilesQueue.length === 0) {
                    return;
                }
                const file = additionalFilesQueue.shift();
                cropSaved = false;

                const reader = new FileReader();
                reader.onload = function (e) {
                    openCropModal(e.target.result, `Crop Additional Image (${additionalFilesQueue.length + 1} remaining)`);
                };
                reader.readAsDataURL(file);
            }

            function openCropModal(imgSrc, title) {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }

                $cropImage.attr('src', imgSrc);
                $('#cropModalTitle').text(title);

                // Set button text based on queue
                if (currentCroppingField === 'additional' && additionalFilesQueue.length > 0) {
                    $cropSaveBtn.text('Save & Next');
                } else {
                    $cropSaveBtn.text('Crop & Save');
                }

                const img = new Image();
                img.src = imgSrc;
                img.onload = function () {
                    const maxHeight = Math.max(300, Math.min(500, window.innerHeight - 220));
                    $cropImage.css('max-height', maxHeight + 'px');
                    $cropModal.find('.img-container').css('max-height', maxHeight + 'px');

                    let modalWidth = img.width;
                    if (img.height > maxHeight) {
                        modalWidth = (img.width * maxHeight) / img.height;
                    }

                    const minWidth = 400;
                    const maxWidth = window.innerWidth * 0.9;
                    modalWidth = Math.max(minWidth, Math.min(modalWidth, maxWidth));

                    $cropModal.find('.modal-dialog').css({
                        'max-width': modalWidth + 'px',
                        'width': '100%'
                    });

                    // Check if modal is already open
                    const isShown = $cropModal.hasClass('show');
                    if (!isShown) {
                        let modalObj = bootstrap.Modal.getInstance($cropModal[0]);
                        if (!modalObj) {
                            modalObj = new bootstrap.Modal($cropModal[0]);
                        }
                        modalObj.show();
                    } else {
                        // Re-initialize cropper immediately as shown event won't fire
                        cropper = new Cropper($cropImage[0], {
                            aspectRatio: 1,
                            viewMode: 1,
                            autoCropArea: 0.8,
                            background: false,
                            responsive: true,
                        });
                    }
                };
            }

            $cropModal.on('shown.bs.modal', function () {
                if (!cropper) {
                    cropper = new Cropper($cropImage[0], {
                        aspectRatio: 1,
                        viewMode: 1,
                        autoCropArea: 0.8,
                        background: false,
                        responsive: true,
                    });
                }
            }).on('hidden.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                $cropImage.attr('src', '');

                // Reset inputs to allow selecting same file again
                $('#primaryImageInput').val('');
                $('#additionalImagesInput').val('');

                // If user closed/cancelled, abort the remaining queue
                if (!cropSaved && currentCroppingField === 'additional') {
                    additionalFilesQueue = [];
                }
            });

            $cropSaveBtn.on('click', function () {
                if (!cropper) return;
                const canvas = cropper.getCroppedCanvas({
                    width: 573,
                    height: 573,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });
                if (canvas) {
                    const dataUrl = canvas.toDataURL('image/jpeg', 1.0);
                    cropSaved = true;

                    if (currentCroppingField === 'primary') {
                        $('#primaryImageBase64').val(dataUrl);
                        $('#primaryImageThumb').attr('src', dataUrl);
                        $('#primaryImagePreview').removeClass('d-none');
                        $('#existingPrimaryContainer').addClass('d-none'); // Hide old primary
                        $('#removePrimaryImageVal').val('0');
                        $('#primaryImageInput').removeClass('is-invalid');
                        $('#primaryImageError').text('');
                        
                        bootstrap.Modal.getInstance($cropModal[0]).hide();
                    } else if (currentCroppingField === 'additional') {
                        const uniqueId = Date.now() + '_' + Math.floor(Math.random() * 1000);
                        $('#additionalPreview').append(`
                            <div class="position-relative additional-img-card" id="added-img-${uniqueId}">
                                <img src="${dataUrl}" width="80" height="80" class="rounded object-fit-cover border" />
                                <input type="hidden" name="additional_images_base64[]" value="${dataUrl}" />
                                <button type="button" class="btn btn-danger btn-icon btn-sm position-absolute top-0 end-0 m-1 rounded-circle remove-additional-img-btn" data-id="${uniqueId}" style="padding: 0; width: 20px; height: 20px; min-width: 20px;" title="Remove">
                                    <i class="ti ti-x" style="font-size: 0.7rem;"></i>
                                </button>
                            </div>
                        `);
                        $('#additionalImagesInput').removeClass('is-invalid');
                        $('#additionalImagesError').text('');

                        if (additionalFilesQueue.length > 0) {
                            // Process the next image in the queue without closing the modal
                            processNextAdditionalFile();
                        } else {
                            bootstrap.Modal.getInstance($cropModal[0]).hide();
                        }
                    }
                }
            });

            // Existing primary image remove button click handler
            $('#removeExistingPrimaryBtn').on('click', function () {
                $('#existingPrimaryContainer').addClass('d-none');
                $('#removePrimaryImageVal').val('1'); // Mark existing for deletion
            });

            // New primary image remove button click handler
            $('#removePrimaryImageBtn').on('click', function () {
                $('#primaryImageInput').val('');
                $('#primaryImageBase64').val('');
                $('#primaryImageThumb').attr('src', '');
                $('#primaryImagePreview').addClass('d-none');
                
                // If existing primary was previously active, restore it, otherwise keep it marked for removal
                @if($primaryImage)
                    $('#existingPrimaryContainer').removeClass('d-none');
                    $('#removePrimaryImageVal').val('0');
                @else
                    $('#removePrimaryImageVal').val('1');
                @endif
            });

            // Additional image remove button click handler
            $(document).on('click', '.remove-additional-img-btn', function () {
                const uniqueId = $(this).data('id');
                $('#added-img-' + uniqueId).remove();
            });

            // Submit via AJAX with FormData
            $('#productForm').on('submit', function (e) {
                e.preventDefault();

                if ($('#productType').val() === 'variable') {
                    if ($('.attribute-select:checked').length === 0 || !variantsData || variantsData.length === 0) {
                        $('#variantsJson').addClass('is-invalid');
                        $('#variantsJson').siblings('.invalid-feedback').text('Please add at least one attribute & variant.');
                        toastr.error('Please add at least one attribute & variant.');
                        return;
                    }

                    if ($('#stockMigrationTotalBadge').length > 0) {
                        const totalAvailable = parseInt($('#stockMigrationTotalBadge').data('total')) || 0;
                        if (totalAvailable > 0) {
                            let totalAllocated = 0;
                            $('.stock-migration-input').each(function () {
                                totalAllocated += parseInt($(this).val()) || 0;
                            });

                            if (totalAllocated !== totalAvailable) {
                                const msg = 'Please allocate all ' + totalAvailable + ' Pcs of existing stock across variations before updating. (Currently allocated: ' + totalAllocated + ' Pcs)';
                                toastr.error(msg);
                                $('html, body').animate({
                                    scrollTop: $("#stockMigrationCard").offset().top - 100
                                }, 500);
                                return false;
                            }
                        }
                    }
                }

                if ($('#productPair').is(':checked') && currentPairMode() === 'custom_size') {
                    let customSizeError = false;
                    if (!customSizesList || customSizesList.length === 0) {
                        toastr.error('Please add at least one custom size with pricing.');
                        return;
                    }
                    $('#customSizeRows .custom-size-sale-field').each(function () {
                        const index = $(this).data('index');
                        const size = $(this).data('size');
                        const $saleInput = $(this).find('.custom-size-sale-price');
                        const $mrpInput = $('#customSizeRows .custom-size-mrp-field[data-index="' + index + '"]').find('.custom-size-mrp');
                        const saleVal = parseFloat($saleInput.val());
                        const mrpVal = parseFloat($mrpInput.val());

                        $saleInput.removeClass('is-invalid');
                        $saleInput.siblings('.invalid-feedback').text('');
                        $mrpInput.removeClass('is-invalid');
                        $mrpInput.siblings('.invalid-feedback').text('');

                        if (isNaN(saleVal) || saleVal <= 0) {
                            $saleInput.addClass('is-invalid');
                            $saleInput.siblings('.invalid-feedback').text('Sale Price (' + size + ' pcs) is required.');
                            customSizeError = true;
                        }
                        if (isNaN(mrpVal) || mrpVal <= 0) {
                            $mrpInput.addClass('is-invalid');
                            $mrpInput.siblings('.invalid-feedback').text('MRP (' + size + ' pcs) is required.');
                            customSizeError = true;
                        }
                    });
                    if (customSizeError) {
                        toastr.error('Please fill in valid Sale Price and MRP for all custom sizes.');
                        return;
                    }
                }

                const form     = $(this);
                const formData = new FormData(this);

                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
                $('#primaryImageError').text('');
                $('#additionalImagesError').text('');
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

                $.ajax({
                    url         : form.attr('action'),
                    type        : 'POST', // Sends PUT internally due to _method input
                    data        : formData,
                    processData : false,
                    contentType : false,
                    success : function (res) {
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            setTimeout(() => window.location.href = '{{ route('admin.products.index') }}', 800);
                        }
                    },
                    error : function (xhr) {
                        $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Update Product');
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON?.message || {};
                            $.each(errors, function (field, messages) {
                                if (field === 'stock_migration') {
                                    toastr.error(messages[0]);
                                    $('html, body').animate({
                                        scrollTop: $("#stockMigrationCard").offset().top - 100
                                    }, 500);
                                } else if (field === 'primary_image_base64') {
                                    $('#primaryImageError').text(messages[0]);
                                    $('#primaryDropZone').css('border-color', '#ea5455');
                                } else if (field === 'additional_images_base64') {
                                    $('#additionalImagesError').text(messages[0]);
                                    $('#additionalDropZone').css('border-color', '#ea5455');
                                } else if (field === 'variants_json') {
                                    $('#variantsJson').siblings('.invalid-feedback').text('Please add at least one attribute & variant.');
                                } else if (field === 'custom_sizes_json') {
                                    toastr.error(messages[0]);
                                } else {
                                    const input = form.find('[name="' + field + '"], [name="' + field + '[]"]');
                                    let feedback = input.siblings('.invalid-feedback');
                                    if (feedback.length === 0 && input.parent('.input-group').length) {
                                        feedback = input.parent('.input-group').siblings('.invalid-feedback');
                                    }
                                    if (input.length && feedback.length) {
                                        input.addClass('is-invalid');
                                        feedback.text(messages[0]);
                                    } else {
                                        toastr.error(messages[0]);
                                    }
                                }
                            });
                        } else {
                            toastr.error('Something went wrong. Please try again.');
                        }
                    }
                });
            });

            // Mark additional image for deletion on save
            $(document).on('click', '.btn-delete-image', function () {
                const id = $(this).data('id');
                $('#img-' + id).fadeOut(300, function() {
                    $(this).addClass('d-none');
                    $('#productForm').append('<input type="hidden" name="deleted_additional_images[]" value="' + id + '" />');
                    if ($('#existingImages').children(':visible').length === 0) {
                        $('#existingImages').addClass('d-none');
                    }
                });
            });

            // ====== Variable Product Logic ======
            const allAttributes = @json($attributes);
            const existingVariants = @json($product->variants);
            let variantsData = [];
            const removedValueIds = {};

            (function() {
                const activeAttributeIds = {};
                const existingValueIds = {};
                existingVariants.forEach(function (v) {
                    const valId = parseInt(v.attribute_value_id);
                    existingValueIds[valId] = true;
                    for (let a = 0; a < allAttributes.length; a++) {
                        const attr = allAttributes[a];
                        const hasVal = attr.values.some(function (val) { return parseInt(val.id) === valId; });
                        if (hasVal) {
                            activeAttributeIds[parseInt(attr.id)] = true;
                            break;
                        }
                    }
                });

                allAttributes.forEach(function (attr) {
                    const attrId = parseInt(attr.id);
                    if (activeAttributeIds[attrId]) {
                        attr.values.forEach(function (val) {
                            const valId = parseInt(val.id);
                            if (!existingValueIds[valId]) {
                                removedValueIds[valId] = true;
                            }
                        });
                    }
                });
            })();

            function buildExistingMap() {
                const map = {};
                existingVariants.forEach(function (v) {
                    map[v.attribute_value_id] = { purchase_price: v.purchase_price, sale_price: v.sale_price, status: v.status };
                });
                return map;
            }

            const existingMap = buildExistingMap();

            function findAttrValue(attrValueId) {
                for (let a = 0; a < allAttributes.length; a++) {
                    const attr = allAttributes[a];
                    for (let v = 0; v < attr.values.length; v++) {
                        if (attr.values[v].id === attrValueId) {
                            return { attrName: attr.name, valName: attr.values[v].value };
                        }
                    }
                }
                return { attrName: '', valName: '' };
            }

            function updateStockMigrationSummary() {
                if ($('#stockMigrationTotalBadge').length === 0) return;
                const totalAvailable = parseInt($('#stockMigrationTotalBadge').data('total')) || 0;
                let totalAllocated = 0;
                $('.stock-migration-input').each(function () {
                    const val = parseInt($(this).val()) || 0;
                    totalAllocated += val;
                });
                const remaining = totalAvailable - totalAllocated;
                
                $('#stockMigrationAllocatedSummary').text('Allocated: ' + totalAllocated + ' / ' + totalAvailable + ' Pcs');
                
                if (remaining < 0) {
                    $('#stockMigrationRemainingSummary').html('<span class="text-danger fw-bold"><i class="ti ti-alert-triangle me-1"></i>Exceeded by ' + Math.abs(remaining) + ' Pcs!</span>');
                } else {
                    $('#stockMigrationRemainingSummary').html('<span class="text-success fw-semibold">Remaining: ' + remaining + ' Pcs</span>');
                }
            }

            $(document).on('input change', '.stock-migration-input', function () {
                updateStockMigrationSummary();
            });

            function renderVariantsTable() {
                let headerHtml = '<th style="width:50px">#</th><th>Attribute</th><th>Value</th>';
                headerHtml += '<th style="width:200px">Purchase Price <span class="text-danger">*</span></th>';
                headerHtml += '<th style="width:200px">Sale Price <span class="text-danger">*</span></th>';
                headerHtml += '<th style="width:60px">Action</th>';
                $('#variantsHeader').html(headerHtml);

                if ($('#stockMigrationInputsContainer').length > 0) {
                    const totalAvailable = parseInt($('#stockMigrationTotalBadge').data('total')) || 0;
                    let inputsHtml = '';
                    if (variantsData && variantsData.length > 0) {
                        variantsData.forEach(function (v) {
                            const info = findAttrValue(v.attribute_value_id);
                            const label = (info.attrName ? info.attrName + ': ' : '') + info.valName;
                            const existingVal = $('#stock_migration_' + v.attribute_value_id).val() || '';
                            inputsHtml += `
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group mb-1">
                                        <label class="form-label mb-1 small text-dark fw-semibold" title="${label}">${label}</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="stock_migration[${v.attribute_value_id}]" id="stock_migration_${v.attribute_value_id}" class="form-control form-control-sm stock-migration-input" data-attr-val-id="${v.attribute_value_id}" value="${existingVal}" min="0" max="${totalAvailable}" placeholder="0 Pcs" />
                                            <span class="input-group-text">Pcs</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        inputsHtml = '<div class="text-muted small">Select attributes below to display variants for stock allocation.</div>';
                    }
                    $('#stockMigrationInputsContainer').html(inputsHtml);
                    updateStockMigrationSummary();
                }

                if (!variantsData || variantsData.length === 0) {
                    $('#variantsBody').empty();
                    $('#variantsJson').val('');
                    return;
                }

                let bodyHtml = '';
                variantsData.forEach(function (v, idx) {
                    const info = findAttrValue(v.attribute_value_id);
                    bodyHtml += '<tr>' +
                        '<td>' + (idx + 1) + '</td>' +
                        '<td>' + info.attrName + '</td>' +
                        '<td>' + info.valName + '</td>' +
                        '<td><div class="input-group input-group-sm"><span class="input-group-text">{{ currency_symbol() }}</span><input type="number" class="form-control form-control-sm variant-purchase" value="' + v.purchase_price + '" placeholder="0.00" step="0.01" min="0" data-index="' + idx + '" /></div></td>' +
                        '<td><div class="input-group input-group-sm"><span class="input-group-text">{{ currency_symbol() }}</span><input type="number" class="form-control form-control-sm variant-sale" value="' + v.sale_price + '" placeholder="0.00" step="0.01" min="0" data-index="' + idx + '" /></div></td>' +
                        '<td><button type="button" class="btn btn-sm btn-icon text-danger remove-variant" data-index="' + idx + '"><i class="ti ti-trash"></i></button></td>' +
                        '</tr>';
                });
                $('#variantsBody').html(bodyHtml);
                $('#variantsJson').val(JSON.stringify(variantsData));
            }

            function generateVariants() {
                const $checked = $('.attribute-select:checked');
                if ($checked.length === 0) {
                    variantsData = [];
                    renderVariantsTable();
                    return;
                }

                const existingMapCopy = {};
                variantsData.forEach(function (v) { existingMapCopy[v.attribute_value_id] = v; });

                const defaultPurchase = parseFloat($('input[name="purchase_price"]').val()) || 0;
                const defaultSale = parseFloat($('input[name="sale_price"]').val()) || 0;

                const newData = [];
                $checked.each(function () {
                    const attrId = parseInt($(this).data('attribute-id'));
                    const attr = allAttributes.find(function (a) { return a.id === attrId; });
                    if (attr) {
                        attr.values.forEach(function (val) {
                            if (removedValueIds[val.id]) return;
                            const existing = existingMapCopy[val.id] || existingMap[val.id];
                            if (existing) {
                                newData.push({ ...existing, attribute_value_id: val.id });
                            } else {
                                newData.push({
                                    attribute_value_id: val.id,
                                    purchase_price: defaultPurchase,
                                    sale_price: defaultSale,
                                    status: 1
                                });
                            }
                        });
                    }
                });
                variantsData = newData;
                renderVariantsTable();
            }

            $(document).on('input', '.variant-purchase', function () {
                const idx = $(this).data('index');
                if (variantsData[idx]) {
                    variantsData[idx].purchase_price = parseFloat($(this).val()) || 0;
                    $('#variantsJson').val(JSON.stringify(variantsData));
                }
            });

            $(document).on('input', '.variant-sale', function () {
                const idx = $(this).data('index');
                if (variantsData[idx]) {
                    variantsData[idx].sale_price = parseFloat($(this).val()) || 0;
                    $('#variantsJson').val(JSON.stringify(variantsData));
                }
            });

            $(document).on('click', '.remove-variant', function () {
                const idx = $(this).data('index');
                const removed = variantsData.splice(idx, 1);
                if (removed[0]) removedValueIds[removed[0].attribute_value_id] = true;
                renderVariantsTable();
            });

            $(document).on('change', '.attribute-select', function () {
                $(this).closest('.attribute-chip').toggleClass('active', this.checked);
                if (!this.checked) {
                    const attrId = parseInt($(this).data('attribute-id'));
                    const attr = allAttributes.find(function(a) { return a.id === attrId; });
                    if (attr) {
                        attr.values.forEach(function(val) { 
                            delete removedValueIds[val.id]; 
                            if (typeof existingMap !== 'undefined' && existingMap[val.id]) {
                                delete existingMap[val.id];
                            }
                        });
                    }
                }
                generateVariants();
            });

            function roundToNearest5(val) {
                return Math.ceil(parseFloat(val) / 5) * 5;
            }

            function getMultipliers() {
                return {
                    purchase: parseFloat($('#purchaseMultiplierInput').val()) || 0,
                    sale: parseFloat($('#saleMultiplierInput').val()) || 0,
                    mrp: parseFloat($('#mrpMultiplierInput').val()) || 0,
                };
            }

            $('#productCodeInput, #purchaseMultiplierInput, #saleMultiplierInput, #mrpMultiplierInput').on('input change', function () {
                const code = parseFloat($('#productCodeInput').val()) || 0;
                const mult = getMultipliers();
                const purchasePrice = (code * mult.purchase).toFixed(2);
                const isPair = $('#productPair').is(':checked');
                const mode = currentPairMode();

                if (isPair && mode === 'custom_size') {
                    $('#purchasePriceInput').val(purchasePrice).trigger('change');

                    const firstSalePrice = roundToNearest5(code * mult.sale).toFixed(2);
                    const firstMrp = roundToNearest5(code * mult.mrp).toFixed(2);
                    const $saleFirst = $('#customSizeRows .custom-size-sale-field[data-index="0"] .custom-size-sale-price');
                    const $mrpFirst = $('#customSizeRows .custom-size-mrp-field[data-index="0"] .custom-size-mrp');
                    if ($saleFirst.length) {
                        $saleFirst.val(firstSalePrice).trigger('input');
                    }
                    if ($mrpFirst.length) {
                        $mrpFirst.val(firstMrp).trigger('input');
                    }
                } else if (isPair) {
                    const pairSalePrice = roundToNearest5(code * mult.sale).toFixed(2);
                    const pairMrp = roundToNearest5(code * mult.mrp).toFixed(2);
                    const singleSalePrice = roundToNearest5((code / 2) * mult.sale).toFixed(2);
                    const singleMrp = roundToNearest5((code / 2) * mult.mrp).toFixed(2);

                    $('#purchasePriceInput').val(purchasePrice).trigger('change');
                    $('#salePriceInput').val(singleSalePrice).trigger('change');
                    $('#mrpInput').val(singleMrp);
                    $('#pairSalePriceInput').val(pairSalePrice);
                    $('#pairMrpInput').val(pairMrp);
                } else {
                    const salePrice = roundToNearest5(code * mult.sale).toFixed(2);
                    const mrp = roundToNearest5(code * mult.mrp).toFixed(2);

                    $('#purchasePriceInput').val(purchasePrice).trigger('change');
                    $('#salePriceInput').val(salePrice).trigger('change');
                    $('#mrpInput').val(mrp);
                }
            });

            $('#salePriceInput').on('change', function () {
                const val = parseFloat($(this).val()) || 0;
                if (val > 0) {
                    const rounded = roundToNearest5(val).toFixed(2);
                    $(this).val(rounded);
                }
            });

            // Pair Product toggle
            function updatePairPricingLabels(isPair) {
                $('#salePriceLabel').html(isPair ? 'Sale Price (Piece) <span class="text-danger">*</span>' : 'Sale Price <span class="text-danger">*</span>');
                $('#mrpLabel').html(isPair ? 'MRP (Piece) <span class="text-danger">*</span>' : 'MRP <span class="text-danger">*</span>');
            }

            function currentPairMode() {
                return $('#pairModeInput').val() || 'pieces_pair';
            }

            $(document).on('click', '.pair-mode-btn', function () {
                const $toggle = $(this).closest('.pair-mode-toggle');
                $toggle.find('.pair-mode-btn').removeClass('active');
                $(this).addClass('active');
                $('#pairModeInput').val($(this).data('value')).trigger('change');
            });

            function updatePairModeUI() {
                const isPair = $('#productPair').is(':checked');
                const mode = currentPairMode();
                const hideBasePrice = isPair && mode === 'custom_size';
                $('.pair-mode-pieces-field').toggleClass('d-none', !(isPair && mode !== 'custom_size'));
                $('.pair-mode-custom-field').toggleClass('d-none', !hideBasePrice);
                $('.base-price-field').toggleClass('d-none', hideBasePrice);

                if (hideBasePrice) {
                    $('#productTypeCol').insertAfter('#purchasePriceCol');
                } else {
                    $('#productTypeCol').insertAfter('#customSizeSection');
                }
            }

            $('#productPair').on('change', function () {
                const isPair = $(this).is(':checked');
                updatePairPricingLabels(isPair);
                if (isPair) {
                    $('.pair-pricing-field').removeClass('d-none');
                } else {
                    $('.pair-pricing-field').addClass('d-none');
                    $('#pairSalePriceInput').val('');
                    $('#pairMrpInput').val('');
                    customSizesList = [];
                    renderCustomSizeBadges();
                    $('#customSizeRows').empty();
                    $('#customSizesJson').val('');
                }
                updatePairModeUI();
                // Recalculate prices based on current code
                $('#productCodeInput').trigger('change');
            });

            const isPairChecked = $('#productPair').is(':checked');
            updatePairPricingLabels(isPairChecked);
            if (isPairChecked && (!$('#pairSalePriceInput').val() || !$('#pairMrpInput').val())) {
                $('#productCodeInput').trigger('change');
            }

            $('#pairModeInput').on('change', function () {
                updatePairModeUI();
                if (currentPairMode() === 'custom_size') {
                    $('#pairSalePriceInput').val('');
                    $('#pairMrpInput').val('');
                    renderCustomSizeRows(true);
                    $('#productCodeInput').trigger('change');
                } else {
                    customSizesList = [];
                    renderCustomSizeBadges();
                    $('#customSizeRows').empty();
                    $('#customSizesJson').val('');
                    // Restore the normal calculated piece price now that these fields are visible again
                    $('#productCodeInput').trigger('change');
                }
            });

            $('#salePriceInput').on('input', function () {
                const mult = getMultipliers();
                const ratio = mult.sale > 0 ? (mult.mrp / mult.sale) : 0;
                $('#mrpInput').val(roundToNearest5((parseFloat($(this).val()) || 0) * ratio).toFixed(2));
            });

            $('#pairSalePriceInput').on('input', function () {
                const mult = getMultipliers();
                const ratio = mult.sale > 0 ? (mult.mrp / mult.sale) : 0;
                $('#pairMrpInput').val(roundToNearest5((parseFloat($(this).val()) || 0) * ratio).toFixed(2));
            });

            // ---- Custom Size pair pricing ----
            const currencySymbol = "{{ currency_symbol() }}";

            let customSizesList = @json($product->custom_sizes ? collect($product->custom_sizes)->pluck('size')->map(fn($s) => (int) $s)->sort()->values() : []);

            function buildCustomSizeRowHtml(index, size) {
                return '<div class="col-md-6 custom-size-sale-field" data-index="' + index + '" data-size="' + size + '">' +
                        '<label class="form-label">Sale Price <span>(' + size + ' pcs)</span> <span class="text-danger">*</span></label>' +
                        '<div class="input-group has-validation">' +
                            '<span class="input-group-text">' + currencySymbol + '</span>' +
                            '<input type="number" class="form-control custom-size-sale-price" step="0.01" min="0" placeholder="0.00">' +
                            '<div class="invalid-feedback"></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-6 custom-size-mrp-field" data-index="' + index + '" data-size="' + size + '">' +
                        '<label class="form-label">MRP <span>(' + size + ' pcs)</span> <span class="text-danger">*</span></label>' +
                        '<div class="input-group has-validation">' +
                            '<span class="input-group-text">' + currencySymbol + '</span>' +
                            '<input type="number" class="form-control custom-size-mrp" step="0.01" min="0" placeholder="0.00">' +
                            '<div class="invalid-feedback"></div>' +
                        '</div>' +
                    '</div>';
            }

            function renderCustomSizeBadges() {
                const $container = $('#customSizeBadges');
                $container.empty();
                customSizesList.forEach(function (size) {
                    $container.append(
                        $('<span>', { class: 'badge bg-label-primary d-inline-flex align-items-center gap-1 custom-size-badge', 'data-size': size })
                            .append($('<span>').text(size + ' pcs'))
                            .append($('<i>', { class: 'ti ti-x remove-custom-size-badge', style: 'font-size:0.7rem;cursor:pointer;' }))
                    );
                });
            }

            function addCustomSize(rawVal) {
                if (typeof rawVal === 'string' && rawVal.includes('.')) {
                    return;
                }
                const size = parseInt(rawVal, 10);
                if (isNaN(size) || size <= 0 || size > 20) {
                    return;
                }
                if (!customSizesList.includes(size)) {
                    customSizesList.push(size);
                    customSizesList.sort(function (a, b) { return a - b; });
                }
                renderCustomSizeBadges();
                renderCustomSizeRows(false);
                $('#productCodeInput').trigger('change');
            }

            function removeCustomSize(size) {
                customSizesList = customSizesList.filter(function (s) { return s !== size; });
                renderCustomSizeBadges();
                renderCustomSizeRows(false);
                $('#productCodeInput').trigger('change');
            }

            $('#customSizesTagWrapper').on('click', function (e) {
                if (!$(e.target).is('input')) {
                    $('#customSizesInput').trigger('focus');
                }
            });

            $('#customSizesInput').on('input', function () {
                let val = $(this).val().replace(/[^0-9]/g, '');
                if (val !== '') {
                    let num = parseInt(val, 10);
                    if (num > 20) {
                        num = 20;
                    }
                    val = num > 0 ? num.toString() : '';
                }
                $(this).val(val);
            });

            $('#customSizesInput').on('keydown', function (e) {
                if (e.key === 'Backspace' && $(this).val() === '') {
                    if (customSizesList.length > 0) {
                        customSizesList.pop();
                        renderCustomSizeBadges();
                        renderCustomSizeRows(false);
                        $('#productCodeInput').trigger('change');
                    }
                } else if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    addCustomSize($(this).val());
                    $(this).val('');
                }
            });

            $('#customSizesInput').on('blur', function () {
                if ($(this).val().trim() !== '') {
                    addCustomSize($(this).val());
                    $(this).val('');
                }
            });

            $(document).on('click', '.remove-custom-size-badge', function () {
                const size = parseInt($(this).closest('.custom-size-badge').data('size'), 10);
                removeCustomSize(size);
            });

            function renderCustomSizeRows(preserveValues) {
                const sizes = customSizesList.slice().sort(function (a, b) { return a - b; });
                const $container = $('#customSizeRows');

                const existingValues = {};
                if (preserveValues) {
                    $container.find('.custom-size-sale-field').each(function () {
                        const size = parseFloat($(this).data('size'));
                        existingValues[size] = existingValues[size] || {};
                        existingValues[size].sale = $(this).find('.custom-size-sale-price').val();
                    });
                    $container.find('.custom-size-mrp-field').each(function () {
                        const size = parseFloat($(this).data('size'));
                        existingValues[size] = existingValues[size] || {};
                        existingValues[size].mrp = $(this).find('.custom-size-mrp').val();
                    });
                }

                $container.empty();
                sizes.forEach(function (size, index) {
                    $container.append(buildCustomSizeRowHtml(index, size));
                    if (preserveValues && existingValues[size]) {
                        $container.find('.custom-size-sale-field[data-index="' + index + '"] .custom-size-sale-price').val(existingValues[size].sale);
                        $container.find('.custom-size-mrp-field[data-index="' + index + '"] .custom-size-mrp').val(existingValues[size].mrp);
                    }
                });

                syncCustomSizesJson();
                cascadeFromFirstCustomSize();
            }

            function cascadeFromFirstCustomSize() {
                $('#customSizeRows .custom-size-sale-field').each(function () {
                    const $input = $(this).find('.custom-size-sale-price');
                    if (parseFloat($input.val()) > 0) {
                        $input.trigger('input');
                        return false;
                    }
                });
                $('#customSizeRows .custom-size-mrp-field').each(function () {
                    const $input = $(this).find('.custom-size-mrp');
                    if (parseFloat($input.val()) > 0) {
                        $input.trigger('input');
                        return false;
                    }
                });
            }

            function syncBasePriceFromCustomSizes() {
                if (currentPairMode() !== 'custom_size') {
                    return;
                }
                const $saleFirst = $('#customSizeRows .custom-size-sale-field[data-index="0"]');
                const $mrpFirst = $('#customSizeRows .custom-size-mrp-field[data-index="0"]');
                if ($saleFirst.length) {
                    $('#salePriceInput').val($saleFirst.find('.custom-size-sale-price').val() || '');
                }
                if ($mrpFirst.length) {
                    $('#mrpInput').val($mrpFirst.find('.custom-size-mrp').val() || '');
                }
            }

            function syncCustomSizesJson() {
                const rows = [];
                $('#customSizeRows .custom-size-sale-field').each(function () {
                    const index = $(this).data('index');
                    const size = parseFloat($(this).data('size'));
                    const rawSale = $(this).find('.custom-size-sale-price').val();
                    const rawMrp = $('#customSizeRows .custom-size-mrp-field[data-index="' + index + '"]').find('.custom-size-mrp').val();
                    const salePrice = (rawSale !== undefined && rawSale !== null && rawSale.trim() !== '') ? parseFloat(rawSale) : null;
                    const mrp = (rawMrp !== undefined && rawMrp !== null && rawMrp.trim() !== '') ? parseFloat(rawMrp) : null;
                    rows.push({ size: size, sale_price: salePrice, mrp: mrp });
                });
                $('#customSizesJson').val(JSON.stringify(rows));
                syncBasePriceFromCustomSizes();
            }

            $(document).on('input', '.custom-size-sale-price, .custom-size-mrp', function () {
                const $field = $(this).closest('[data-index]');
                const currentIndex = parseInt($field.data('index'));
                const currentSize = parseFloat($field.data('size')) || 0;
                const isSale = $(this).hasClass('custom-size-sale-price');
                const baseVal = parseFloat($(this).val()) || 0;
                const targetFieldClass = isSale ? '.custom-size-sale-field' : '.custom-size-mrp-field';
                const targetInputClass = isSale ? '.custom-size-sale-price' : '.custom-size-mrp';

                if (currentSize > 0 && baseVal > 0) {
                    $('#customSizeRows ' + targetFieldClass).each(function () {
                        if (parseInt($(this).data('index')) === currentIndex) {
                            return;
                        }
                        const size = parseFloat($(this).data('size'));
                        const scaled = roundToNearest5(baseVal * (size / currentSize)).toFixed(2);
                        $(this).find(targetInputClass).val(scaled);
                    });
                }

                syncCustomSizesJson();
            });

            updatePairModeUI();
            renderCustomSizeBadges();
            @if($product->pair_product && $productPairMode === 'custom_size')
                renderCustomSizeRows(false);
                @foreach(collect($product->custom_sizes ?? [])->values() as $i => $row)
                    $('#customSizeRows .custom-size-sale-field[data-index="{{ $i }}"] .custom-size-sale-price').val({{ (float) ($row['sale_price'] ?? 0) }});
                    $('#customSizeRows .custom-size-mrp-field[data-index="{{ $i }}"] .custom-size-mrp').val({{ (float) ($row['mrp'] ?? 0) }});
                @endforeach
                syncCustomSizesJson();
            @endif

            $(document).on('change', 'input[name="purchase_price"], input[name="sale_price"]', function () {
                if ($('#variableSection').is(':visible') && variantsData.length > 0) {
                    const defaultPurchase = parseFloat($('input[name="purchase_price"]').val()) || 0;
                    const defaultSale = parseFloat($('input[name="sale_price"]').val()) || 0;
                    variantsData.forEach(function (v) {
                        v.purchase_price = defaultPurchase;
                        v.sale_price = defaultSale;
                    });
                    renderVariantsTable();
                }
            });

            $('#productType').on('change', function () {
                if ($(this).val() === 'variable') {
                    $('#variableSection').removeClass('d-none');
                } else {
                    $('#variableSection').addClass('d-none');
                }
            });

            if ($('#productType').val() === 'variable') {
                $('#variableSection').removeClass('d-none');
                generateVariants();
            }



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
                    '.mrp-line{font-size:8.5pt !important;font-weight:700 !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                    '.category-line{font-size:5.5pt !important;font-weight:normal !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                    '.variations-line{font-size:5.5pt !important;font-weight:normal !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                    '.code-line{font-size:5.5pt !important;font-weight:normal !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                    '.barcode-img{width:22mm !important;height:3.5mm !important;object-fit:fill !important;margin:0 !important;display:block;}' +
                    '</style>' +
                    '<script>window.onload=function(){setTimeout(function(){window.print();window.onafterprint=function(){window.close();};setTimeout(function(){window.close();},500);},500);};<\/script>' +
                    '</head><body>' + body + '</body></html>';
            };

            window.viewBarcode = function(barcodeText, productId, category, variations, salePrice) {
                const barcodeUrl = '{{ route('admin.products.barcode', ':id') }}'.replace(':id', productId);
                const customSizes = @json(($product->pair_product && $product->pair_mode === 'custom_size') ? ($product->custom_sizes ?? []) : []);
                
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
                                            <p class="fw-bold mb-0 text-dark font-monospace fs-5">${barcodeText}</p>
                                        </div>
                                        
                                        ${customSizeSelectHtml}

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
                    let url = '{{ route("admin.products.print-barcodes") }}' + '?items[0][id]=' + productId + '&items[0][qty]=' + qty;
                    if ($('#printCustomSize').length > 0) {
                        url += '&items[0][selected_size]=' + encodeURIComponent($('#printCustomSize').val());
                    }
                    window.open(url, '_blank');
                });
                
                document.getElementById('barcodeModal').addEventListener('hidden.bs.modal', function () {
                    this.remove();
                });
            };

        });
    </script>
@endsection
