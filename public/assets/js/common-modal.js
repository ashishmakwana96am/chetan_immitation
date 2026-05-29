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
            let inputName = field;
            if (field.includes('.')) {
                let parts = field.split('.');
                inputName = parts[0] + '[' + parts.slice(1).join('][') + ']';
            }
            let input = form.find('[name="' + inputName + '"], [name="' + inputName + '[]"]');
            if (input.length) {
                input.addClass('is-invalid');
                let feedback = input.siblings('.invalid-feedback');
                if (feedback.length === 0 && input.parent('.input-group').length) {
                    feedback = input.parent('.input-group').siblings('.invalid-feedback');
                }
                if (feedback.length) {
                    feedback.text(messages[0]);
                } else {
                    toastr.error(messages[0]);
                }
            } else {
                toastr.error(messages[0]);
            }
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
    // Clear validation error on input/change globally
    // -------------------------------------------------------
    $(document).on('input change', '.is-invalid', function () {
        $(this).removeClass('is-invalid');
        let feedback = $(this).siblings('.invalid-feedback');
        if (feedback.length === 0 && $(this).parent('.input-group').length) {
            feedback = $(this).parent('.input-group').siblings('.invalid-feedback');
        }
        feedback.text('');
    });

    // -------------------------------------------------------
    // DataTables global pagination fix
    // -------------------------------------------------------
    if ($.fn.DataTable) {
        $(document).on('draw.dt', function (e, settings) {
            const api = new $.fn.dataTable.Api(settings);
            const info = api.page.info();
            if (info.pages > 0 && info.page >= info.pages) {
                api.page('previous').draw('page');
            }
        });
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
                    
                    form.find('.row > div')
                        .removeClass('col-12 col-md-12 col-md-6 col-sm-6 col-lg-6 col-md-4 col-lg-4')
                        .addClass('col-md-6 col-12');
                    
                    let btnDiv = form.find('button[type="submit"]').parent();
                    let scrollWrapper = $('<div class="flex-grow-1 p-4" style="overflow-y: auto;"></div>');
                    
                    form.children().not(btnDiv).appendTo(scrollWrapper);
                    form.prepend(scrollWrapper);
                    
                    if(btnDiv.length) {
                        btnDiv.removeClass('text-center mt-4 pt-3 gap-2 border-top').addClass('d-flex p-4 border-top gap-3 mt-auto mb-0');
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
    // Delete confirmation modal (SweetAlert)
    // Trigger : [data-common-delete="url"]
    // Required: [data-row-id="row-element-id"]
    // -------------------------------------------------------
    $(document).on('click', '[data-common-delete]', function (e) {
        e.preventDefault();

        const url = $(this).attr('data-common-delete');
        const rowId = $(this).attr('data-row-id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.value) {
                $.ajax({
                    url  : url,
                    type : 'DELETE',
                    data : { _token: csrfToken },
                    success : function (res) {
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            if (typeof window.refreshTable === 'function') {
                                window.refreshTable();
                            } else if (rowId) {
                                $('#' + rowId).fadeOut(400, function () { $(this).remove(); });
                            }
                        }
                    },
                    error : function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('Something went wrong. Please try again.');
                        }
                    }
                });
            }
        });
    });

    // -------------------------------------------------------
    // Common Confirm Modal (SweetAlert)
    // Trigger : [data-common-confirm="url"]
    // Required: [data-confirm-method="PATCH|POST|PUT"]
    // Optional: [data-confirm-title="Title text"]
    //           [data-confirm-text="Body text"]
    //           [data-confirm-btn="Button label"]
    //           [data-confirm-btn-class="btn-success|btn-warning..."]
    //           [data-confirm-data='{"key":"value"}']
    // -------------------------------------------------------
    $(document).on('click', '[data-common-confirm]', function (e) {
        e.preventDefault();

        const url    = $(this).attr('data-common-confirm');
        const method = $(this).attr('data-confirm-method') || 'POST';
        let data   = {};

        try {
            data = JSON.parse($(this).attr('data-confirm-data') || '{}');
        } catch (err) {}

        const title    = $(this).attr('data-confirm-title')     || 'Are you sure?';
        const text     = $(this).attr('data-confirm-text')      || '';
        const btnLabel = $(this).attr('data-confirm-btn')       || 'Confirm';
        const btnClass = $(this).attr('data-confirm-btn-class') || 'btn-primary';

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: btnLabel,
            customClass: {
                confirmButton: 'btn ' + btnClass + ' me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.value) {
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const ajaxData = Object.assign({ _token: csrfToken }, data);

                $.ajax({
                    url     : url,
                    type    : method,
                    data    : ajaxData,
                    success : function (res) {
                        Swal.close();
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            if (typeof window.onConfirmSuccess === 'function') {
                                window.onConfirmSuccess(res);
                            } else {
                                setTimeout(() => location.reload(), 800);
                            }
                        }
                    },
                    error : function (xhr) {
                        Swal.close();
                        const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                        toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                    }
                });
            }
        });
    });

});
