<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ asset('assets/') }}" data-template="vertical-menu-template-no-customizer">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>@yield('title', 'Dashboard') | Chetan Immitation</title>
    <meta name="description" content="" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/tabler-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ filemtime(public_path('assets/css/custom.css')) }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />

    @yield('page-css')

    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            @include('layouts.partials.sidebar')

            <div class="layout-page">

                @include('layouts.partials.navbar')

                <div class="content-wrapper">
                    <div class="container-fluid flex-grow-1 container-p-y">
                        @yield('content')
                    </div>

                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-fluid">
                            <div
                                class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
                                <div>©
                                    <script>
                                        document.write(new Date().getFullYear());
                                    </script>, made with ❤️ by <a href="https://risingstarinfotech.com/" target="_blank"
                                        class="fw-semibold">Rising Star Infotech</a>
                                </div>
                            </div>
                        </div>
                    </footer>

                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>

    {{-- Common Side Panel --}}
    <div class="offcanvas offcanvas-end" id="commonModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-scroll="false" style="width: 600px; max-width: 100vw;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="commonModalTitle">Details</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column" id="commonModalBody">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        </div>
    </div>

    @stack('modals')



    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
    <script src="{{ asset('assets/js/common-modal.js') }}"></script>
    <script>
        $(document).ready(function() {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "5000"
            };
            @if(session('success'))
                toastr.success("{{ session('success') }}");
            @endif
            @if(session('error'))
                toastr.error("{{ session('error') }}");
            @endif
            @if(session('warning'))
                toastr.warning("{{ session('warning') }}");
            @endif
            @if(session('info'))
                toastr.info("{{ session('info') }}");
            @endif

            // Global password show/hide toggle
            $(document).on('click', '.input-group-text.cursor-pointer', function () {
                const input = $(this).siblings('input');
                const icon = $(this).find('i');
                if (input.length) {
                    if (input.attr('type') === 'password') {
                        input.attr('type', 'text');
                        icon.removeClass('ti-eye-off').addClass('ti-eye');
                    } else {
                        input.attr('type', 'password');
                        icon.removeClass('ti-eye').addClass('ti-eye-off');
                    }
                }
            });

            function initGlobalSelect2() {
                if (typeof $.fn.select2 === 'undefined') {
                    console.error('Select2 library is not loaded!');
                    return;
                }
                
                const targets = $('form select:not(.datatables-select):not([name$="_length"]):not(.no-select2):not([name*="status"]):not([name*="payment_method"])');
                targets.each(function() {
                    const selectEl = $(this);
                    if (selectEl.hasClass('select2-hidden-accessible')) {
                        return;
                    }
                    
                    if (selectEl.closest('.ql-toolbar').length || selectEl.closest('.ql-container').length || selectEl.closest('.ql-snow').length) {
                        return;
                    }
                    
                    selectEl.addClass('select2');
                    
                    const parentModal = selectEl.closest('#commonModal');
                    const hasEmptyOpt = selectEl.find('option[value=""]').length > 0;
                    
                    selectEl.select2({
                        dropdownParent: parentModal.length ? parentModal : $(document.body),
                        placeholder: hasEmptyOpt ? (selectEl.find('option[value=""]').text() || 'Select an option') : false,
                        allowClear: hasEmptyOpt,
                        width: '100%'
                    });
                });
            }

            initGlobalSelect2();

            const commonModalEl = document.getElementById('commonModal');
            if (commonModalEl) {
                commonModalEl.addEventListener('shown.bs.offcanvas', function () {
                    initGlobalSelect2();
                    setTimeout(initGlobalSelect2, 150);
                    setTimeout(initGlobalSelect2, 350);
                });
            }

            $(document).ajaxComplete(function() {
                initGlobalSelect2();
                setTimeout(initGlobalSelect2, 50);
            });
        });
    </script>

    <script>
        window.Apex = {
            noData: {
                text: 'No data available',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    color: '#8592a3',
                    fontSize: '16px',
                    fontFamily: 'Public Sans'
                }
            }
        };

        // Dynamically intercept and wrap ApexCharts globally to hide empty grids and axes
        (function() {
            let privateApexCharts = undefined;
            Object.defineProperty(window, 'ApexCharts', {
                get: function() {
                    return privateApexCharts;
                },
                set: function(originalValue) {
                    if (typeof originalValue === 'function') {
                        privateApexCharts = class extends originalValue {
                            constructor(el, options) {
                                const targetEl = typeof el === 'string' ? document.querySelector(el) : el;
                                
                                let hasData = false;
                                if (options && options.series && options.series.length > 0) {
                                    if (typeof options.series[0] === 'number') {
                                        hasData = options.series.some(val => val > 0);
                                    } else if (typeof options.series[0] === 'object') {
                                        hasData = options.series.some(s => s && s.data && s.data.length > 0 && s.data.some(val => val > 0));
                                    }
                                }
                                
                                if (!hasData) {
                                    if (targetEl) {
                                        targetEl.innerHTML = '<div class="d-flex flex-column align-items-center justify-content-center w-100 h-100 text-muted" style="min-height: 280px;"><i class="ti ti-chart-bar fs-1 mb-2" style="font-size: 2.8rem !important; color: #a1b0cb;"></i><span class="fw-semibold">No data available</span></div>';
                                    }
                                    return {
                                        render: function() { return Promise.resolve(); },
                                        destroy: function() {},
                                        updateOptions: function() {},
                                        updateSeries: function() {}
                                    };
                                }
                                
                                super(el, options);
                            }
                        };
                    } else {
                        privateApexCharts = originalValue;
                    }
                },
                configurable: true
            });
        })();
    </script>

    @yield('page-js')
</body>

</html>
