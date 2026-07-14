<style>
    #productImportDropzone {
        border: 2px dashed #cbd5e1 !important;
        background-color: #f8fafc;
        transition: all 0.2s ease;
        min-height: 160px;
    }
    #productImportDropzone.drag-over,
    #productImportDropzone:hover {
        background-color: #f1f5f9 !important;
        border-color: #94a3b8 !important;
    }
    #productImportDropzone.is-invalid {
        border-color: #ea5455 !important;
    }
    #productImportProgressWrap {
        display: none;
    }
</style>

<form id="productImportForm" class="d-flex flex-column h-100 mb-0">
    @csrf
    <div class="flex-grow-1 p-4" style="overflow-y: auto;">
        <div class="d-flex justify-content-end mb-4">
            <a href="{{ route('admin.products.import.sample') }}" class="btn btn-outline-secondary">
                <i class="ti ti-file-download me-1"></i> Download Sample File
            </a>
        </div>

        <label class="form-label fw-medium mb-2">Choose Excel File <span class="text-danger">*</span></label>
        <div id="productImportDropzone" class="rounded-3 p-4 text-center cursor-pointer position-relative d-flex flex-column justify-content-center align-items-center">
            <input type="file" name="excel_file" id="product_excel_file" accept=".xlsx,.xls" class="position-absolute top-0 start-0 w-100 h-100" style="opacity:0; cursor:pointer;" />
            <div class="py-2" style="pointer-events:none;">
                <i class="ti ti-cloud-upload text-muted mb-2" style="font-size:2.5rem;"></i>
                <p class="fw-semibold mb-1">Drag & drop your Excel file here or click to browse</p>
                <span class="text-muted small">Supports: XLSX, XLS</span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 mt-2">
            <span class="badge bg-label-primary px-3 py-2 d-none" id="productImportSelectedFileBadge"></span>
            <button type="button" id="productImportBtnRemoveFile" class="btn btn-sm btn-icon btn-danger rounded-circle d-none" style="width:22px;height:22px;min-width:22px;padding:0;" title="Remove file">
                <i class="ti ti-trash" style="font-size:0.75rem;"></i>
            </button>
        </div>
        <div class="invalid-feedback" id="productImportFileErrorFeedback" style="display:none;">Please select a valid Excel file.</div>

        <div id="productImportProgressWrap" class="mt-3">
            <div class="progress mb-1" style="height: 8px;">
                <div id="productImportProgressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted" id="productImportProgressLabel">Uploading… 0%</small>
                <a href="javascript:void(0);" id="productImportBtnCancel" class="text-danger fw-semibold d-inline-flex align-items-center" style="font-size: 0.75rem; text-decoration: none;">
                    <i class="ti ti-x me-1" style="font-size: 0.85rem;"></i> Cancel
                </a>
            </div>
        </div>
    </div>

    <div class="d-flex p-4 border-top gap-3 mt-auto mb-0">
        <button type="submit" class="btn btn-primary flex-fill w-50 m-0" id="productImportBtnSubmit">
            <i class="ti ti-upload me-1"></i> Import
        </button>
        <button type="button" class="btn btn-label-secondary flex-fill w-50 m-0" data-bs-dismiss="offcanvas">Cancel</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = $('#product_excel_file');
    const dropzone = $('#productImportDropzone');
    const badge = $('#productImportSelectedFileBadge');
    let currentProductImportRequest = null;

    $(document).on('click', '#productImportBtnCancel', function () {
        if (currentProductImportRequest) {
            currentProductImportRequest.abort();
            currentProductImportRequest = null;
            toastr.warning('Import process cancelled.');
        }
        $('#productImportProgressWrap').hide();
        $('#productImportProgressBar').css('width', '0%');
        $('#productImportProgressLabel').text('Uploading… 0%');
        $('#productImportBtnSubmit').prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Import');
    });

    function resetFileState() {
        badge.text('').addClass('d-none');
        $('#productImportBtnRemoveFile').addClass('d-none');
    }

    fileInput.on('change', function () {
        if (this.files && this.files.length > 0) {
            badge.text(this.files[0].name).removeClass('d-none');
            $('#productImportBtnRemoveFile').removeClass('d-none');
            fileInput.removeClass('is-invalid');
            dropzone.removeClass('is-invalid');
            $('#productImportFileErrorFeedback').hide();
        } else {
            resetFileState();
        }
    });

    $('#productImportBtnRemoveFile').on('click', function () {
        fileInput.val('');
        resetFileState();
    });

    // Reset the whole panel every time it's opened, so a previous import's
    // file/progress state never lingers into the next use.
    document.getElementById('productImportOffcanvas')?.addEventListener('show.bs.offcanvas', function () {
        fileInput.val('');
        resetFileState();
        fileInput.removeClass('is-invalid');
        dropzone.removeClass('is-invalid');
        $('#productImportFileErrorFeedback').hide().text('');
        $('#productImportProgressWrap').hide();
        $('#productImportProgressBar').css('width', '0%');
        $('#productImportProgressLabel').text('Uploading… 0%');
        $('#productImportBtnSubmit').prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Import');
    });

    document.getElementById('productImportOffcanvas')?.addEventListener('hidden.bs.offcanvas', function () {
        if (currentProductImportRequest) {
            currentProductImportRequest.abort();
            currentProductImportRequest = null;
            toastr.warning('Import process cancelled.');
        }
    });

    dropzone.on('dragenter dragover', function (e) {
        e.preventDefault();
        dropzone.addClass('drag-over');
    });

    dropzone.on('dragleave drop', function (e) {
        e.preventDefault();
        dropzone.removeClass('drag-over');
        if (e.type === 'drop') {
            const dt = e.originalEvent.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                fileInput[0].files = dt.files;
                fileInput.trigger('change');
            }
        }
    });

    $('#productImportForm').off('submit').on('submit', function (e) {
        e.preventDefault();

        fileInput.removeClass('is-invalid');
        dropzone.removeClass('is-invalid');
        $('#productImportFileErrorFeedback').hide();

        if (!fileInput[0].files || fileInput[0].files.length === 0) {
            fileInput.addClass('is-invalid');
            dropzone.addClass('is-invalid');
            $('#productImportFileErrorFeedback').text('Please select a valid Excel file.').show();
            return;
        }

        const formData = new FormData(this);
        const submitBtn = $('#productImportBtnSubmit');
        const progressWrap = $('#productImportProgressWrap');
        const progressBar = $('#productImportProgressBar');
        const progressLabel = $('#productImportProgressLabel');

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Uploading...');
        progressWrap.show();
        progressBar.css('width', '0%');
        progressLabel.text('Uploading… 0%');

        currentProductImportRequest = $.ajax({
            url: '{{ route('admin.products.import') }}',
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
                currentProductImportRequest = null;
                submitBtn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Import');
                progressWrap.hide();

                if (res.status === 'success') {
                    toastr.success(res.message || 'Product import completed successfully.');
                    fileInput.val('');
                    resetFileState();
                    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('productImportOffcanvas')).hide();
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    }
                    showProductImportHistoryOffcanvas(res);
                }
            },
            error: function (xhr) {
                currentProductImportRequest = null;
                submitBtn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Import');
                progressWrap.hide();

                if (xhr.statusText === 'abort') {
                    return;
                }

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
                $('#productImportFileErrorFeedback').text(message).show();
                toastr.error(message, 'Import Failed', { timeOut: 12000, closeButton: true });
            }
        });
    });

    const importSummaryTiles = [
        { key: 'total_groups', label: 'Total Rows/Groups', icon: 'ti-list-details', color: 'primary' },
        { key: 'products_created', label: 'Products Created', icon: 'ti-square-plus', color: 'success' },
        { key: 'existing_products_used', label: 'Existing Reused', icon: 'ti-refresh', color: 'info' },
        { key: 'categories_created', label: 'Categories Created', icon: 'ti-category', color: 'warning' },
        { key: 'sub_categories_created', label: 'Sub Categories Created', icon: 'ti-category-2', color: 'secondary' },
        { key: 'failed_rows', label: 'Failed Rows', icon: 'ti-x', color: 'danger' },
        { key: 'skipped_rows', label: 'Skipped Rows', icon: 'ti-file-off', color: 'secondary' },
    ];

    function showProductImportHistoryOffcanvas(res) {
        const summaryCards = $('#productImportHistorySummaryCards').empty();
        importSummaryTiles.forEach(function (tile) {
            const value = res.summary[tile.key] ?? 0;
            summaryCards.append(`
                <div class="col-sm-6 col-md-4">
                    <div class="card shadow-none border h-100">
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

        window.productImportHistory = res.history || [];
        $('#productImportHistorySearchInput').val('');
        renderProductImportHistoryTable(window.productImportHistory);

        const historyEl = document.getElementById('productImportHistoryOffcanvas');
        bootstrap.Offcanvas.getOrCreateInstance(historyEl).show();
    }

    function renderProductImportHistoryTable(items) {
        const tbody = $('#productImportHistoryTableBody').empty();
        if (items.length === 0) {
            tbody.append(`<tr><td colspan="4" class="text-center text-muted py-3">No history details available.</td></tr>`);
            return;
        }

        items.forEach(function (item) {
            let badgeColor = 'secondary';
            if (item.status === 'Success') badgeColor = 'success';
            else if (item.status === 'Warning') badgeColor = 'warning';
            else if (item.status === 'Failed') badgeColor = 'danger';

            tbody.append(`
                <tr>
                    <td class="fw-semibold">${item.barcode || 'N/A'}</td>
                    <td>${item.product || 'N/A'}</td>
                    <td><span class="badge bg-label-${badgeColor}">${item.status}</span></td>
                    <td>
                        <span class="text-dark d-block fw-medium">${item.reason}</span>
                        <small class="text-muted">${item.details}</small>
                    </td>
                </tr>
            `);
        });
    }

    $(document).on('input', '#productImportHistorySearchInput', function () {
        const query = $(this).val().toLowerCase().trim();
        if (!window.productImportHistory) return;
        const filtered = window.productImportHistory.filter(function (item) {
            const barcode = item.barcode ? String(item.barcode).toLowerCase() : '';
            const product = item.product ? String(item.product).toLowerCase() : '';
            return barcode.includes(query) || product.includes(query);
        });
        renderProductImportHistoryTable(filtered);
    });
});
</script>
