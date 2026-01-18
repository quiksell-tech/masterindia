<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Master India')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/all.min.css') }}">
    <script src="{{ asset('adminlte/dist/js/jquery.min.js') }}"></script>
    <!-- AdminLTE v4 -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
@yield('style')
<body class="layout-fixed sidebar-expand-lg">
<div class="app-wrapper">

    <!-- Header -->
    <nav class="app-header navbar navbar-expand navbar-light bg-light">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.logout') }}">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="app-sidebar sidebar-dark-primary elevation-4">

    <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="brand-link">
                <span class="brand-text fw-light">Relcube</span>
            </a>
        </div>

        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-lte-toggle="treeview">

                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}"
                           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-gauge"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('companies.index') }}" class="nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-building"></i>
                            <p>Companies</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('party.index') }}" class="nav-link {{ request()->routeIs('party.*') ? 'active' : '' }} ">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Parties</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('company-addresses.index') }}"
                           class="nav-link {{ request()->routeIs('company-addresses.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-map-marker-alt"></i>
                            <p>Addresses</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('transporters.index') }}"
                           class="nav-link {{ request()->routeIs('transporters.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>Transporters</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('items.index') }}"
                           class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-map-marker-alt"></i>
                            <p>Items</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('orders.index') }}"
                           class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-map-marker-alt"></i>
                            <p>Orders</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('creditnote.index') }}"
                           class="nav-link {{ request()->routeIs('creditnote.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-map-marker-alt"></i>
                            <p>CreditNote</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content -->
    <main class="app-main">
        <div class="content-wrapper p-3">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="app-footer text-center">
        <strong>© {{ date('Y') }} AdminLTE v4</strong>

        <script>

            function unexpectedErrorHandler(xhr) {
                let errorMsg = "An unexpected error occurred.";
                if (xhr.status === 500) {
                    errorMsg = "Internal Server Error (500). Please try again later.";
                } else if (xhr.status === 403) {
                    errorMsg = "Forbidden (403). You do not have permission to perform this action.";
                }else if (xhr.status === 401) {
                    errorMsg = "Unauthorized (401). Your session may have expired, please log in again.";
                }else if (xhr.status === 402) {
                    errorMsg = "Some thing went wrong";
                }
                // Update modal content with the error message
                $('#errorModalBody').text(errorMsg);
                // Show the modal (Bootstrap 5 syntax)
                let errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
            }
            function showAjaxResponse(response,title)
            {
                if(response.status=='success') {
                    Swal.fire({
                        icon: 'success',
                        title: title,
                        text: response.message,
                        timer: 40000,
                        showConfirmButton: false
                    });
                }else{
                    Swal.fire({
                        icon: 'error',
                        title: title,
                        text: response.message,
                        timer: 40000,
                        showConfirmButton: false
                    });
                }
            }

        </script>
    </footer>

</div>

<!-- JS -->
<script src="{{ asset('adminlte/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('adminlte/dist/js/adminlte.min.js') }}"></script>

@yield('scripts')
</body>
</html>
