@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
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
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control"
                                    placeholder="Enter SKU" value="{{ $product->sku }}" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Barcode <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <input type="text" name="barcode" class="form-control" 
                                        placeholder="Enter Barcode" value="{{ $product->barcode ?? '' }}" />
                                    @if($product->barcode)
                                    <button type="button" onclick="viewBarcode('{{ $product->barcode }}', {{ $product->id }})" class="btn btn-icon btn-label-secondary" title="View Barcode">
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
                              <div class="col-md-6">
                                  <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                                  <div class="input-group has-validation">
                                      <span class="input-group-text">{{ currency_symbol() }}</span>
                                      <input type="number" name="purchase_price" id="purchasePriceInput" class="form-control"
                                          placeholder="Enter Purchase Price" step="0.01" min="0" value="{{ $product->purchase_price }}" />
                                      <div class="invalid-feedback"></div>
                                  </div>
                              </div>
                              <div class="col-md-6">
                                  <label class="form-label" id="salePriceLabel">Sale Price <span class="text-danger">*</span></label>
                                  <div class="input-group has-validation">
                                      <span class="input-group-text">{{ currency_symbol() }}</span>
                                      <input type="number" name="sale_price" id="salePriceInput" class="form-control"
                                          placeholder="Enter Sale Price" step="0.01" min="0" value="{{ $product->sale_price }}" />
                                      <div class="invalid-feedback"></div>
                                  </div>
                              </div>
                              <div class="col-md-6">
                                  <label class="form-label" id="mrpLabel">MRP <span class="text-danger">*</span></label>
                                  <div class="input-group has-validation">
                                      <span class="input-group-text">{{ currency_symbol() }}</span>
                                      <input type="number" name="mrp" id="mrpInput" class="form-control"
                                          placeholder="Enter MRP" step="0.01" min="0" value="{{ $product->mrp }}" readonly style="background-color: #f1f0f2;" />
                                      <div class="invalid-feedback"></div>
                                  </div>
                              </div>

                              {{-- Pair Product pricing rows (shown only when pair_product is enabled) --}}
                              <div id="pairPricingSection" class="{{ $product->pair_product ? '' : 'd-none' }}">
                                  <div class="row g-3">
                                      <div class="col-md-6">
                                          <label class="form-label">Sale Price (Pair) <span class="text-danger">*</span></label>
                                          <div class="input-group has-validation">
                                              <span class="input-group-text">{{ currency_symbol() }}</span>
                                              <input type="number" name="pair_sale_price" id="pairSalePriceInput" class="form-control"
                                                  placeholder="Enter Pair Sale Price" step="0.01" min="0" value="{{ $product->pair_sale_price }}" />
                                              <div class="invalid-feedback"></div>
                                          </div>
                                      </div>
                                      <div class="col-md-6">
                                          <label class="form-label">MRP (Pair) <span class="text-danger">*</span></label>
                                          <div class="input-group has-validation">
                                              <span class="input-group-text">{{ currency_symbol() }}</span>
                                              <input type="number" name="pair_mrp" id="pairMrpInput" class="form-control"
                                                  placeholder="MRP (Pair)" step="0.01" min="0" value="{{ $product->pair_mrp }}" readonly style="background-color: #f1f0f2;" />
                                              <div class="invalid-feedback"></div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                             <div class="col-md-6">
                                 <label class="form-label">Product Type <span class="text-danger">*</span></label>
                                 <select name="type" id="productType" class="form-select no-select2">
                                     <option value="normal" {{ $product->type === 'variable' ? '' : 'selected' }}>Normal Product</option>
                                     <option value="variable" {{ $product->type === 'variable' ? 'selected' : '' }}>Variable Product</option>
                                 </select>
                                <div class="invalid-feedback"></div>
                              </div>
                             <div id="variableSection" class="{{ $product->type === 'variable' ? '' : 'd-none' }}">
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
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
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
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="productPair" name="pair_product" value="1"
                                {{ $product->pair_product == 1 ? 'checked' : '' }} />
                            <label class="form-check-label" for="productPair">Pair Product</label>
                        </div>
                    </div>
                </div>

                <!-- Primary Image -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Primary Image <span class="text-danger">*</span></h5></div>
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
                    <div class="card-header"><h5 class="mb-0">Additional Images <span class="text-danger">*</span></h5></div>
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
                                if (field === 'primary_image_base64') {
                                    $('#primaryImageError').text(messages[0]);
                                    $('#primaryDropZone').css('border-color', '#ea5455');
                                } else if (field === 'additional_images_base64') {
                                    $('#additionalImagesError').text(messages[0]);
                                    $('#additionalDropZone').css('border-color', '#ea5455');
                                } else {
                                    form.find('[name="' + field + '"], [name="' + field + '[]"]')
                                        .addClass('is-invalid')
                                        .siblings('.invalid-feedback').text(messages[0]);
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

            function renderVariantsTable() {
                let headerHtml = '<th style="width:50px">#</th><th>Attribute</th><th>Value</th>';
                headerHtml += '<th style="width:200px">Purchase Price <span class="text-danger">*</span></th>';
                headerHtml += '<th style="width:200px">Sale Price <span class="text-danger">*</span></th>';
                headerHtml += '<th style="width:60px">Action</th>';
                $('#variantsHeader').html(headerHtml);

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
                // Only one attribute allowed at a time — uncheck all others
                if (this.checked) {
                    $('.attribute-select').not(this).each(function () {
                        this.checked = false;
                        $(this).closest('.attribute-chip').removeClass('active');
                    });
                }
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

            $('#productCodeInput').on('input change', function () {
                const code = parseFloat($(this).val()) || 0;
                const purchasePrice = (code * 2.5).toFixed(2);
                const isPair = $('#productPair').is(':checked');

                if (isPair) {
                    const pairSalePrice = (code * 4.125).toFixed(2);
                    const pairMrp = (pairSalePrice * 1.10).toFixed(2);
                    const singleSalePrice = (pairSalePrice / 2).toFixed(2);
                    const singleMrp = (singleSalePrice * 1.10).toFixed(2);

                    $('#purchasePriceInput').val(purchasePrice).trigger('change');
                    $('#salePriceInput').val(singleSalePrice).trigger('change');
                    $('#mrpInput').val(singleMrp);
                    $('#pairSalePriceInput').val(pairSalePrice);
                    $('#pairMrpInput').val(pairMrp);
                } else {
                    const salePrice = (code * 4.125).toFixed(2);
                    const mrp = (salePrice * 1.10).toFixed(2);

                    $('#purchasePriceInput').val(purchasePrice).trigger('change');
                    $('#salePriceInput').val(salePrice).trigger('change');
                    $('#mrpInput').val(mrp);
                }
            });

            // Pair Product toggle
            function updatePairPricingLabels(isPair) {
                $('#salePriceLabel').html(isPair ? 'Sale Price (Piece) <span class="text-danger">*</span>' : 'Sale Price <span class="text-danger">*</span>');
                $('#mrpLabel').html(isPair ? 'MRP (Piece) <span class="text-danger">*</span>' : 'MRP <span class="text-danger">*</span>');
            }

            $('#productPair').on('change', function () {
                const isPair = $(this).is(':checked');
                updatePairPricingLabels(isPair);
                if (isPair) {
                    $('#pairPricingSection').removeClass('d-none');
                } else {
                    $('#pairPricingSection').addClass('d-none');
                    $('#pairSalePriceInput').val('');
                    $('#pairMrpInput').val('');
                }
                // Recalculate prices based on current code
                $('#productCodeInput').trigger('change');
            });

            updatePairPricingLabels($('#productPair').is(':checked'));

            $('#salePriceInput').on('input', function () {
                $('#mrpInput').val(((parseFloat($(this).val()) || 0) * 1.10).toFixed(2));
            });

            $('#pairSalePriceInput').on('input', function () {
                $('#pairMrpInput').val(((parseFloat($(this).val()) || 0) * 1.10).toFixed(2));
            });

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

            $('#productForm').on('submit', function () {
                if ($('#productType').val() === 'variable') {
                    if ($('.attribute-select:checked').length === 0) {
                        $('#variantsJson').val('[]');
                    }
                }
            });

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

        });
    </script>
@endsection
