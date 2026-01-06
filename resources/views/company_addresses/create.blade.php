@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    Add Party Address
                </h3>
            </div>

            <form method="POST" action="{{ route('company-addresses.store') }}">
                @csrf

                <div class="card-body">

                    {{-- Company & Party --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-building mr-1"></i> Company
                                </label>
                                <select name="company_id" id="company_id" class="form-control" required>
                                    <option value="">Select Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->company_id }}">
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-users mr-1"></i> Party
                                </label>
                                <select name="party_id" id="party_id" class="form-control" required>
                                    <option value="">Select Company First</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Address Type --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tag mr-1"></i> Address Type
                                </label>
                                <select name="address_type" class="form-control" required>
                                    <option value="office">Office</option>
                                    <option value="shipping">Shipping</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Address Line --}}
                    <div class="form-group">
                        <label>
                            <i class="fas fa-location-arrow mr-1"></i> Address Line
                        </label>
                        <textarea name="address_line"
                                  rows="3"
                                  class="form-control"
                                  placeholder="Enter complete address"
                                  required></textarea>
                    </div>

                    {{-- Pincode --}}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-map-pin mr-1"></i> Pincode
                                </label>
                                <input type="text"
                                       id="pincode"
                                       name="pincode"
                                       maxlength="6"
                                       class="form-control"
                                       placeholder="6-digit pincode"
                                       required>
                            </div>
                        </div>
                    </div>

                    {{-- Auto Filled Location --}}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" id="city" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" id="state" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>State Code</label>
                                <input type="text" id="state_code" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Save Address
                    </button>
                    <a href="{{ route('company-addresses.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
@endsection


@section('scripts')
    <script>
        document.getElementById('pincode').addEventListener('blur', function () {
            let pincode = this.value;
            if (pincode.length < 6) return;

            fetch(`/fetch-pincode/${pincode}`)
                .then(res => res.json())
                .then(data => {
                    if (data) {
                        document.getElementById('city').value = data.city_name;
                        document.getElementById('state').value = data.state_name;
                        document.getElementById('state_code').value = data.state_code;
                    } else {
                        alert('Invalid pincode');
                    }
                });
        });

        $('#company_id').on('change', function () {
            let companyId = $(this).val();

            $('#party_id').html('<option value="">Loading...</option>');

            if (!companyId) {
                $('#party_id').html('<option value="">Select Company First</option>');
                return;
            }

            $.ajax({
                url: "{{ route('get.parties') }}",
                type: "GET",
                data: { company_id: companyId },
                success: function (data) {
                    let options = '<option value="">Select Party</option>';

                    if (data.length === 0) {
                        options += '<option value="">No parties found</option>';
                    }

                    $.each(data, function (i, party) {
                        options += `<option value="${party.party_id}">${party.name}</option>`;
                    });

                    $('#party_id').html(options);
                },
                error: function () {
                    $('#party_id').html('<option value="">Failed to load</option>');
                }
            });
        });

    </script>

@endsection
