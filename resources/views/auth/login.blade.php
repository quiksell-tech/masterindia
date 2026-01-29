<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <script src="{{ asset('adminlte/dist/js/jquery.min.js') }}"></script>
    <link rel="icon" href="/favicon.ico">
    <link rel="icon" type="image/png" href="/favicon.png">
    <style>
        body {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
        }
        .login-box {
            width: 380px;
        }
        .login-card-body {
            border-radius: 10px;
        }
        .btn-loading {
            pointer-events: none;
            opacity: 0.8;
        }
    </style>
</head>

<body class="login-page">

<div class="login-box">
    <div class="card shadow-lg">
        <div class="card-header text-center bg-white border-0">
            <h3 class="mb-0">
                <i class="fas fa-user-shield text-primary"></i> Admin Login
            </h3>
            <small class="text-muted">Secure OTP based login</small>
        </div>

        <div class="card-body login-card-body">

            <!-- PHONE BOX -->
            <div id="loginBox">
                <p class="login-box-msg">Enter your registered phone number</p>

                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    </div>
                    <input type="text" id="phone" class="form-control"
                           placeholder="10-digit phone number" maxlength="10">
                </div>
                <small id="phoneError" class="text-danger d-block mb-3"></small>

                <button id="sendOtp" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i> Send OTP
                </button>
            </div>

            <!-- OTP BOX -->
            <div id="otpBox" class="d-none">
                <p class="login-box-msg">Enter OTP sent to your phone</p>

                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                    </div>
                    <input type="text" id="otp" class="form-control"
                           placeholder="6-digit OTP" maxlength="6">
                </div>
                <small id="otpError" class="text-danger d-block mb-3"></small>

                <button id="verifyOtp" class="btn btn-success btn-block">
                    <i class="fas fa-check-circle"></i> Verify OTP
                </button>

                <button id="backToPhone" class="btn btn-link btn-block mt-2">
                    <i class="fas fa-arrow-left"></i> Change Phone Number
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    $('#sendOtp').click(function () {
        let phone = $('#phone').val();
        $('#phoneError').text('');

        let btn = $(this);
        btn.addClass('btn-loading').html('<i class="fas fa-spinner fa-spin"></i> Sending...');

        $.post('/send-otp', { phone })
            .done(res => {
                if (res.status) {
                    $('#loginBox').slideUp(200, function () {
                        $('#otpBox').removeClass('d-none').hide().slideDown(200);
                    });
                }
            })
            .fail(xhr => {
                if (xhr.status === 422) {
                    $('#phoneError').text(
                        xhr.responseJSON.errors?.phone?.[0] ||
                        xhr.responseJSON.message
                    );
                } else {
                    $('#phoneError').text('Something went wrong. Try again.');
                }
            })
            .always(() => {
                btn.removeClass('btn-loading')
                    .html('<i class="fas fa-paper-plane"></i> Send OTP');
            });
    });

    $('#verifyOtp').click(function () {
        let phone = $('#phone').val();
        let otp = $('#otp').val();
        $('#otpError').text('');

        let btn = $(this);
        btn.addClass('btn-loading').html('<i class="fas fa-spinner fa-spin"></i> Verifying...');

        $.post('/verify-otp', { phone, otp })
            .done(res => {
                if (res.status) {
                    window.location.href = res.redirect;
                }
            })
            .fail(xhr => {
                if (xhr.status === 422) {
                    $('#otpError').text(
                        xhr.responseJSON.errors?.otp?.[0] ||
                        xhr.responseJSON.message
                    );
                } else {
                    $('#otpError').text('Verification failed.');
                }
            })
            .always(() => {
                btn.removeClass('btn-loading')
                    .html('<i class="fas fa-check-circle"></i> Verify OTP');
            });
    });

    $('#backToPhone').click(function () {
        $('#otpBox').slideUp(200, function () {
            $('#loginBox').slideDown(200);
        });
    });
</script>

</body>
</html>
