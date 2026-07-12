<style>
    #purchaseImportDropzone {
        border: 2px dashed #cbd5e1 !important;
        background-color: #f8fafc;
        transition: all 0.2s ease;
        min-height: 160px;
    }
    #purchaseImportDropzone.drag-over,
    #purchaseImportDropzone:hover {
        background-color: #f1f5f9 !important;
        border-color: #94a3b8 !important;
    }
    #purchaseImportDropzone.is-invalid {
        border-color: #ea5455 !important;
    }
    #purchaseImportProgressWrap {
        display: none;
    }
</style>

<form id="purchaseImportForm" class="d-flex flex-column h-100 mb-0">
    @csrf
    <div class="flex-grow-1 p-4" style="overflow-y: auto;">
        <div class="d-flex justify-content-end mb-4">
            <a href="{{ route('admin.purchases.import.sample') }}" class="btn btn-outline-secondary">
                <i class="ti ti-file-download me-1"></i> Download Sample Excel
            </a>
        </div>

        <label class="form-label fw-medium mb-2">Choose Excel File <span class="text-danger">*</span></label>
        <div id="purchaseImportDropzone" class="rounded-3 p-4 text-center cursor-pointer position-relative d-flex flex-column justify-content-center align-items-center">
            <input type="file" name="excel_file" id="purchase_excel_file" accept=".xlsx,.xls" class="position-absolute top-0 start-0 w-100 h-100" style="opacity:0; cursor:pointer;" />
            <div class="py-2" style="pointer-events:none;">
                <i class="ti ti-cloud-upload text-muted mb-2" style="font-size:2.5rem;"></i>
                <p class="fw-semibold mb-1">Drag & drop your Excel file here or click to browse</p>
                <span class="text-muted small">Supports: XLSX, XLS</span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 mt-2">
            <span class="badge bg-label-primary px-3 py-2 d-none" id="purchaseImportSelectedFileBadge"></span>
            <button type="button" id="purchaseImportBtnRemoveFile" class="btn btn-sm btn-icon btn-danger rounded-circle d-none" style="width:22px;height:22px;min-width:22px;padding:0;" title="Remove file">
                <i class="ti ti-x" style="font-size:0.75rem;"></i>
            </button>
        </div>
        <div class="invalid-feedback" id="purchaseImportFileErrorFeedback" style="display:none;">Please select a valid Excel file.</div>

        <div id="purchaseImportProgressWrap" class="mt-3">
            <div class="progress" style="height: 8px;">
                <div id="purchaseImportProgressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <small class="text-muted" id="purchaseImportProgressLabel">Uploading… 0%</small>
        </div>
    </div>

    <div class="d-flex p-4 border-top gap-3 mt-auto mb-0">
        <button type="submit" class="btn btn-primary flex-fill w-50 m-0" id="purchaseImportBtnSubmit">
            <i class="ti ti-upload me-1"></i> Import
        </button>
        <button type="button" class="btn btn-label-secondary flex-fill w-50 m-0" data-bs-dismiss="offcanvas">Cancel</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = $('#purchase_excel_file');
    const dropzone = $('#purchaseImportDropzone');
    const badge = $('#purchaseImportSelectedFileBadge');

    function resetFileState() {
        badge.text('').addClass('d-none');
        $('#purchaseImportBtnRemoveFile').addClass('d-none');
    }

    fileInput.on('change', function () {
        if (this.files && this.files.length > 0) {
            badge.text(this.files[0].name).removeClass('d-none');
            $('#purchaseImportBtnRemoveFile').removeClass('d-none');
            fileInput.removeClass('is-invalid');
            dropzone.removeClass('is-invalid');
            $('#purchaseImportFileErrorFeedback').hide();
        } else {
            resetFileState();
        }
    });

    $('#purchaseImportBtnRemoveFile').on('click', function () {
        fileInput.val('');
        resetFileState();
    });

    // Reset the whole panel every time it's opened, so a previous import's
    // file/progress state never lingers into the next use.
    document.getElementById('purchaseImportOffcanvas')?.addEventListener('show.bs.offcanvas', function () {
        fileInput.val('');
        resetFileState();
        fileInput.removeClass('is-invalid');
        dropzone.removeClass('is-invalid');
        $('#purchaseImportFileErrorFeedback').hide().text('');
        $('#purchaseImportProgressWrap').hide();
        $('#purchaseImportProgressBar').css('width', '0%');
        $('#purchaseImportProgressLabel').text('Uploading… 0%');
        $('#purchaseImportBtnSubmit').prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Import');
    });

    dropzone.on('dragenter dragover', function (e) {
        e.preventDefault();
        dropzone.addClass('drag-over');
    });

    dropzone.on('dragleave drop', function (e) {
        e.preventDefault();
        dropzone.removeClass('drag-over');
    });

    $('#purchaseImportForm').off('submit').on('submit', function (e) {
        e.preventDefault();

        fileInput.removeClass('is-invalid');
        dropzone.removeClass('is-invalid');
        $('#purchaseImportFileErrorFeedback').hide();

        if (!fileInput[0].files || fileInput[0].files.length === 0) {
            fileInput.addClass('is-invalid');
            dropzone.addClass('is-invalid');
            $('#purchaseImportFileErrorFeedback').text('Please select a valid Excel file.').show();
            return;
        }

        const formData = new FormData(this);
        const submitBtn = $('#purchaseImportBtnSubmit');
        const progressWrap = $('#purchaseImportProgressWrap');
        const progressBar = $('#purchaseImportProgressBar');
        const progressLabel = $('#purchaseImportProgressLabel');

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Uploading...');
        progressWrap.show();
        progressBar.css('width', '0%');
        progressLabel.text('Uploading… 0%');

        $.ajax({
            url: '{{ route('admin.purchases.import.store') }}',
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
                                progressLabel.text('Processing… this may take a moment for large files.');
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
                submitBtn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Import');
                progressWrap.hide();

                if (res.status === 'success') {
                    toastr.success(res.message || 'Purchase import completed successfully.');
                    fileInput.val('');
                    resetFileState();
                    bootstrap.Offcanvas.getInstance(document.getElementById('purchaseImportOffcanvas'))?.hide();
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    }
                }
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Import');
                progressWrap.hide();

                let message = 'Failed to process the Excel file.';
                if (xhr.responseJSON) {
                    if (typeof xhr.responseJSON.message === 'string') {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors && xhr.responseJSON.errors.excel_file) {
                        message = xhr.responseJSON.errors.excel_file[0];
                    }
                }

                fileInput.addClass('is-invalid');
                dropzone.addClass('is-invalid');
                $('#purchaseImportFileErrorFeedback').text(message).show();
                toastr.error(message, 'Import Failed', { timeOut: 12000, closeButton: true });
            }
        });
    });
});
</script>
