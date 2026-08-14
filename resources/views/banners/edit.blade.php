<div class="text-center mb-4">
    <h3 class="mb-2">Edit Banner</h3>
    <p class="text-muted">Update banner image or status</p>
</div>

<form id="commonModalForm" action="{{ route('admin.banners.update', $banner) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <!-- Drag and Drop Banner Image -->
        <div class="col-12">
            <label class="form-label">Banner Image (1920 x 750 px) <span class="text-danger">*</span></label>
            <input type="hidden" name="image_base64" id="croppedImageVal" />
            <input type="hidden" name="remove_image" id="removeImageVal" value="0" />

            <div id="imageDropZone" class="border border-2 rounded-3 p-4 text-center cursor-pointer position-relative" style="border-style: dashed !important; border-color: #cbd5e1 !important; background-color: #f8fafc; transition: all 0.2s ease; min-height: 140px; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden;">
                <input type="file" id="bannerImage" name="image" class="position-absolute" style="position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; opacity: 0 !important; cursor: pointer !important; z-index: 10 !important; margin: 0 !important; padding: 0 !important;" accept="image/*" />
                <div class="dz-message py-2" style="pointer-events: none; position: relative; z-index: 1;">
                    <i class="ti ti-cloud-upload text-muted mb-2" style="font-size: 2.5rem !important;"></i>
                    <p class="fw-semibold mb-1" style="font-size: 0.95rem; color: #4b4b4b;">Drag & drop new image here or click to browse</p>
                    <span class="text-muted small">Supports: JPG, JPEG, PNG, WEBP (Required aspect ratio: 1920:750)</span>
                </div>
            </div>
            <div class="invalid-feedback"></div>
            
            <div class="mt-3 {{ $banner->image ? '' : 'd-none' }}" id="croppedPreviewContainer">
                <div class="position-relative d-inline-block w-100 text-center">
                    <img id="croppedPreview" src="{{ $banner->image_url }}" class="img-thumbnail object-fit-cover w-100" style="max-height: 220px; border-radius: 8px;" />
                    <button type="button" id="removeImageBtn" class="btn btn-danger btn-icon btn-sm position-absolute top-0 end-0 m-2 rounded-circle" style="padding: 0; width: 28px; height: 28px; min-width: 28px;" title="Remove Image">
                        <i class="ti ti-x" style="font-size: 0.9rem;"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="col-12 mt-2">
            <label class="form-label" for="bannerStatus">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="bannerStatus" name="status" value="1" {{ $banner->status == 1 ? 'checked' : '' }} />
                <label class="form-check-label" for="bannerStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-4 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Banner</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<!-- Crop Image Modal -->
<div class="modal fade" id="cropImageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Banner Image (1920 x 750)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="img-container" style="max-height: 500px; overflow: hidden; background: #1e1e1e; display: flex; justify-content: center; align-items: center;">
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
    const $fileInput = $('#bannerImage');
    const $base64Input = $('#croppedImageVal');
    const $removeInput = $('#removeImageVal');
    const $previewImg = $('#croppedPreview');
    const $previewContainer = $('#croppedPreviewContainer');
    const $cropModal = $('#cropImageModal');
    const $cropImage = $('#imageToCrop');
    const $cropSaveBtn = $('#cropSaveBtn');
    let cropper = null;

    if ($cropModal.length && !$cropModal.parent().is('body')) {
        $cropModal.appendTo('body');
    }

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

        if (file.size > 50 * 1024 * 1024) {
            toastr.error('The image size must be less than 50 MB.');
            $base64Input.addClass('is-invalid');
            $base64Input.siblings('.invalid-feedback').text('The image size must be less than 50 MB.');
            this.value = '';
            $base64Input.val('');
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

                const isShown = $cropModal.hasClass('show');
                if (!isShown) {
                    let modalObj = bootstrap.Modal.getInstance($cropModal[0]);
                    if (!modalObj) {
                        modalObj = new bootstrap.Modal($cropModal[0]);
                    }
                    modalObj.show();
                } else {
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }
                    cropper = new Cropper($cropImage[0], {
                        aspectRatio: 1920 / 750,
                        viewMode: 1,
                        autoCropArea: 0.8,
                        background: false,
                        responsive: true,
                    });
                }
            };
        };
        reader.readAsDataURL(file);
    });

    $cropModal.on('shown.bs.modal', function () {
        if (!cropper) {
            cropper = new Cropper($cropImage[0], {
                aspectRatio: 1920 / 750,
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
        $fileInput.val('');
    });

    $cropSaveBtn.on('click', function () {
        if (!cropper) return;
        
        let canvasOptions = {
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        };

        const imageData = cropper.getImageData();
        const cropData = cropper.getCropBoxData();
        const scale = imageData.naturalWidth / (imageData.width || 1);
        const actualCropWidth = (cropData.width || 0) * scale;

        if (actualCropWidth >= 1920) {
            canvasOptions.width = 1920;
            canvasOptions.height = 750;
        }

        const canvas = cropper.getCroppedCanvas(canvasOptions);
        if (canvas) {
            const dataUrl = canvas.toDataURL('image/png');
            $base64Input.val(dataUrl);
            $removeInput.val('0');
            $previewImg.attr('src', dataUrl);
            $previewContainer.removeClass('d-none');
            
            bootstrap.Modal.getInstance($cropModal[0]).hide();
        }
    });

    $previewContainer.on('click', '#removeImageBtn', function () {
        $fileInput.val('');
        $base64Input.val('');
        $removeInput.val('1');
        $previewImg.attr('src', '');
        $previewContainer.addClass('d-none');
    });

    $('#commonModal').one('hidden.bs.offcanvas', function () {
        $cropModal.remove();
    });
});
</script>
