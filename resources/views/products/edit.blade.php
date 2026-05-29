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
                                    placeholder="e.g. iPhone 15 Pro" value="{{ $product->name }}" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control"
                                    placeholder="e.g. IPH-15-PRO" value="{{ $product->sku }}" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ $product->category_id === $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">{{ currency_symbol() }}</span>
                                    <input type="number" name="purchase_price" class="form-control"
                                        placeholder="0.00" step="0.01" min="0" value="{{ $product->purchase_price }}" />
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sale Price <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">{{ currency_symbol() }}</span>
                                    <input type="number" name="sale_price" class="form-control"
                                        placeholder="0.00" step="0.01" min="0" value="{{ $product->sale_price }}" />
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description <span class="text-muted">(optional)</span></label>
                                <div id="description-editor">{!! $product->description !!}</div>
                                <textarea name="description" id="description-textarea" class="d-none">{{ $product->description }}</textarea>
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
                            <input class="form-check-input" type="checkbox" id="productStatus" name="status" value="active"
                                {{ $product->status === 'active' ? 'checked' : '' }} />
                            <label class="form-check-label" for="productStatus">Active</label>
                        </div>
                    </div>
                </div>

                <!-- Primary Image -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Primary Image</h5></div>
                    <div class="card-body">
                        @php $primaryImage = $product->images->firstWhere('is_primary', true); @endphp
                        @if($primaryImage)
                            <div class="mb-3">
                                <p class="text-muted small mb-1">Current primary image:</p>
                                <img src="{{ asset('storage/' . $primaryImage->image_path) }}"
                                    width="100" height="100" class="rounded object-fit-cover border border-primary border-2" />
                            </div>
                        @endif
                        <p class="text-muted small mb-2">Upload to replace current primary image. Max 5MB. (jpg, jpeg, png, webp)</p>
                        <input type="file" name="primary_image" id="primaryImageInput" class="form-control" accept="image/*" />
                        <div class="invalid-feedback"></div>
                        <div id="primaryImagePreview" class="mt-3 d-none">
                            <img id="primaryImageThumb" src="" width="100" height="100" class="rounded object-fit-cover border border-primary border-2" />
                        </div>
                    </div>
                </div>

                <!-- Additional Images -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Additional Images</h5></div>
                    <div class="card-body">
                        @php $additionalImages = $product->images->where('is_primary', false); @endphp
                        @if($additionalImages->count())
                            <div class="d-flex flex-wrap gap-2 mb-3" id="existingImages">
                                @foreach($additionalImages as $image)
                                    <div class="position-relative" id="img-{{ $image->id }}">
                                        <img src="{{ asset('storage/' . $image->image_path) }}"
                                            width="70" height="70" class="rounded object-fit-cover border" />
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger position-absolute top-0 end-0 btn-delete-image"
                                            data-url="{{ route('admin.products.images.destroy', $image) }}"
                                            data-id="{{ $image->id }}">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <p class="text-muted small mb-2">Add more images. Max 5MB each. (jpg, jpeg, png, webp)</p>
                        <input type="file" name="images[]" id="additionalImages" class="form-control" multiple accept="image/*" />
                        <div class="invalid-feedback"></div>
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
@endsection

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/typography.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/katex.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/quill/katex.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
    <script>
        $(document).ready(function () {

            // Initialize Quill Editor
            const quill = new Quill('#description-editor', {
                theme: 'snow',
                placeholder: 'Enter product description...'
            });
            
            quill.on('text-change', function() {
                $('#description-textarea').val(quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML).trigger('input');
            });

            // Primary image preview
            $('#primaryImageInput').on('change', function () {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#primaryImageThumb').attr('src', e.target.result);
                    $('#primaryImagePreview').removeClass('d-none');
                };
                reader.readAsDataURL(file);
            });

            // Additional images preview
            $('#additionalImages').on('change', function () {
                $('#additionalPreview').empty();
                $.each(this.files, function (i, file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        $('#additionalPreview').append(
                            '<img src="' + e.target.result + '" width="70" height="70" class="rounded object-fit-cover border">'
                        );
                    };
                    reader.readAsDataURL(file);
                });
            });

            // Submit via AJAX with FormData
            $('#productForm').on('submit', function (e) {
                e.preventDefault();

                const form     = $(this);
                const formData = new FormData(this);

                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
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
                        $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Update Product');
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON?.message || {};
                            $.each(errors, function (field, messages) {
                                form.find('[name="' + field + '"], [name="' + field + '[]"]')
                                    .addClass('is-invalid')
                                    .siblings('.invalid-feedback').text(messages[0]);
                            });
                        } else {
                            toastr.error('Something went wrong. Please try again.');
                        }
                    }
                });
            });

            // Delete additional image
            $(document).on('click', '.btn-delete-image', function () {
                const btn = $(this);
                const url = btn.data('url');
                const id  = btn.data('id');

                if (!confirm('Delete this image?')) return;

                $.ajax({
                    url  : url,
                    type : 'DELETE',
                    data : { _token: $('meta[name="csrf-token"]').attr('content') },
                    success : function (res) {
                        if (res.status === 'success') {
                            $('#img-' + id).remove();
                            toastr.success(res.message);
                        }
                    },
                    error : function () { toastr.error('Something went wrong.'); }
                });
            });

        });
    </script>
@endsection
