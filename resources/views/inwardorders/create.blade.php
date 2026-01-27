@extends('layouts.adminlte')

@section('content')
    <form method="POST" action="{{ route('inward.orders.store') }}">
        @csrf

        <div class="card">
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible">

                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                {{-- Order Flags --}}
                <div class="row">
                    <div class="col-md-3">
                        <label>Supply Type</label>
                        <select name="supply_type" id="supply_type" class="form-control mb-3">
                            <option value="inward">Inward</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Sub Supply Type</label>
                        <select name="sub_supply_type" class="form-control mb-3">
                            <option value="supply" selected>Supply</option>
                            <option value="export" disabled>Export</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="order_invoice_div" style="display:block;">
                        <div class="form-group">
                            <label>Invoice No.</label>
                            <input type="text"
                                   name="order_invoice_number"
                                   id="order_invoice_number"
                                   class="form-control mb-3">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Document Date</label>
                            <input type="text"
                                   name="order_invoice_date"
                                   id="order_invoice_date"
                                   class="form-control mb-3"
                                   value="{{ old('order_invoice_date', \Carbon\Carbon::parse($defaultDate)->format('d-M-Y')) }}"
                                   placeholder="DD-MMM-YYYY">
                        </div>
                    </div>



                    <div class="col-md-3">
                        <label>Document Type</label>
                        <select name="document_type" class="form-control mb-3">
                            <option value="Tax invoice" selected>Tax invoice</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Vehicle Type</label>
                        <select name="vehicle_type" class="form-control mb-3">
                            <option value="Regular" selected>Regular</option>

                        </select>
                    </div>
                </div>

                <hr>

                {{-- Transport Details --}}
                <div class="row">
                    <div class="col-md-3">
                        <label>Transporter ID</label>
                        <select name="transporter_id" id="transporter_id" class="form-control" required>
                            <option value="" data-name="">Select Transporter</option>
                            @foreach($transporters as $val)
                                <option value="{{ $val->transporter_gstn }}" data-name="{{$val->name}}" {{ $val->transporter_id == '1' ? 'selected' : '' }}>
                                    {{ $val->transporter_gstn }}-{{$val->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Transporter Name</label>
                            <input type="text"
                                   name="transporter_name"
                                   id="transporter_name"
                                   class="form-control mb-3" value="NO DETAIL">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Vehicle No</label>
                            <input type="text"
                                   name="vehicle_no"
                                   id="vehicle_no"
                                   class="form-control mb-3">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Transporter Document No</label>
                            <input type="text"
                                   name="transporter_document_no"
                                   id="transporter_document_no"
                                   class="form-control mb-3">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Transportation Doc Date</label>
                            <input type="text"
                                   name="transportation_date"
                                   id="transportation_date"
                                   class="form-control mb-3"
                                   placeholder="DD-MMM-YYYY">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>Transportation Mode</label>
                        <select name="transportation_mode" class="form-control mb-3">
                            <option value="Road" selected>Road</option>
                            <option value="Air" disabled>Air</option>
                            <option value="Rail" disabled>Rail</option>
                            <option value="Ship" disabled >Ship+</option>

                        </select>
                    </div>
                </div>

                <hr>
                <div class="row">

                    <!-- BILL FROM -->
                    <div class="col-md-6">
                        <div class="card card-outline card-primary">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-file-invoice mr-1"></i> Bill From
                                </h6>
                            </div>

                            <div class="card-body p-3">
                                <div class="form-group position-relative">
                                    <label class="small text-muted">Party</label>
                                    <input type="text"
                                           class="form-control party-search"
                                           placeholder="Search Party / GSTN"
                                           data-target="bill_from_address_id"
                                           data-party-input="bill_from_party_id"
                                           value="{{$billFromParty->party_trade_name}}-{{$billFromParty->party_gstn}}" >

                                    <input type="hidden"
                                           name="bill_from_party_id"
                                           id="bill_from_party_id"
                                           value="{{$billFromParty->party_id}}"
                                    >
                                </div>

                                <div class="form-group mb-0">
                                    <label class="small text-muted">Address</label>
                                    <select name="bill_from_address_id"
                                            id="bill_from_address_id"
                                            class="form-control">
                                        <option value="{{$billFromAddress->address_id}}" selected>{{$billFromAddress->address_line}}-{{$billFromAddress->city}} {{$billFromAddress->state}}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- BILL TO -->
                    <div class="col-md-6">
                        <div class="card card-outline card-info">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-file-invoice-dollar mr-1"></i> Bill To
                                </h6>
                            </div>

                            <div class="card-body p-3">
                                <div class="form-group position-relative">
                                    <label class="small text-muted">Party</label>
                                    <input type="text"
                                           class="form-control party-search"
                                           placeholder="Search Party / GSTN"
                                           data-target="bill_to_address_id"
                                           data-party-input="bill_to_party_id">

                                    <input type="hidden"
                                           name="bill_to_party_id"
                                           id="bill_to_party_id" required>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="small text-muted">Address</label>
                                    <select name="bill_to_address_id"
                                            id="bill_to_address_id"
                                            class="form-control" required>
                                        <option value="">Select Party First</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- SHIP TO -->
                    <div class="col-md-6">
                        <div class="card card-outline card-success">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-shipping-fast mr-1"></i> Ship To
                                </h6>
                            </div>

                            <div class="card-body p-3">
                                <div class="form-group position-relative">
                                    <label class="small text-muted">Party</label>
                                    <input type="text"
                                           class="form-control party-search"
                                           placeholder="Search Party / GSTN"
                                           data-target="ship_to_address_id"
                                           data-party-input="ship_to_party_id">

                                    <input type="hidden"
                                           name="ship_to_party_id"
                                           id="ship_to_party_id">
                                </div>

                                <div class="form-group mb-0">
                                    <label class="small text-muted">Address</label>
                                    <select name="ship_to_address_id"
                                            id="ship_to_address_id"
                                            class="form-control">
                                        <option value="">Select Party First</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dispatch From -->
                    <div class="col-md-6">
                        <div class="card card-outline card-success">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-shipping-fast mr-1"></i> Dispatch From
                                </h6>
                            </div>

                            <div class="card-body p-3">
                                <div class="form-group position-relative">
                                    <label class="small text-muted">Party</label>
                                    <input type="text"
                                           class="form-control party-search"
                                           placeholder="Search Party / GSTN"
                                           data-target="dispatch_from_address_id"
                                           data-party-input="dispatch_from_party_id">

                                    <input type="hidden"
                                           name="dispatch_from_party_id"
                                           id="dispatch_from_party_id">
                                </div>

                                <div class="form-group mb-0">
                                    <label class="small text-muted">Address</label>
                                    <select name="dispatch_from_address_id"
                                            id="dispatch_from_address_id"
                                            class="form-control">
                                        <option value="">Select Party First</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>


            </div>

            <div class="card-footer text-right">
                <button class="btn btn-success px-4">
                    <i class="fas fa-save"></i> Save Order
                </button>
            </div>
        </div>
    </form>

@endsection

@section('style')
    <style>
        .party-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            max-height: 220px;
            overflow-y: auto;
        }

    </style>
@endsection
@section('scripts')

    <script>

        $(document).ready(function () {
            $('#supply_type').on('change', function () {
                if ($(this).val() === 'inward') {
                    $('#order_invoice_div').slideDown();
                } else {
                    $('#order_invoice_div').slideUp();
                    $('#order_invoice_number').val(''); // clear value
                }
            });
        });
        $(document).ready(function () {

            $('[name="transporter_id"]').on('change', function () {
                const name = $(this).find('option:selected').data('name') || '';
                $('#transporter_name').val(name);
            });


        });
    </script>
    <script>
        $(document).ready(function () {

            let typingTimer;

            // 🔍 Party search
            $('.party-search').on('keyup', function () {

                clearTimeout(typingTimer);

                let input = $(this);
                let query = input.val();

                if (query.length < 2) return;

                typingTimer = setTimeout(function () {

                    $.get("{{ route('party.search') }}", { q: query }, function (data) {

                        let list = '<ul class="list-group party-list">';
                        data.forEach(party => {
                            list += `
                        <li class="list-group-item party-item"
                            data-party-id="${party.party_id}"
                            data-company-id="${party.company_id}">
                            ${party.party_trade_name}
                            <small class="text-muted">(${party.party_gstn})</small>
                        </li>`;
                        });
                        list += '</ul>';

                        input.next('.party-list').remove();
                        input.after(list);
                    });

                }, 400);
            });

            // ✅ Select party
            $(document).on('click', '.party-item', function () {

                let partyId   = $(this).data('party-id');
                let companyId = $(this).data('company-id');

                let searchInput   = $(this).closest('.party-list').prev('.party-search');
                let targetSelect  = searchInput.data('target');
                let partyInputId  = searchInput.data('party-input');

                // set visible input
                searchInput.val($(this).text().trim());

                // save party_id
                $('#' + partyInputId).val(partyId);

                $('.party-list').remove();

                // load addresses by company
                loadCompanyAddressesByCompany(companyId,partyId, targetSelect);
            });

            // Close dropdown
            $(document).click(function (e) {
                if (!$(e.target).closest('.party-search, .party-list').length) {
                    $('.party-list').remove();
                }
            });

        });

        // 📌 Load addresses by company
        function loadCompanyAddressesByCompany(companyId,partyId, targetSelectId) {

            let select = $('#' + targetSelectId);
            select.html('<option>Loading...</option>');

            $.ajax({
                url: `/orders/company-addresses/${companyId}/${partyId}`,
                type: 'GET',
                success: function (data) {

                    let options = '<option value="">Select Address</option>';

                    if (!data.length) {
                        options += '<option value="">No address found</option>';
                    }

                    data.forEach(addr => {
                        options += `
                    <option value="${addr.address_id}">
                        ${addr.address_line}, ${addr.city}, ${addr.state} - ${addr.pincode}
                    </option>`;
                    });

                    select.html(options);
                },
                error: function () {
                    select.html('<option>Error loading addresses</option>');
                }
            });
        }
    </script>
    <script>
        $(document).ready(function () {

            $('form').on('submit', function (e) {

                let isValid = true;
                $('.is-invalid').removeClass('is-invalid');

                const fields = [
                    { id: '#transporter_id', message: 'Select Transporter' },
                    { id: '#bill_from_party_id', message: 'Select Bill From Party' },
                    { id: '#bill_from_address_id', message: 'Select Bill From Address' },
                    { id: '#bill_to_party_id', message: 'Select Bill To Party' },
                    { id: '#bill_to_address_id', message: 'Select Bill To Address' },
                    { id: '#order_invoice_date', message: 'Select Invoice Date' }
                ];
                if (
                    $('#supply_type').val().toLowerCase() === 'inward' &&
                    !$('#order_invoice_number').val().trim()
                ) {
                    fields.push({
                        id: '#order_invoice_number',
                        message: 'Enter Order Invoice Number'
                    });
                }
                if ($('#transporter_id').val().toLowerCase() === 'no_gstn' ) {
                    fields.push({
                        id: '#vehicle_no',
                        message: 'Please enter vehicle no'
                    });
                }
                var vehicleNo = $('#vehicle_no').val().trim();
                if (vehicleNo !== '') {
                    var vehicleRegex = /^[A-Z]{2}[ -]?[0-9]{1,2}[ -]?[A-Z]{1,3}[ -]?[0-9]{4}$/;

                    if (!vehicleRegex.test(vehicleNo.toUpperCase())) {
                        fields.push({
                            id: '#vehicle_no',
                            message: 'Please enter a valid Indian vehicle number'
                        });
                    }
                }

                fields.forEach(field => {
                    let el = $(field.id);

                    if (!el.val()) {
                        el.addClass('is-invalid');
                        el.closest('.form-group').find('.invalid-feedback').remove();
                        el.closest('.form-group').append(`
                    <div class="invalid-feedback">${field.message}</div>
                `);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    toastr.error('Please fill all required fields');
                }
            });

        });


    </script>
    <script>
        $(document).ready(function () {

            function handleOutwardDateRule() {
                let supplyType = $('#supply_type').val();
                let today = $('#order_invoice_date').data('today');

                if (supplyType === 'outward') {
                    $('#order_invoice_date')
                        .val(today)
                        .attr('min', today);
                } else {
                    $('#order_invoice_date').removeAttr('min');
                }
            }

            $('#supply_type').on('change', handleOutwardDateRule);
            handleOutwardDateRule(); // page load
        });
    </script>

    <script>
        flatpickr("#order_invoice_date", {
            dateFormat: "d-M-Y",
            allowInput: true,
            minDate: new Date(
                {{ \Carbon\Carbon::parse($latestDate)->year }},
                {{ \Carbon\Carbon::parse($latestDate)->month - 1 }},
                {{ \Carbon\Carbon::parse($latestDate)->day }}
            ),

            defaultDate: "{{ \Carbon\Carbon::parse($defaultDate)->format('d-M-Y') }}"
        });


    </script>
    <script>
        flatpickr("#transportation_date", {
            dateFormat: "d-M-Y",   // 10-Jan-2025
            allowInput: true
        });
    </script>
@endsection
