<div class="text-center mb-4">
    <h3 class="mb-2">Import Products</h3>
    <p class="text-muted">Upload a CSV file to bulk create products</p>
</div>

<form id="commonModalForm" action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <div class="alert alert-info py-2 small mb-0" role="alert">
                <i class="ti ti-info-circle me-1"></i> You can download the <a href="{{ route('admin.products.import.sample') }}" class="fw-bold text-decoration-underline text-info">Sample CSV file</a> to format your data correctly. All imported products will be set as <strong>Active</strong>.
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="csv_file">Choose CSV File <span class="text-danger">*</span></label>
            <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" autofocus />
            <div class="invalid-feedback"></div>
        </div>
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
    $('#commonModalForm').on('submit', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const form = $(this);
        const formData = new FormData(this);
        const submitBtn = $('#btnSubmitImport');
        const fileInput = $('#csv_file');

        fileInput.removeClass('is-invalid');
        fileInput.siblings('.invalid-feedback').text('');
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
                toastr.error(errorsHtml || 'Failed to import products.', 'Validation Errors', {
                    timeOut: 12000,
                    closeButton: true
                });
            }
        });
    });
});
</script>
