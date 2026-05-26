$(document).ready(function () {

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------
    function showSpinner() {
        return '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    }

    function clearFormErrors(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
    }

    function showFormErrors(form, errors) {
        $.each(errors, function (field, messages) {
            form.find('[name="' + field + '"]')
                .addClass('is-invalid')
                .siblings('.invalid-feedback')
                .text(messages[0]);
        });
    }

    function disableBtn(btn, loadingText) {
        btn.prop('disabled', true)
           .data('original-text', btn.html())
           .html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>' + (loadingText || 'Processing...'));
    }

    function enableBtn(btn) {
        btn.prop('disabled', false)
           .html(btn.data('original-text'));
    }

    // -------------------------------------------------------
    // Open common modal
    // Trigger : [data-common-modal="url"]
    // Optional: [data-size="modal-lg|modal-xl|modal-sm"]
    // -------------------------------------------------------
    $(document).on('click', '[data-common-modal]', function (e) {
        e.preventDefault();

        const url  = $(this).attr('data-common-modal');
        const size = $(this).attr('data-size') || '';

        $('#commonModalBody').html(showSpinner());
        $('#commonModal').offcanvas('show');

        $.get(url)
            .done(function (html) {
                $('#commonModalBody').html(html);

                let body = $('#commonModalBody');
                let titleBlock = body.find('.text-center.mb-4');
                
                if (titleBlock.length) {
                    $('#commonModalTitle').text(titleBlock.find('h3').text() || 'Details');
                    titleBlock.remove();
                } else {
                    $('#commonModalTitle').text('Details');
                }
                
                let form = body.find('form');
                if (form.length) {
                    form.addClass('d-flex flex-column flex-grow-1 h-100 mb-0');
                    
                    // Force single column layout inside side panel
                    form.find('.col-md-6, .col-sm-6, .col-lg-6, .col-md-4, .col-lg-4')
                        .removeClass('col-md-6 col-sm-6 col-lg-6 col-md-4 col-lg-4')
                        .addClass('col-12');
                    
                    let btnDiv = form.find('.text-center.mt-4').first();
                    let scrollWrapper = $('<div class="flex-grow-1 p-4" style="overflow-y: auto;"></div>');
                    
                    form.children().not(btnDiv).appendTo(scrollWrapper);
                    form.prepend(scrollWrapper);
                    
                    if(btnDiv.length) {
                        btnDiv.removeClass('text-center mt-4').addClass('d-flex p-4 border-top gap-3 mt-auto mb-0');
                        let submitBtn = btnDiv.find('button[type="submit"]');
                        let cancelBtn = btnDiv.find('button[data-bs-dismiss="modal"]');
                        
                        submitBtn.removeClass('me-2').addClass('flex-fill m-0 w-50');
                        cancelBtn.addClass('flex-fill m-0 w-50 btn-label-secondary');
                        cancelBtn.removeClass('btn-secondary'); // ensure label style
                    }
                }
            })
            .fail(function () {
                $('#commonModalBody').html('<p class="text-center text-danger p-4">Failed to load content.</p>');
            });
    });

    // Reset offcanvas on close
    $('#commonModal').on('hidden.bs.offcanvas', function () {
        $('#commonModalBody').html(showSpinner());
    });

    // Support for modal dismiss buttons inside the loaded HTML
    $(document).on('click', '#commonModal [data-bs-dismiss="modal"]', function() {
        $('#commonModal').offcanvas('hide');
    });

    // -------------------------------------------------------
    // Submit common modal form
    // Form must have: id="commonModalForm"
    // -------------------------------------------------------
    $(document).on('submit', '#commonModalForm', function (e) {
        e.preventDefault();

        const form      = $(this);
        const url       = form.attr('action');
        const method    = form.find('input[name="_method"]').val() || 'POST';
        const submitBtn = form.find('[type="submit"]');

        clearFormErrors(form);
        disableBtn(submitBtn, 'Saving...');

        $.ajax({
            url     : url,
            type    : method,
            data    : form.serialize(),
            success : function (res) {
                if (res.status === 'success') {
                    $('#commonModal').offcanvas('hide');
                    toastr.success(res.message);
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    } else {
                        setTimeout(() => location.reload(), 800);
                    }
                }
            },
            error : function (xhr) {
                enableBtn(submitBtn);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.message || {};
                    showFormErrors(form, errors);
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
            }
        });
    });

    // -------------------------------------------------------
    // Delete confirmation modal
    // Trigger : [data-common-delete="url"]
    // Required: [data-row-id="row-element-id"]
    // -------------------------------------------------------
    let deleteUrl   = null;
    let deleteRowId = null;

    $(document).on('click', '[data-common-delete]', function (e) {
        e.preventDefault();

        deleteUrl   = $(this).attr('data-common-delete');
        deleteRowId = $(this).attr('data-row-id');

        $('#commonDeleteModal').modal('show');
    });

    $(document).on('click', '#commonDeleteConfirmBtn', function () {
        if (!deleteUrl) return;

        const btn = $(this);
        disableBtn(btn, 'Deleting...');

        $.ajax({
            url  : deleteUrl,
            type : 'DELETE',
            data : { _token: csrfToken },
            success : function (res) {
                $('#commonDeleteModal').modal('hide');
                enableBtn(btn);
                if (res.status === 'success') {
                    toastr.success(res.message);
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    } else if (deleteRowId) {
                        $('#' + deleteRowId).fadeOut(400, function () { $(this).remove(); });
                    }
                }
                deleteUrl   = null;
                deleteRowId = null;
            },
            error : function (xhr) {
                $('#commonDeleteModal').modal('hide');
                enableBtn(btn);
                if (xhr.status === 422 && xhr.responseJSON?.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
                deleteUrl   = null;
                deleteRowId = null;
            }
        });
    });

    // Reset delete state on modal dismiss
    $('#commonDeleteModal').on('hidden.bs.modal', function () {
        enableBtn($('#commonDeleteConfirmBtn'));
        deleteUrl   = null;
        deleteRowId = null;
    });

    // -------------------------------------------------------
    // Common Confirm Modal
    // Trigger : [data-common-confirm="url"]
    // Required: [data-confirm-method="PATCH|POST|PUT"]
    // Optional: [data-confirm-title="Title text"]
    //           [data-confirm-text="Body text"]
    //           [data-confirm-btn="Button label"]
    //           [data-confirm-btn-class="btn-success|btn-warning..."]
    //           [data-confirm-data='{"key":"value"}']
    // -------------------------------------------------------
    let confirmUrl    = null;
    let confirmMethod = null;
    let confirmData   = {};

    $(document).on('click', '[data-common-confirm]', function (e) {
        e.preventDefault();

        confirmUrl    = $(this).attr('data-common-confirm');
        confirmMethod = $(this).attr('data-confirm-method') || 'POST';
        confirmData   = {};

        try {
            confirmData = JSON.parse($(this).attr('data-confirm-data') || '{}');
        } catch (err) {}

        const title    = $(this).attr('data-confirm-title')     || 'Are you sure?';
        const text     = $(this).attr('data-confirm-text')      || '';
        const btnLabel = $(this).attr('data-confirm-btn')       || 'Confirm';
        const btnClass = $(this).attr('data-confirm-btn-class') || 'btn-primary';

        $('#commonConfirmTitle').text(title);
        $('#commonConfirmText').text(text);
        $('#commonConfirmBtn')
            .text(btnLabel)
            .attr('class', 'btn ' + btnClass);

        $('#commonConfirmModal').modal('show');
    });

    $(document).on('click', '#commonConfirmBtn', function () {
        if (!confirmUrl) return;

        const btn  = $(this);
        const data = Object.assign({ _token: csrfToken }, confirmData);

        disableBtn(btn, 'Processing...');

        $.ajax({
            url     : confirmUrl,
            type    : confirmMethod,
            data    : data,
            success : function (res) {
                $('#commonConfirmModal').modal('hide');
                enableBtn(btn);
                if (res.status === 'success') {
                    toastr.success(res.message);
                    if (typeof window.onConfirmSuccess === 'function') {
                        window.onConfirmSuccess(res);
                    } else {
                        setTimeout(() => location.reload(), 800);
                    }
                }
                confirmUrl    = null;
                confirmMethod = null;
                confirmData   = {};
            },
            error : function (xhr) {
                $('#commonConfirmModal').modal('hide');
                enableBtn(btn);
                const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                confirmUrl    = null;
                confirmMethod = null;
                confirmData   = {};
            }
        });
    });

    // Reset confirm state on modal dismiss
    $('#commonConfirmModal').on('hidden.bs.modal', function () {
        enableBtn($('#commonConfirmBtn'));
        confirmUrl    = null;
        confirmMethod = null;
        confirmData   = {};
    });

});
