<style>
    #bulkZipDropzone {
        border: 2px dashed #cbd5e1 !important;
        background-color: #f8fafc;
        transition: all 0.2s ease;
        min-height: 160px;
    }
    #bulkZipDropzone.drag-over,
    #bulkZipDropzone:hover {
        background-color: #f1f5f9 !important;
        border-color: #94a3b8 !important;
    }
    #bulkZipDropzone.is-invalid {
        border-color: #ea5455 !important;
    }
    #bulkUploadProgressWrap {
        display: none;
    }
</style>

<form id="bulkImageUploadForm" class="d-flex flex-column h-100 mb-0">
    @csrf
    <div class="flex-grow-1 p-4" style="overflow-y: auto;">
        <div class="d-flex justify-content-end mb-4">
            <a href="{{ route('admin.products.bulk-images.sample') }}" class="btn btn-outline-secondary">
                <i class="ti ti-file-download me-1"></i> Download Sample ZIP
            </a>
        </div>

        <label class="form-label fw-medium mb-2">Choose ZIP File <span class="text-danger">*</span></label>
        <div id="bulkZipDropzone" class="rounded-3 p-4 text-center cursor-pointer position-relative d-flex flex-column justify-content-center align-items-center">
            <input type="file" name="zip_file" id="zip_file" accept=".zip" class="position-absolute top-0 start-0 w-100 h-100" style="opacity:0; cursor:pointer;" />
            <div class="py-2" style="pointer-events:none;">
                <i class="ti ti-cloud-upload text-muted mb-2" style="font-size:2.5rem;"></i>
                <p class="fw-semibold mb-1">Drag & drop your ZIP file here or click to browse</p>
                <span class="text-muted small">Supports: ZIP only</span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 mt-2">
            <span class="badge bg-label-primary px-3 py-2 d-none" id="bulkSelectedFileBadge"></span>
            <button type="button" id="bulkBtnRemoveFile" class="btn btn-sm btn-icon btn-danger rounded-circle d-none" style="width:22px;height:22px;min-width:22px;padding:0;" title="Remove file">
                <i class="ti ti-x" style="font-size:0.75rem;"></i>
            </button>
        </div>
        <div class="invalid-feedback" id="bulkFileErrorFeedback" style="display:none;">Please select a valid ZIP file.</div>

        <div id="bulkUploadProgressWrap" class="mt-3">
            <div class="progress" style="height: 8px;">
                <div id="bulkUploadProgressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <small class="text-muted" id="bulkUploadProgressLabel">Uploading… 0%</small>
        </div>

        <div id="bulkSummarySection" class="d-none mt-4">
            <h6 class="fw-semibold mb-3">Upload Summary</h6>
            <div class="row g-3 mb-4" id="bulkSummaryCards"></div>
        </div>
    </div>

    <div class="d-flex p-4 border-top gap-3 mt-auto mb-0">
        <button type="submit" class="btn btn-primary flex-fill w-50 m-0" id="bulkBtnSubmit">
            <i class="ti ti-upload me-1"></i> Upload & Process
        </button>
        <button type="button" class="btn btn-label-secondary flex-fill w-50 m-0" data-bs-dismiss="offcanvas">Cancel</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = $('#zip_file');
    const dropzone = $('#bulkZipDropzone');
    const badge = $('#bulkSelectedFileBadge');

    const summaryTiles = [
        { key: 'total_folders', label: 'Total Barcode Folders', icon: 'ti-folder', color: 'primary' },
        { key: 'matched', label: 'Matched Products', icon: 'ti-check', color: 'success' },
        { key: 'not_found', label: 'Products Not Found', icon: 'ti-alert-triangle', color: 'danger' },
        { key: 'primary_added', label: 'Primary Images Added', icon: 'ti-star', color: 'warning' },
        { key: 'additional_added', label: 'Additional Images Added', icon: 'ti-photo', color: 'info' },
        { key: 'failed_images', label: 'Failed Images', icon: 'ti-x', color: 'danger' },
        { key: 'skipped_files', label: 'Skipped Files', icon: 'ti-file-off', color: 'secondary' },
    ];

    function resetFileState() {
        badge.text('').addClass('d-none');
        $('#bulkBtnRemoveFile').addClass('d-none');
    }

    fileInput.on('change', function () {
        if (this.files && this.files.length > 0) {
            badge.text(this.files[0].name).removeClass('d-none');
            $('#bulkBtnRemoveFile').removeClass('d-none');
            fileInput.removeClass('is-invalid');
            dropzone.removeClass('is-invalid');
            $('#bulkFileErrorFeedback').hide();
        } else {
            resetFileState();
        }
    });

    $('#bulkBtnRemoveFile').on('click', function () {
        fileInput.val('');
        resetFileState();
    });

    // Reset the whole panel every time it's opened, so a previous upload's
    // file/progress/summary never lingers into the next use.
    document.getElementById('bulkImageOffcanvas')?.addEventListener('show.bs.offcanvas', function () {
        fileInput.val('');
        resetFileState();
        fileInput.removeClass('is-invalid');
        dropzone.removeClass('is-invalid');
        $('#bulkFileErrorFeedback').hide().text('');
        $('#bulkUploadProgressWrap').hide();
        $('#bulkUploadProgressBar').css('width', '0%');
        $('#bulkUploadProgressLabel').text('Uploading… 0%');
        $('#bulkSummarySection').addClass('d-none');
        $('#bulkSummaryCards').empty();
        $('#bulkBtnSubmit').prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Upload & Process');
    });

    // Refresh the products list whenever the panel closes, so any images
    // uploaded this session show up immediately without a full page reload.
    document.getElementById('bulkImageOffcanvas')?.addEventListener('hidden.bs.offcanvas', function () {
        if (typeof window.refreshTable === 'function') {
            window.refreshTable();
        }
    });

    dropzone.on('dragenter dragover', function (e) {
        e.preventDefault();
        dropzone.addClass('drag-over');
    });

    dropzone.on('dragleave drop', function (e) {
        e.preventDefault();
        dropzone.removeClass('drag-over');
    });

    function renderSummary(summary) {
        const cards = $('#bulkSummaryCards').empty();
        summaryTiles.forEach(function (tile) {
            const value = summary[tile.key] ?? 0;
            cards.append(`
                <div class="col-6">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <span class="text-muted small">${tile.label}</span>
                                    <h5 class="mb-0 mt-1">${value}</h5>
                                </div>
                                <span class="badge bg-label-${tile.color} rounded p-2"><i class="ti ${tile.icon} ti-sm"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });
        $('#bulkSummarySection').removeClass('d-none');
    }

    $('#bulkImageUploadForm').off('submit').on('submit', function (e) {
        e.preventDefault();

        fileInput.removeClass('is-invalid');
        dropzone.removeClass('is-invalid');
        $('#bulkFileErrorFeedback').hide();

        if (!fileInput[0].files || fileInput[0].files.length === 0) {
            fileInput.addClass('is-invalid');
            dropzone.addClass('is-invalid');
            $('#bulkFileErrorFeedback').text('Please select a valid ZIP file.').show();
            return;
        }

        const formData = new FormData(this);
        const submitBtn = $('#bulkBtnSubmit');
        const progressWrap = $('#bulkUploadProgressWrap');
        const progressBar = $('#bulkUploadProgressBar');
        const progressLabel = $('#bulkUploadProgressLabel');

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Uploading...');
        progressWrap.show();
        progressBar.css('width', '0%');
        progressLabel.text('Uploading… 0%');
        $('#bulkSummarySection').addClass('d-none');

        $.ajax({
            url: '{{ route('admin.products.bulk-images.store') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function () {
                const xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function (evt) {
                        if (evt.lengthComputable) {
                            const percent = Math.round((evt.loaded / evt.total) * 100);
                            progressBar.css('width', percent + '%');
                            if (percent >= 100) {
                                progressLabel.text('Processing… this may take a moment for large ZIP files.');
                                submitBtn.html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');
                            } else {
                                progressLabel.text('Uploading… ' + percent + '%');
                            }
                        }
                    });
                }
                return xhr;
            },
            success: function (res) {
                submitBtn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Upload & Process');
                progressWrap.hide();

                if (res.status === 'success') {
                    toastr.success(res.message || 'Bulk upload completed successfully.');
                    renderSummary(res.summary);
                }
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Upload & Process');
                progressWrap.hide();

                let message = 'Failed to process the ZIP file.';
                if (xhr.responseJSON) {
                    if (typeof xhr.responseJSON.message === 'string') {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors && xhr.responseJSON.errors.zip_file) {
                        message = xhr.responseJSON.errors.zip_file[0];
                    }
                }

                fileInput.addClass('is-invalid');
                dropzone.addClass('is-invalid');
                $('#bulkFileErrorFeedback').text(message).show();
                toastr.error(message, 'Upload Failed', { timeOut: 12000, closeButton: true });
            }
        });
    });
});
</script>
