<div class="text-center mb-4">
    <h3 class="mb-2">Edit Category</h3>
    <p class="text-muted">Update category details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.categories.update', $category) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <!-- Name -->
        <div class="col-12">
            <label class="form-label" for="categoryName">Name <span class="text-danger">*</span></label>
            <input type="text" id="categoryName" name="name"
                class="form-control" placeholder="e.g. Electronics"
                value="{{ $category->name }}" autofocus />
            <div class="invalid-feedback"></div>
        </div>

        <!-- Drag and Drop Category Image -->
        <div class="col-12">
            <label class="form-label">Category Image <span class="text-danger">*</span></label>
            <input type="hidden" name="image_base64" id="croppedImageVal" />
            <input type="hidden" name="remove_image" id="removeImageVal" value="0" />
            <div id="imageDropZone" class="border border-2 rounded-3 p-4 text-center cursor-pointer position-relative" style="border-style: dashed !important; border-color: #cbd5e1 !important; background-color: #f8fafc; transition: all 0.2s ease; min-height: 150px; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden;">
                <input type="file" id="categoryImage" name="image" class="position-absolute" style="position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; opacity: 0 !important; cursor: pointer !important; z-index: 10 !important; margin: 0 !important; padding: 0 !important;" accept="image/*" />
                <div class="dz-message py-2" style="pointer-events: none; position: relative; z-index: 1;">
                    <i class="ti ti-cloud-upload text-muted mb-2" style="font-size: 2.5rem !important;"></i>
                    <p class="fw-semibold mb-1" style="font-size: 0.95rem; color: #4b4b4b;">Drag & drop your image here or click to browse</p>
                    <span class="text-muted small">Supports: JPG, JPEG, PNG, WEBP (Max 50MB)</span>
                </div>
            </div>
            <div class="invalid-feedback"></div>
            
            <div class="mt-3 {{ $category->image ? '' : 'd-none' }}" id="croppedPreviewContainer">
                <div class="position-relative d-inline-block">
                    <img id="croppedPreview" src="{{ $category->image_url }}" class="img-thumbnail object-fit-cover" style="width: 150px; height: 150px; border-radius: 8px;" />
                    <button type="button" id="removeImageBtn" class="btn btn-danger btn-icon btn-sm position-absolute top-0 end-0 m-1 rounded-circle" style="padding: 0; width: 24px; height: 24px; min-width: 24px;" title="Remove Image">
                        <i class="ti ti-x" style="font-size: 0.8rem;"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Featured & Status (One line show) -->
        <div class="col-12 mt-2">
            <div class="d-flex align-items-center gap-5">
                <div class="flex-fill">
                    <label class="form-label" for="categoryFeatured">Featured Category</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" id="categoryFeatured" name="is_featured" value="1"
                            {{ $category->is_featured ? 'checked' : '' }} />
                        <label class="form-check-label" for="categoryFeatured">Featured</label>
                    </div>
                </div>
                <div class="flex-fill">
                    <label class="form-label" for="categoryStatus">Status</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" id="categoryStatus" name="status" value="active"
                            {{ $category->status === 'active' ? 'checked' : '' }} />
                        <label class="form-check-label" for="categoryStatus">Active</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Category</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<!-- Crop Image Modal -->
<div class="modal fade" id="cropImageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Image</h5>
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

<style>
    #croppedImageVal.is-invalid ~ #imageDropZone {
        border-color: #ea5455 !important;
    }
    #croppedImageVal.is-invalid ~ .invalid-feedback {
        display: block !important;
    }
</style>

<script>
$(document).ready(function () {
    const $dropZone = $('#imageDropZone');
    const $fileInput = $('#categoryImage');
    const $base64Input = $('#croppedImageVal');
    const $previewImg = $('#croppedPreview');
    const $previewContainer = $('#croppedPreviewContainer');
    const $cropModal = $('#cropImageModal');
    const $cropImage = $('#imageToCrop');
    const $cropSaveBtn = $('#cropSaveBtn');
    let cropper = null;

    // Move modal to body to avoid offcanvas constraint issues
    if ($cropModal.length && !$cropModal.parent().is('body')) {
        $cropModal.appendTo('body');
    }

    // Dropzone hover effects
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

    $fileInput.on('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Size check: 50MB limit
        if (file.size > 50 * 1024 * 1024) {
            toastr.error('The image size must be less than 50 MB.');
            $base64Input.addClass('is-invalid');
            $base64Input.siblings('.invalid-feedback').text('The image size must be less than 50 MB.');
            this.value = '';
            $base64Input.val('');
            @if(!$category->image)
                $previewContainer.addClass('d-none');
            @endif
            return;
        }

        $base64Input.removeClass('is-invalid');
        $base64Input.siblings('.invalid-feedback').text('');

        const reader = new FileReader();
        reader.onload = function (e) {
            $cropImage.attr('src', e.target.result);
            
            const img = new Image();
            img.src = e.target.result;
            img.onload = function () {
                const maxHeight = Math.max(300, Math.min(500, window.innerHeight - 220));
                
                // Apply dynamic max-height to fit viewport and prevent scrolling
                $cropImage.css('max-height', maxHeight + 'px');
                $cropModal.find('.img-container').css('max-height', maxHeight + 'px');
                
                let modalWidth = img.width;
                if (img.height > maxHeight) {
                    modalWidth = (img.width * maxHeight) / img.height;
                }
                
                const minWidth = 350;
                const maxWidth = window.innerWidth * 0.9;
                modalWidth = Math.max(minWidth, Math.min(modalWidth, maxWidth));
                
                $cropModal.find('.modal-dialog').css({
                    'max-width': modalWidth + 'px',
                    'width': '100%'
                });
                
                // Show the modal
                const modalObj = new bootstrap.Modal($cropModal[0]);
                modalObj.show();
            };
        };
        reader.readAsDataURL(file);
    });

    $cropModal.on('shown.bs.modal', function () {
        cropper = new Cropper($cropImage[0], {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 0.8,
            background: false,
            responsive: true,
        });
    }).on('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        $cropImage.attr('src', '');
    });

    $cropSaveBtn.on('click', function () {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({
            width: 400,
            height: 400
        });
        if (canvas) {
            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            $base64Input.val(dataUrl);
            $previewImg.attr('src', dataUrl);
            $previewContainer.removeClass('d-none');
            $('#removeImageVal').val('0'); // Reset removal status
            
            // Hide modal
            bootstrap.Modal.getInstance($cropModal[0]).hide();
        }
    });

    // Remove selected image
    $previewContainer.on('click', '#removeImageBtn', function () {
        $fileInput.val('');
        $base64Input.val('');
        $previewImg.attr('src', '');
        $previewContainer.addClass('d-none');
        $('#removeImageVal').val('1'); // Mark for removal on backend
    });

    // Cleanup when offcanvas is hidden to remove the modal from the body
    $('#commonModal').one('hidden.bs.offcanvas', function () {
        $cropModal.remove();
    });
});
</script>
