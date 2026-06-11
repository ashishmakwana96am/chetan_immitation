@extends('layouts.app')

@section('title', 'Add Product')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Add Product</h4>
        <a href="{{ route('admin.products.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>
    </div>

    <form id="productForm" action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">

            <!-- Main Details -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Product Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. iPhone 15 Pro" value="{{ isset($clonedProduct) ? $clonedProduct->name . ' - Copy' : '' }}" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control" placeholder="e.g. IPH-15-PRO" value="{{ isset($clonedProduct) ? $clonedProduct->sku . '-copy' : '' }}" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="productCategory" class="form-select">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ isset($clonedProduct) && $clonedProduct->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sub Category</label>
                                <select name="sub_category_id" id="productSubCategory" class="form-select" {{ isset($clonedProduct) && $subCategories->count() > 0 ? '' : 'disabled' }}>
                                    <option value="">-- Select Sub Category --</option>
                                    @if(isset($clonedProduct) && $subCategories->count() > 0)
                                        @foreach($subCategories as $subCategory)
                                            <option value="{{ $subCategory->id }}" {{ $clonedProduct->sub_category_id == $subCategory->id ? 'selected' : '' }}>{{ $subCategory->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">{{ currency_symbol() }}</span>
                                    <input type="number" name="purchase_price" class="form-control" placeholder="0.00" step="0.01" min="0" value="{{ isset($clonedProduct) ? $clonedProduct->purchase_price : '' }}" />
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sale Price <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">{{ currency_symbol() }}</span>
                                    <input type="number" name="sale_price" class="form-control" placeholder="0.00" step="0.01" min="0" value="{{ isset($clonedProduct) ? $clonedProduct->sale_price : '' }}" />
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Product Type <span class="text-danger">*</span></label>
                                <select name="type" id="productType" class="form-select no-select2">
                                    <option value="normal" {{ isset($clonedProduct) && $clonedProduct->type === 'variable' ? '' : 'selected' }}>Normal Product</option>
                                    <option value="variable" {{ isset($clonedProduct) && $clonedProduct->type === 'variable' ? 'selected' : '' }}>Variable Product</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div id="variableSection" class="d-none">
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
                                                <label class="attribute-chip btn btn-sm d-inline-flex align-items-center gap-2 px-3 py-2 cursor-pointer mb-0" for="attr_{{ $attr->id }}" style="transition:all .2s;user-select:none;">
                                                    <input class="form-check-input attribute-select m-0" type="checkbox" data-attribute-id="{{ $attr->id }}" id="attr_{{ $attr->id }}" style="cursor:pointer;" />
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
                                <div id="description-editor">{!! isset($clonedProduct) ? $clonedProduct->description : old('description') !!}</div>
                                <textarea name="description" id="description-textarea" class="d-none">{{ isset($clonedProduct) ? $clonedProduct->description : '' }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Additional Information <span class="text-danger">*</span></label>
                                <div id="information-editor">{!! isset($clonedProduct) ? $clonedProduct->additional_information : old('additional_information') !!}</div>
                                <textarea name="additional_information" id="information-textarea" class="d-none">{{ isset($clonedProduct) ? $clonedProduct->additional_information : '' }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">

                <!-- Status -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Status</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="productStatus" name="status" value="active" {{ !isset($clonedProduct) || $clonedProduct->status === 'active' ? 'checked' : '' }} />
                            <label class="form-check-label" for="productStatus">Active</label>
                        </div>
                    </div>
                </div>

                <!-- Primary Image -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Primary Image <span class="text-danger">*</span></h5></div>
                    <div class="card-body">
                        <input type="hidden" name="primary_image_base64" id="primaryImageBase64" />
                        <input type="hidden" name="cloned_from_id" value="{{ isset($clonedProduct) ? $clonedProduct->id : '' }}" />
                        <input type="hidden" name="remove_cloned_primary" id="removeClonedPrimary" value="0" />
                        <div id="primaryDropZone" class="border border-2 rounded-3 p-4 text-center cursor-pointer position-relative" style="border-style: dashed !important; border-color: #cbd5e1 !important; background-color: #f8fafc; transition: all 0.2s ease; min-height: 150px; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden;">
                            <input type="file" id="primaryImageInput" class="position-absolute" style="position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; opacity: 0 !important; cursor: pointer !important; z-index: 10 !important; margin: 0 !important; padding: 0 !important;" accept="image/*" />
                            <div class="dz-message py-2" style="pointer-events: none; position: relative; z-index: 1;">
                                <i class="ti ti-cloud-upload text-muted mb-2" style="font-size: 2.5rem !important;"></i>
                                <p class="fw-semibold mb-1" style="font-size: 0.95rem; color: #4b4b4b;">Drag & drop your image here or click to browse</p>
                                <span class="text-muted small">Supports: JPG, JPEG, PNG, WEBP (Max 50MB)</span>
                            </div>
                        </div>
                        <div class="invalid-feedback d-block text-danger mt-1" id="primaryImageError"></div>
                        @php
                            $clonedPrimary = isset($clonedProduct) ? $clonedProduct->images->firstWhere('is_primary', true) : null;
                        @endphp
                        <div id="primaryImagePreview" class="mt-3 {{ $clonedPrimary ? '' : 'd-none' }}">
                            <div class="position-relative d-inline-block">
                                <img id="primaryImageThumb" src="{{ $clonedPrimary ? $clonedPrimary->image_url : '' }}" width="120" height="120" class="rounded object-fit-cover border border-primary border-2" />
                                <button type="button" id="removePrimaryImageBtn" class="btn btn-danger btn-icon btn-sm position-absolute top-0 end-0 m-1 rounded-circle" style="padding: 0; width: 24px; height: 24px; min-width: 24px;" title="Remove Image">
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
                        <div id="additionalDropZone" class="border border-2 rounded-3 p-4 text-center cursor-pointer position-relative" style="border-style: dashed !important; border-color: #cbd5e1 !important; background-color: #f8fafc; transition: all 0.2s ease; min-height: 150px; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden;">
                            <input type="file" id="additionalImagesInput" class="position-absolute" style="position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; opacity: 0 !important; cursor: pointer !important; z-index: 10 !important; margin: 0 !important; padding: 0 !important;" multiple accept="image/*" />
                            <div class="dz-message py-2" style="pointer-events: none; position: relative; z-index: 1;">
                                <i class="ti ti-cloud-upload text-muted mb-2" style="font-size: 2.5rem !important;"></i>
                                <p class="fw-semibold mb-1" style="font-size: 0.95rem; color: #4b4b4b;">Drag & drop your image here or click to browse</p>
                                <span class="text-muted small">Supports: JPG, JPEG, PNG, WEBP (Max 50MB)</span>
                            </div>
                        </div>
                        <div class="invalid-feedback d-block text-danger mt-1" id="additionalImagesError"></div>
                        <div id="additionalPreview" class="d-flex flex-wrap gap-2 mt-3">
                            @if(isset($clonedProduct))
                                @foreach($clonedProduct->images->where('is_primary', false) as $img)
                                    @php $uniqueId = 'cloned_' . $img->id; @endphp
                                    <div class="position-relative additional-img-card" id="added-img-{{ $uniqueId }}">
                                        <img src="{{ $img->image_url }}" width="80" height="80" class="rounded object-fit-cover border" />
                                        <input type="hidden" name="existing_cloned_images[]" value="{{ $img->id }}" />
                                        <button type="button" class="btn btn-danger btn-icon btn-sm position-absolute top-0 end-0 m-1 rounded-circle remove-additional-img-btn" data-id="{{ $uniqueId }}" style="padding: 0; width: 20px; height: 20px; min-width: 20px;" title="Remove">
                                            <i class="ti ti-x" style="font-size: 0.7rem;"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ti ti-device-floppy me-1"></i> Save Product
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
        .attribute-chip.active .form-check-input { background-color:#fff; border-color:#fff; }
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

            // Dynamic Sub Categories load
            $('#productCategory').on('change', function () {
                const categoryId = $(this).val();
                const subCategorySelect = $('#productSubCategory');

                subCategorySelect.empty().append('<option value="">-- Select Sub Category --</option>');

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
                        const modalObj = new bootstrap.Modal($cropModal[0]);
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

                // If user closed/cancelled, abort the remaining queue
                if (!cropSaved && currentCroppingField === 'additional') {
                    additionalFilesQueue = [];
                }
            });

            $cropSaveBtn.on('click', function () {
                if (!cropper) return;
                const canvas = cropper.getCroppedCanvas({
                    width: 500,
                    height: 500
                });
                if (canvas) {
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                    cropSaved = true;

                    if (currentCroppingField === 'primary') {
                        $('#primaryImageBase64').val(dataUrl);
                        $('#primaryImageThumb').attr('src', dataUrl);
                        $('#primaryImagePreview').removeClass('d-none');
                        $('#primaryImageInput').removeClass('is-invalid');
                        $('#primaryImageError').text('');
                        $('#removeClonedPrimary').val('0');
                        
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

            // Primary image remove button click handler
            $('#removePrimaryImageBtn').on('click', function () {
                $('#primaryImageInput').val('');
                $('#primaryImageBase64').val('');
                $('#primaryImageThumb').attr('src', '');
                $('#primaryImagePreview').addClass('d-none');
                $('#removeClonedPrimary').val('1');
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
                    type        : 'POST',
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
                        $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Product');
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

            // ====== Variable Product Logic ======
            const allAttributes = @json($attributes);
            let variantsData = [];
            const removedValueIds = {};

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

                const existingMap = {};
                variantsData.forEach(function (v) { existingMap[v.attribute_value_id] = v; });

                const defaultPurchase = parseFloat($('input[name="purchase_price"]').val()) || 0;
                const defaultSale = parseFloat($('input[name="sale_price"]').val()) || 0;

                const newData = [];
                $checked.each(function () {
                    const attrId = parseInt($(this).data('attribute-id'));
                    const attr = allAttributes.find(function (a) { return a.id === attrId; });
                    if (attr) {
                        attr.values.forEach(function (val) {
                            if (removedValueIds[val.id]) return;
                            const existing = existingMap[val.id];
                            if (existing) {
                                newData.push({ ...existing });
                            } else {
                                newData.push({
                                    attribute_value_id: val.id,
                                    purchase_price: defaultPurchase,
                                    sale_price: defaultSale,
                                    status: 'active'
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
                        attr.values.forEach(function(val) { delete removedValueIds[val.id]; });
                    }
                }
                generateVariants();
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

        });
    </script>
@endsection
