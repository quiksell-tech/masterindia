@extends('layouts.adminlte')

@section('content')
    <h4 class="text-success">Part A : Order Detail</h4>
    <form method="POST" action="{{ route('orders.update', $order->order_id) }}">
        @csrf

        <div class="card">
            <div class="card-body">

                {{-- Order Flags --}}
                <div class="row">
                    <div class="col-md-3">
                        <label>Supply Type</label>
                        <select name="supply_type" id="supply_type" class="form-control mb-3">
                            <option value="outward" {{ $order->supply_type == 'outward' ? 'selected' : '' }}>Outward</option>
                            <option value="inward" {{ $order->supply_type == 'inward' ? 'selected' : '' }}>Inward</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Sub Supply Type</label>
                        <select name="sub_supply_type" class="form-control mb-3">
                            <option value="supply" {{ $order->sub_supply_type == 'supply' ? 'selected' : '' }}>Supply</option>
                            <option value="export" {{ $order->sub_supply_type == 'export' ? 'selected' : '' }}>Export</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="order_invoice_div" >
                        <div class="form-group">
                            <label>Invoice No.</label>
                            <input type="text"
                                   name="order_invoice_number"
                                   id="order_invoice_number"
                                   class="form-control mb-3" value="{{$order->order_invoice_number}}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Document Date</label>
                            <input type="date"
                                   name="order_invoice_date"
                                   id="order_invoice_date"
                                   class="form-control mb-3"
                                   value="{{$order->order_invoice_date}}">
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
                                <option value="{{$val->transporter_gstn }}" data-name="{{$val->name}}" {{ $val->transporter_gstn == $order->transporter_id ? 'selected' : '' }} >
                                    {{ $val->transporter_gstn }}-{{$val->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>

                     <div class="col-md-3">
                        <div class="form-group">
                            <label>Vehicle No</label>
                            <input type="text"
                                   name="transporter_name"
                                   id="transporter_name"
                                   class="form-control mb-3"
                                   value="{{$order->transporter_name}}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Vehicle No</label>
                            <input type="text"
                                   name="vehicle_no"
                                   id="vehicle_no"
                                   class="form-control mb-3"
                                   value="{{$order->vehicle_no}}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>Transportation Mode</label>
                        <select name="transportation_mode" class="form-control mb-3">
                            <option value="Road" {{ $order->transportation_mode == 'Road' ? 'selected' : '' }}>Road</option>
                            <option value="Air" {{ $order->transportation_mode == 'Air' ? 'selected' : '' }}>Air</option>
                            <option value="Rail" {{ $order->transportation_mode == 'Rail' ? 'selected' : '' }}>Rail</option>
                            <option value="Ship" {{ $order->transportation_mode == 'Ship' ? 'selected' : '' }}>Ship+</option>

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
                                <div class="form-group">
                                    <label class="small text-muted">Party</label>
                                    <input type="text"
                                           class="form-control party-search"
                                           placeholder="Search Party / GSTN"
                                           data-target="bill_from_address_id"
                                           data-party-input="bill_from_party_id" value="{{ $order->billFromParty->party_trade_name ?? '-' }}">

                                    <input type="hidden"
                                           name="bill_from_party_id"
                                           id="bill_from_party_id" value="{{$order->bill_from_party_id}}">
                                </div>

                                <div class="form-group mb-0">
                                    <label class="small text-muted">Address</label>
                                    <select name="bill_from_address_id"
                                            id="bill_from_address_id"
                                            class="form-control" >
                                        <option value="{{$order->bill_from_address_id}}">{{ $order->billFromAddress->address_line ?? '-' }}</option>
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
                                <div class="form-group">
                                    <label class="small text-muted">Party</label>
                                    <input type="text"
                                           class="form-control party-search"
                                           placeholder="Search Party / GSTN"
                                           data-target="bill_to_address_id"
                                           data-party-input="bill_to_party_id"
                                            value="{{ $order->billToParty->party_trade_name ?? '-' }}">

                                    <input type="hidden"
                                           name="bill_to_party_id"
                                           id="bill_to_party_id" value="{{$order->bill_to_party_id}}" required>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="small text-muted">Address</label>
                                    <select name="bill_to_address_id"
                                            id="bill_to_address_id"
                                            class="form-control" required>
                                        <option value="{{$order->bill_to_address_id}}">{{ $order->billToAddress->address_line ?? '-' }}</option>
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
                                <div class="form-group">
                                    <label class="small text-muted">Party</label>
                                    <input type="text"
                                           class="form-control party-search"
                                           placeholder="Search Party / GSTN"
                                           data-target="ship_to_address_id"
                                           data-party-input="ship_to_party_id" value="{{ $order->shipToParty->party_trade_name ?? '-' }}">

                                    <input type="hidden"
                                           name="ship_to_party_id"
                                           id="ship_to_party_id" value="{{$order->ship_to_party_id}}">
                                </div>

                                <div class="form-group mb-0">
                                    <label class="small text-muted">Address</label>
                                    <select name="ship_to_address_id"
                                            id="ship_to_address_id"
                                            class="form-control">
                                        <option value="{{$order->ship_to_address_id}}">{{ $order->shipToAddress->address_line ?? '-' }}</option>
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
                                <div class="form-group">
                                    <label class="small text-muted">Party</label>
                                    <input type="text"
                                           class="form-control party-search"
                                           placeholder="Search Party / GSTN"
                                           data-target="dispatch_from_address_id"
                                           data-party-input="dispatch_from_party_id" value="{{ $order->dispatchFromParty->party_trade_name ?? '-' }}">

                                    <input type="hidden"
                                           name="dispatch_from_party_id"
                                           id="dispatch_from_party_id" value="{{$order->dispatch_from_party_id}}">
                                </div>

                                <div class="form-group mb-0">
                                    <label class="small text-muted">Address</label>
                                    <select name="dispatch_from_address_id"
                                            id="dispatch_from_address_id"
                                            class="form-control">
                                        <option value="{{$order->dispatch_from_address_id}}">{{ $order->dispatchFromAddress->address_line ?? '-' }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>


            </div>

            <div class="card-footer text-right">
                <button class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Order
                </button>
            </div>
        </div>
    </form>

    <hr>
    <h4 class="text-success">Part B : Order Items</h4>

    <form method="POST" action="{{ route('orders.items.save', $order->order_id) }}">
        @csrf

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="bg-light">
                <tr>
                    <th width="220">Item</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Price/Unit</th>
                    <th>Taxable Amount</th>
                    <th>After_tax_val</th>

                    <th width="60">Action</th>
                </tr>
                </thead>

                <tbody id="itemRows">

                {{-- Existing Items --}}
                @foreach($items as $i => $item)
                    <tr>
                        <td>
                            <select name="items[{{ $i }}][item_id]" class="form-control item-select">
                                <option value="">Select Item</option>
                                @foreach($allItems as $it)
                                    <option value="{{ $it->item_id }}"
                                            data-tax_percentage="{{ $it->tax_percentage }}"
                                        {{ $it->item_id == $item->item_id ? 'selected' : '' }}>
                                        {{ $it->item_name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td>
                            <input name="items[{{ $i }}][total_item_quantity]"
                                   class="form-control qty"
                                   type="number" step="any"
                                   value="{{ $item->total_item_quantity }}">
                        </td>

                        <td>
                            <select name="items[{{ $i }}][item_unit]" class="form-control">
                                <option value="pieces" {{ $item->item_unit == 'pieces' ? 'selected' : '' }}>Pieces</option>
                                <option value="kg" {{ $item->item_unit == 'kg' ? 'selected' : '' }}>Kg</option>
                            </select>
                        </td>

                        <td>
                            <input name="items[{{ $i }}][tax_per_unit]"
                                   class="form-control rate"
                                   type="number" step="any"
                                   value="{{ $item->price_per_unit }}">
                        </td>

                        <td>
                            <input name="items[{{ $i }}][taxable_amount]"
                                   class="form-control taxable_amount"
                                   readonly
                                   value="{{ $item->taxable_amount }}" >
                        </td>

                        <td>
                            <input name="items[{{ $i }}][after_tax_value]"
                                   class="form-control after_tax_value"
                                   readonly
                                   value="{{ $item->after_tax_value }}" >
                        </td>

                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-row">×</button>
                        </td>
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>

        <div class="mt-2">
            <button type="button" class="btn btn-primary btn-sm" id="addRow">
                <i class="fas fa-plus"></i> Add Item
            </button>


            <div class="mt-3 d-flex justify-content-end">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fas fa-save"></i> Save Items
                </button>
            </div>
        </div>
    </form>

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

                        let list = '<ul class="list-group position-absolute w-10 party-list">';
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
                loadCompanyAddressesByCompany(companyId, targetSelect);
            });

            // Close dropdown
            $(document).click(function (e) {
                if (!$(e.target).closest('.party-search, .party-list').length) {
                    $('.party-list').remove();
                }
            });

        });

        // 📌 Load addresses by company
        function loadCompanyAddressesByCompany(companyId, targetSelectId) {

            let select = $('#' + targetSelectId);
            select.html('<option>Loading...</option>');

            $.ajax({
                url: `/orders/company-addresses/${companyId}`,
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
        let rowIndex = {{ count($items) }};

        // ADD ROW
        document.getElementById('addRow').addEventListener('click', function () {

            let row = `
    <tr>
        <td>
            <select name="items[${rowIndex}][item_id]" class="form-control item-select">
                <option value="">Select Item</option>
                @foreach($allItems as $it)
            <option value="{{ $it->item_id }}"
                        data-tax_percentage="{{ $it->tax_percentage }}">
                        {{ $it->item_name }}
            </option>
@endforeach
            </select>
        </td>

        <td>
            <input name="items[${rowIndex}][total_item_quantity]"
                   class="form-control qty" type="number" step="any">
        </td>

        <td>
            <select name="items[${rowIndex}][item_unit]" class="form-control">
                <option value="pieces">Pieces</option>
                <option value="kg">Kg</option>
            </select>
        </td>

        <td>
            <input name="items[${rowIndex}][tax_per_unit]"
                   class="form-control rate" type="number" step="any">
        </td>

        <td>
            <input name="items[${rowIndex}][taxable_amount]"
                   class="form-control taxable_amount" readonly>
        </td>

        <td>
            <input name="items[${rowIndex}][after_tax_value]"
                   class="form-control after_tax_value" readonly>
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm remove-row">×</button>
        </td>
    </tr>`;

            document.getElementById('itemRows').insertAdjacentHTML('beforeend', row);
            rowIndex++;
        });

        // REMOVE ROW
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('tr').remove();
            }
        });

        // CALCULATION (EVENT DELEGATION)
        document.addEventListener('input', function (e) {

            if (
                e.target.classList.contains('qty') ||
                e.target.classList.contains('rate') ||
                e.target.classList.contains('item-select')
            ) {
                let row = e.target.closest('tr');

                let qty  = parseFloat(row.querySelector('.qty')?.value) || 0;
                let rate = parseFloat(row.querySelector('.rate')?.value) || 0;

                let itemSelect = row.querySelector('.item-select');
                let taxPercent = parseFloat(
                    itemSelect?.selectedOptions[0]?.dataset.tax_percentage
                ) || 0;

                let taxableAmount = qty * rate;
                let taxAmount = (taxableAmount * taxPercent) / 100;
                let afterTaxValue = taxableAmount + taxAmount;

                row.querySelector('.taxable_amount').value = taxableAmount.toFixed(2);
                row.querySelector('.after_tax_value').value = afterTaxValue.toFixed(2);
            }
        });


    </script>


@endsection
