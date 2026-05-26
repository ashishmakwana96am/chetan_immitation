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
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/toastr/toastr.css') }}" />

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
                                    </script>, made with ❤️ by <a href="#"
                                        class="fw-semibold">Chetan Immitation</a>
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
    <div class="offcanvas offcanvas-end" id="commonModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" style="width: 600px;">
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

    {{-- Common Delete Confirmation Modal --}}
    <div class="modal fade" id="commonDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px">
            <div class="modal-content p-3 p-md-4">
                <div class="modal-body text-center">
                    <span class="ti ti-alert-circle text-danger" style="font-size: 4rem;"></span>
                    <h4 class="mt-3 mb-1">Are you sure?</h4>
                    <p class="text-muted mb-4">This action cannot be undone.</p>
                    <button type="button" class="btn btn-label-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="commonDeleteConfirmBtn">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Common Confirm Modal --}}
    <div class="modal fade" id="commonConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px">
            <div class="modal-content p-3 p-md-4">
                <div class="modal-body text-center">
                    <span class="ti ti-alert-circle text-warning" style="font-size: 4rem;"></span>
                    <h4 class="mt-3 mb-1" id="commonConfirmTitle">Are you sure?</h4>
                    <p class="text-muted mb-4" id="commonConfirmText"></p>
                    <button type="button" class="btn btn-label-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" id="commonConfirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset('assets/js/common-modal.js') }}"></script>

    @yield('page-js')
</body>

</html>
