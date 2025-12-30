<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
</head>
<body class="login-page bg-body-tertiary">

<div class="login-box">
    <div class="card">
        <div class="card-body login-card-body">
            <div id="loginBox">
            <p class="login-box-msg">Login with Phone</p>

            <input type="text" id="phone" class="form-control mb-3" placeholder="Phone Number" maxlength="10">
                <small id="phoneError" class="text-danger mb-3 d-block"></small>

            <button id="sendOtp" class="btn btn-primary w-100">Send OTP</button>
            </div>
            <div id="otpBox" class="mt-3 d-none">
                <input type="text" id="otp" class="form-control mb-2" placeholder="Enter OTP" maxlength="6">
                <small id="otpError" class="text-danger mb-3 d-block"></small>
                <button id="verifyOtp" class="btn btn-success w-100">Verify OTP</button>
            </div>

        </div>
    </div>
</div>

<script src="{{ asset('adminlte/dist/js/jquery.min.js') }}"></script>

<script>
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    $('#sendOtp').click(function () {
        let phone = $('#phone').val();
        $('#phoneError').text('');

        $.ajax({
            url: '/send-otp',
            type: 'POST',
            data: { phone: phone },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.status) {
                    $('#otpBox').removeClass('d-none');
                    $('#loginBox').addClass('d-none');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    if (xhr.responseJSON.errors?.phone) {
                        $('#phoneError').text(xhr.responseJSON.errors.phone[0]);
                    } else if (xhr.responseJSON.message) {
                        $('#phoneError').text(xhr.responseJSON.message);

                    }
                } else {
                    $('#phoneError').text('Something went wrong, try again.');
                }
            }
        });
    });


    $('#verifyOtp').click(function () {
        let phone = $('#phone').val();
        let otp = $('#otp').val();
        $('#otpError').text(''); // Clear previous error

        $.ajax({
            url: '/verify-otp',
            type: 'POST',
            data: { phone: phone, otp: otp },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.status) {
                    window.location.href = res.redirect;
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors from Laravel
                    if (xhr.responseJSON.errors?.otp) {
                        $('#otpError').text(xhr.responseJSON.errors.otp[0]);
                    } else if (xhr.responseJSON.message) {
                        // Custom error from controller (invalid/expired OTP)
                        $('#otpError').text(xhr.responseJSON.message);
                    }
                } else {
                    $('#otpError').text('Something went wrong. Please try again.');
                }
            }
        });
    });

</script>

</body>
</html>
