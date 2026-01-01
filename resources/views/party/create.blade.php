@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-1"></i>
                    Add Party
                </h3>
            </div>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('party.store') }}">
                @csrf

                <div class="card-body">

                    {{-- Company --}}
                    <div class="form-group">
                        <label>
                            <i class="fas fa-building mr-1"></i> Company
                        </label>
                        <select name="company_id" class="form-control" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->company_id }}">
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        {{-- Party Name --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-user-tag mr-1"></i> Party Trade Name
                                </label>
                                <input name="party_trade_name"
                                       class="form-control"
                                       placeholder="Enter party name"
                                       id="party_trade_name"
                                       required>
                                <small class="text-danger d-none" id="error_party_trade_name"></small>
                            </div>

                        </div>

                        {{-- Legal Name --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-file-contract mr-1"></i> Legal Name
                                </label>
                                <input name="party_legal_name"
                                       class="form-control"
                                       id="party_legal_name"
                                       placeholder="Enter legal name"
                                       required>
                                <small class="text-danger d-none" id="error_party_legal_name"></small>

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-file-contract mr-1"></i> contact_name
                                </label>
                                <input name="contact_name"
                                       class="form-control"
                                       placeholder="Enter Contact name"
                                       id="contact_name"
                                       required>
                                <small class="text-danger d-none" id="error_contact_name"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-file-contract mr-1"></i> phone
                                </label>
                                <input name="phone"
                                       class="form-control"
                                       placeholder="Enter Phone"
                                       id="phone"
                                       maxlength="10"
                                       required>
                                <small class="text-danger d-none" id="error_phone"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-file-contract mr-1"></i> email
                                </label>
                                <input name="email"
                                       class="form-control"
                                       placeholder="Enter Email"
                                       id="email"
                                       required>
                                <small class="text-danger d-none" id="error_email"></small>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        {{-- GSTN --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-receipt mr-1"></i> GSTN
                                </label>
                                <input name="party_gstn"
                                       class="form-control"
                                       id="party_gstn"
                                       placeholder="15-character GST number UPPER CASE">
                                <small class="text-danger d-none" id="error_party_gstn"></small>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-toggle-on mr-1"></i> Status
                                </label>
                                <select name="is_active" class="form-control">
                                    <option value="Y">Active</option>
                                    <option value="N">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Save
                    </button>
                    <a href="{{ route('party.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>

            </form>
        </div>

    </div>
@endsection
@section('scripts')
    <script>
        const form = document.querySelector('form');

        const fields = {
            party_trade_name: 'Party Trade Name is required',
            party_legal_name: 'Legal Name is required',
            contact_name: 'Contact Name is required',
        };

        function showError(field, message) {
            const input = document.getElementById(field);
            const error = document.getElementById('error_' + field);
            input.classList.add('is-invalid');
            error.textContent = message;
            error.classList.remove('d-none');
        }

        function clearError(field) {
            const input = document.getElementById(field);
            const error = document.getElementById('error_' + field);
            input.classList.remove('is-invalid');
            error.textContent = '';
            error.classList.add('d-none');
        }

        form.addEventListener('submit', function (e) {
            let hasError = false;

            // Required fields
            Object.keys(fields).forEach(field => {
                const value = document.getElementById(field).value.trim();
                if (!value) {
                    showError(field, fields[field]);
                    hasError = true;
                }
            });

            // Phone
            const phone = document.getElementById('phone').value.trim();
            if (!/^[6-9]\d{9}$/.test(phone)) {
                showError('phone', 'Phone must be a valid 10-digit Indian number');
                hasError = true;
            }

            // Email
            const email = document.getElementById('email').value.trim();
            if (!/^\S+@\S+\.\S+$/.test(email)) {
                showError('email', 'Invalid email address');
                hasError = true;
            }

            // GST
            const gstn = document.getElementById('party_gstn').value.trim();
            if (!/^[0-9A-Z]{15}$/.test(gstn)) {
                showError('party_gstn', 'GSTN must be exactly 15 characters');
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
            }
        });

        // Clear error on input
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function () {
                clearError(this.id);
            });
        });
    </script>


@endsection
