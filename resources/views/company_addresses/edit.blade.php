@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-1"></i>
                    Edit Company Address
                </h3>
            </div>

            <form method="POST"
                  action="{{ route('company-addresses.update', $address->address_id) }}">
                @csrf


                <div class="card-body">

                    {{-- Company & Party --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Company</label>
                                <select name="company_id" id="company_id" class="form-control" required>
                                    <option value="">Select Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->company_id }}"
                                            {{ $company->company_id == $address->company_id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Party</label>
                                <select name="party_id" id="party_id" class="form-control" required>
                                    <option value="">Select Party</option>
                                    @foreach($parties as $party)
                                        <option value="{{ $party->party_id }}"
                                            {{ $party->party_id == $address->party_id ? 'selected' : '' }}>
                                            {{ $party->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        {{-- Address Type --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Address Type</label>
                                <select name="address_type" class="form-control" required>
                                    <option value="office" {{ $address->address_type == 'office' ? 'selected' : '' }}>
                                        Office
                                    </option>
                                    <option value="shipping" {{ $address->address_type == 'shipping' ? 'selected' : '' }}>
                                        Shipping
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Is Active</label>
                                <select name="is_active" class="form-control" required>
                                    <option value="Y" {{ $address->is_active == 'Y' ? 'selected' : '' }}>
                                        Yes
                                    </option>
                                    <option value="N" {{ $address->is_active == 'N' ? 'selected' : '' }}>
                                        No
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    {{-- Address Line --}}
                    <div class="form-group">
                        <label>Address Line</label>
                        <textarea name="address_line"
                                  class="form-control"
                                  rows="3"
                                  required>{{ $address->address_line }}</textarea>
                    </div>

                    {{-- Pincode --}}
                    <div class="row">
                        <div class="col-md-4">
                            <label>Pincode</label>
                            <input type="text"
                                   id="pincode"
                                   name="pincode"
                                   value="{{ $address->pincode }}"
                                   class="form-control"
                                   required>
                        </div>
                    </div>

                    {{-- Location --}}
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <label>City</label>
                            <input type="text"
                                   id="city"
                                   class="form-control"
                                   value="{{ $address->city }}"
                                   readonly>
                        </div>
                        <div class="col-md-4">
                            <label>State</label>
                            <input type="text"
                                   id="state"
                                   class="form-control"
                                   value="{{ $address->state }}"
                                   readonly>
                        </div>
                        <div class="col-md-4">
                            <label>State Code</label>
                            <input type="text"
                                   id="state_code"
                                   class="form-control"
                                   value="{{ $address->state_code }}"
                                   readonly>
                        </div>
                    </div>

                </div>

                <div class="card-footer text-right">
                    <button class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i> Update
                    </button>
                    <a href="{{ route('company-addresses.index') }}"
                       class="btn btn-secondary ml-2">
                        Cancel
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
