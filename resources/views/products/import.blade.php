<div class="text-center mb-4">
    <h3 class="mb-2">Import Products</h3>
    <p class="text-muted">Upload a CSV or Excel file to bulk create products</p>
</div>

<form id="commonModalForm" action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <div class="alert alert-info py-2 small mb-0" role="alert">
                <i class="ti ti-info-circle me-1"></i> download the <a href="{{ route('admin.products.import.sample') }}" class="fw-bold text-decoration-underline text-info">Sample Excel file</a>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label fw-medium mb-2">Choose CSV or Excel File <span class="text-danger">*</span></label>
            <div id="dropzone-area" class="border border-2 rounded-3 p-4 text-center cursor-pointer position-relative" style="border-style: dashed !important; border-color: #cbd5e1 !important; background-color: #f8fafc; transition: all 0.2s ease; min-height: 140px; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden;">
                <input type="file" name="csv_file" id="csv_file" class="position-absolute" style="position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; opacity: 0 !important; cursor: pointer !important; z-index: 10 !important; margin: 0 !important; padding: 0 !important;" accept=".csv,.txt,.xlsx,.xls" autofocus />
                <div class="py-2" style="pointer-events: none; position: relative; z-index: 1;">
                    <i class="ti ti-cloud-upload text-muted mb-2" style="font-size: 2.5rem !important;"></i>
                    <p class="fw-semibold mb-1" style="font-size: 0.95rem; color: #4b4b4b;">Drag & drop your file here or click to browse</p>
                    <span class="text-muted small">Supports: CSV, XLSX, XLS</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2">
                <span class="badge bg-label-theme-custom px-3 py-2 d-none" id="selected-file-badge"></span>
                <button type="button" id="btn-remove-file" class="btn btn-sm btn-icon btn-danger rounded-circle d-none" style="width: 22px; height: 22px; min-width: 22px; padding: 0;" title="Remove file">
                    <i class="ti ti-x" style="font-size: 0.75rem;"></i>
                </button>
            </div>
            <div class="invalid-feedback" id="file-error-feedback" style="display:none; color:#ea5455; font-size: 0.85em; margin-top: 0.25rem;">Please select a valid CSV or Excel file.</div>
        </div>

        <style>
            #dropzone-area.drag-over, #dropzone-area:hover {
                background-color: #f1f5f9 !important;
                border-color: #94a3b8 !important;
            }
            #dropzone-area.is-invalid {
                border-color: #ea5455 !important;
            }
            .bg-label-theme-custom {
                background-color: #fcf9f5 !important;
                color: #B4771E !important;
                border: 1px solid #B4771E;
            }
        </style>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50" id="btnSubmitImport">
            <i class="ti ti-upload me-1"></i> Import
        </button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<script>
$(document).ready(function () {
    const fileInput = $('#csv_file');
    const dropzone = $('#dropzone-area');
    const badge = $('#selected-file-badge');

    fileInput.on('change', function () {
        if (this.files && this.files.length > 0) {
            const fileName = this.files[0].name;
            badge.text(fileName).removeClass('d-none');
            $('#btn-remove-file').removeClass('d-none');
            fileInput.removeClass('is-invalid');
            dropzone.removeClass('is-invalid');
            $('#file-error-feedback').hide().text('');
        } else {
            badge.text('').addClass('d-none');
            $('#btn-remove-file').addClass('d-none');
        }
    });

    $('#btn-remove-file').on('click', function () {
        fileInput.val('');
        badge.text('').addClass('d-none');
        $(this).addClass('d-none');
        fileInput.removeClass('is-invalid');
        dropzone.removeClass('is-invalid');
        $('#file-error-feedback').hide().text('');
    });

    dropzone.on('dragenter dragover', function (e) {
        e.preventDefault();
        dropzone.addClass('drag-over');
    });

    dropzone.on('dragleave drop', function (e) {
        e.preventDefault();
        dropzone.removeClass('drag-over');
    });

    $('#commonModalForm').on('submit', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const form = $(this);
        const formData = new FormData(this);
        const submitBtn = $('#btnSubmitImport');

        fileInput.removeClass('is-invalid');
        $('#file-error-feedback').hide().text('');

        if (!fileInput[0].files || fileInput[0].files.length === 0) {
            fileInput.addClass('is-invalid');
            dropzone.addClass('is-invalid');
            $('#file-error-feedback').text('Please select a valid CSV or Excel file.').show();
            return;
        }

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importing...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                submitBtn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Import');
                if (res.status === 'success') {
                    toastr.success(res.message || 'Products imported successfully!');
                    $('#commonModal').offcanvas('hide');
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    } else {
                        window.location.reload();
                    }
                }
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Import');

                let errorsHtml = '';
                if (xhr.status === 422) {
                    const response = xhr.responseJSON;
                    if (response.message) {
                        if (Array.isArray(response.message)) {
                            errorsHtml = '<ul class="mb-0 text-start ps-3">';
                            response.message.forEach(err => { errorsHtml += '<li>' + err + '</li>'; });
                            errorsHtml += '</ul>';
                        } else if (typeof response.message === 'object') {
                            errorsHtml = '<ul class="mb-0 text-start ps-3">';
                            Object.values(response.message).forEach(errArr => {
                                  errArr.forEach(err => { errorsHtml += '<li>' + err + '</li>'; });
                            });
                            errorsHtml += '</ul>';
                        } else {
                            errorsHtml = response.message;
                        }
                    }
                } else {
                    errorsHtml = 'An unexpected error occurred during import.';
                }

                fileInput.addClass('is-invalid');
                $('#file-error-feedback').text('Failed to import products.').show();
                toastr.error(errorsHtml || 'Failed to import products.', 'Validation Errors', {
                    timeOut: 12000,
                    closeButton: true
                });
            }
        });
    });
});
</script>
