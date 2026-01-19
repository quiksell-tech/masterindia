@extends('layouts.adminlte')

@section('content')
    <h4 class="text-success">Part A : Order Detail</h4>
    <form method="POST" action="{{ route('orders.update', $order->order_id) }}">
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
                                   {{ $order->supply_type == 'outward' ? 'readonly' : '' }}
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
                                   value="{{ old('order_invoice_date', \Carbon\Carbon::parse($order->order_invoice_date)->format('d-M-Y')) }}"
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
                                <option value="{{$val->transporter_gstn }}" data-name="{{$val->name}}" {{ $val->transporter_gstn == $order->transporter_id ? 'selected' : '' }} >
                                    {{ $val->transporter_gstn }}-{{$val->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>

                     <div class="col-md-3">
                        <div class="form-group">
                            <label>Name</label>
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
                        <div class="form-group">
                            <label>Transporter Document No</label>
                            <input type="text"
                                   name="transporter_document_no"
                                   id="transporter_document_no"
                                   class="form-control mb-3"
                                   value="{{$order->transporter_document_no}}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Transportation Date</label>
                            <input type="date"
                                   name="transportation_date"
                                   id="transportation_date"
                                   class="form-control mb-3"
                                   value="{{ old('transportation_date', \Carbon\Carbon::parse($order->transportation_date)->format('d-M-Y')) }}"
                                   placeholder="DD-MMM-YYYY">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>Transportation Mode</label>
                        <select name="transportation_mode" class="form-control mb-3">
                            <option value="Road" {{ $order->transportation_mode == 'Road' ? 'selected' : '' }}>Road</option>
                            <option value="Air" {{ $order->transportation_mode == 'Air' ? 'selected' : '' }} disabled>Air</option>
                            <option value="Rail" {{ $order->transportation_mode == 'Rail' ? 'selected' : '' }}  disabled>Rail</option>
                            <option value="Ship" {{ $order->transportation_mode == 'Ship' ? 'selected' : '' }} disabled>Ship+</option>

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
                                        <option value="{{$order->bill_from_address_id}}">
                                            {{ $order->billFromAddress->address_line ?? '-' }}
                                            {{ $order->billFromAddress->city ?? '-' }}
                                            {{ $order->billFromAddress->state ?? '-' }}
                                            {{ $order->billFromAddress->pincode ?? '-' }}
                                            {{ $order->billFromParty->party_gstn ?? '-' }}
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
                                        <option value="{{$order->bill_to_address_id}}">
                                            {{ $order->billToAddress->address_line ?? '-' }}
                                            {{ $order->billToAddress->city?? '' }}
                                            {{ $order->billToAddress->state?? '' }}
                                            {{ $order->billToAddress->pincode?? '' }}
                                            {{ $order->bill_to_party_id->party_gstn ?? '-' }}
                                        </option>
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
                                        <option value="{{$order->ship_to_address_id}}">
                                            {{ $order->shipToAddress->address_line ?? '-' }}
                                            {{ $order->shipToAddress->city ?? '-' }}
                                            {{ $order->shipToAddress->state ?? '-' }}
                                            {{ $order->shipToAddress->pincode ?? '-' }}
                                            {{ $order->ship_to_party_id->party_gstn ?? '-' }}
                                        </option>
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
                                        <option value="{{$order->dispatch_from_address_id}}">{{ $order->dispatchFromAddress->address_line ?? '-' }}
                                            {{ $order->dispatchFromAddress->city ?? '-' }}
                                            {{ $order->dispatchFromAddress->state ?? '-' }}
                                            {{ $order->dispatchFromAddress->pincode ?? '-' }}
                                            {{ $order->dispatch_from_party_id->party_gstn ?? '-' }}
                                        </option>
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
                    <th width="220">item_code</th>
                    <th width="220">hsn_code</th>
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
                                            data-item_code="{{ $it->item_code }}"
                                            data-hsn_code="{{ $it->hsn_code }}"
                                        {{ $it->item_id == $item->item_id ? 'selected' : '' }}>
                                        {{ $it->item_name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td>
                            <input name="items[{{ $i }}][item_code]"
                                   class="form-control item_code"
                                   type="text" step="any"
                                   value="{{ $item->item_code }}">
                        </td>
                        <td>
                            <input name="items[{{ $i }}][hsn_code]"
                                   class="form-control hsn_code"
                                   type="text" step="any"
                                   value="{{ $item->hsn_code }}">
                        </td>
                        <td>
                            <input name="items[{{ $i }}][total_item_quantity]"
                                   class="form-control qty"
                                   type="number" step="any"
                                   value="{{ $item->total_item_quantity }}">
                        </td>

                        <td>
                            <select name="items[{{ $i }}][item_unit]" class="form-control">
                                <option value="PCS" {{ $item->item_unit == 'PCS' ? 'selected' : '' }}>PIECES</option>
                                <option value="KGS" {{ $item->item_unit == 'KGS' ? 'selected' : '' }}>KILOGRAMS</option>
                            </select>
                        </td>

                        <td>
                            <input name="items[{{ $i }}][price_per_unit]"
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
            @if(count($items)>0 && $order->transporter_id !='NO_DETAIL' && !empty($order->vehicle_no))
                <div class="card mt-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-truck"></i> Eway Bill Action
                        </h6>

                        <h6 class="card-title mb-0">
                            Eway Bill Status :
                            <span class="badge
                                @if($order->eway_status == 'C') bg-success
                                @elseif($order->eway_status == 'E') bg-danger
                                @elseif($order->eway_status == 'X') bg-secondary
                                @else bg-warning
                                @endif
                                ">
                                @if($order->eway_status == 'C')
                                    CREATED
                                @elseif($order->eway_status == 'E')
                                    ERROR
                                @elseif($order->eway_status == 'X')
                                    CANCELLED
                                @else
                                    NEW
                                @endif
                             </span>

                            @if(!empty($order->eway_status_message))
                                <i class="fas fa-info-circle text-info ml-1"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   title="{{ $order->eway_status_message }}">
                                </i>
                            @endif
                        </h6>
                    </div>


                    <div class="card-body py-2">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('order.invoice.pdf', $order->order_id) }}"
                               class="btn btn-sm btn-danger">
                                <i class="fas fa-file-pdf"></i> Invoice PDF
                            </a>
                            @if($order->eway_status != 'C')
                            <a href="javascript:void(0)" class="btn btn-sm btn-warning" onclick="createEwayBill({{$order->order_id}})">
                                <i class="fas fa-road"></i> Generate E-WayBill
                            </a>
                            @endif
                            @if($order->eway_status == 'C')
                            <a href="javascript:void(0)"
                               class="btn btn-sm btn-info"  onclick="openCancelEwayBillModal('{{$order->order_id}}')">
                                <i class="fas fa-file-invoice"></i> Cancel E-WayBill
                            </a>
                            <a href="javascript:void(0)"
                               class="btn btn-sm btn-info" onclick="openUpdateEwayBillModal('{{$order->order_id}}')">
                                <i class="fas fa-file-invoice"></i> Update E-WayBill
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{--      Einvoice Section        --}}
                <div class="card mt-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-truck"></i> E-Invoice Action
                        </h6>

                        <h6 class="card-title mb-0">
                            EInvoice Status :
                            <span class="badge
                                @if($order->irn_status == 'C') bg-success
                                @elseif($order->irn_status == 'E') bg-danger
                                @elseif($order->irn_status == 'X') bg-secondary
                                @else bg-warning
                                @endif
                                ">
                                @if($order->irn_status == 'C')
                                    CREATED
                                @elseif($order->irn_status == 'E')
                                    ERROR
                                @elseif($order->irn_status == 'X')
                                    CANCELLED
                                @else
                                    NEW
                                @endif
                             </span>

                            @if(!empty($order->irn_status_message))
                                <i class="fas fa-info-circle text-info ml-1"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   title="{{ $order->irn_status_message }}">
                                </i>
                            @endif
                        </h6>
                    </div>


                    <div class="card-body py-2">
                        <div class="d-flex justify-content-end gap-2">
                            @if($order->irn_status != 'C')
                            <a href="javascript:void(0)"
                               class="btn btn-sm btn-warning"
                               onclick="createEInvoice({{$order->order_id}})">
                                <i class="fas fa-road"></i> Generate E-Invoice
                            </a>
                            @endif
                            @if($order->irn_status == 'C')
                                <a href="javascript:void(0)"
                                   class="btn btn-sm btn-warning"
                                   onclick="createEInvoiceCreditNote({{$order->order_id}})">
                                    <i class="fas fa-road"></i> Generate Credit Note
                                </a>

                                <a href="javascript:void(0)"
                                   class="btn btn-sm btn-info"
                                   onclick="openCancelEInvoiceModal('{{ $order->order_id }}')">
                                    <i class="fas fa-file-invoice"></i> Cancel E-Invoice
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

            @endif
        </div>
    </form>

{{--    Eway Bill Modal--}}
    <div class="modal fade" id="ewayBillModal" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="ewayBillModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="ewayBillForm">
                    @csrf
                    <input type="hidden" id="order_id">
                    <input type="hidden" id="action_type">

                    <div class="modal-body">

                        {{-- CANCEL FIELDS --}}
                        <div id="cancelFields" class="d-none">
                            <div class="mb-3">
                                <label>Cancel Reason</label>
                                <select class="form-control" name="cancel_reason" >
                                    <option value="">Select Reason</option>
                                    @foreach(config('ewaybill.cancellation_reasons') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Cancel Remark</label>
                                <textarea class="form-control" name="cancel_remark"></textarea>
                            </div>
                        </div>

                        {{-- UPDATE FIELDS --}}
                        <div id="updateFields" class="d-none">

                            <div class="mb-3">
                                <label>Update Action</label>
                                <select class="form-control" name="action" onchange="toggleUpdateFields(this.value)">
                                    <option value="">Select</option>
                                    <option value="update-vehicle">Update Vehicle</option>
                                    <option value="update-transporter">Update Transporter</option>
                                    <option value="extend-validity">Extend Validity</option>
                                </select>
                            </div>

                            <div id="extendValidityFields" class="d-none">
                                <div class="mb-3">
                                    <label>Extension Reason</label>
                                    <select class="form-control" name="extension_reason">
                                        <option value="">Select</option>
                                        <option value="natural-calamity">Natural Calamity</option>
                                        <option value="law-order">Law & Order</option>
                                        <option value="transshipment">Transshipment</option>
                                        <option value="accident">Accident</option>
                                        <option value="others">Others</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label>Extension Remarks</label>
                                    <textarea class="form-control" name="extension_remarks"></textarea>
                                </div>
                            </div>

                            <div id="vehicleUpdateFields" class="d-none">
                                <div class="mb-3">
                                    <label>Vehicle Update Reason</label>
{{--                                    <input type="text" class="form-control" name="vehicle_update_reason">--}}
                                    <select class="form-control" name="vehicle_update_reason">
                                        <option value="">Select Reason</option>
                                        @foreach(config('ewaybill.vehicle_update_reasons') as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label>Vehicle Update Remarks</label>
                                    <textarea class="form-control" name="vehicle_update_remarks"></textarea>
                                </div>
                            </div>
                            <div id="transporterUpdateFields" class="d-none">

                                <div class="mb-3">
                                    <label>Transporter ID</label>
                                    <input type="text"
                                           class="form-control"
                                           name="transporter_id">
                                </div>

                                <div class="mb-3">
                                    <label>Transporter Name</label>
                                    <input type="text"
                                           class="form-control"
                                           name="transporter_name">
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            Submit
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- Cancel E-Invoice Modal -->
    <div class="modal fade" id="cancelEInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="cancelEInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <form id="cancelEInvoiceForm">
                @csrf

                <input type="hidden" name="einvoice_order_id" id="einvoice_order_id">

                <div class="modal-content">
                    <div class="modal-header bg-info">
                        <h5 class="modal-title" id="cancelEInvoiceModalLabel">
                            <i class="fas fa-file-invoice"></i> Cancel E-Invoice
                        </h5>

                    </div>

                    <div class="modal-body">
                        <!-- Cancel Reason -->
                        <div class="form-group">
                            <label for="cancel_reason">
                                Cancel Reason <span class="text-danger">*</span>
                            </label>
                            <select name="cancel_reason" id="cancel_reason" class="form-control" required>
                                <option value="">-- Select Reason --</option>
                                @foreach(config('einvoice.cancellation_reasons') as $label => $value)
                                    <option value="{{ $value }}">
                                        {{ ucwords(str_replace('-', ' ', $label)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Cancel Remarks -->
                        <div class="form-group">
                            <label for="cancel_remarks">
                                Cancel Remarks <span class="text-danger">*</span>
                            </label>
                            <textarea
                                name="cancel_remarks"
                                id="cancel_remarks"
                                class="form-control"
                                rows="3"
                                maxlength="100"
                                placeholder="Enter remarks (max 100 characters)"
                                required></textarea>
                            <small class="text-muted">
                                <span id="remarks_count">0</span>/100 characters
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="button" class="btn btn-info" id="cancelEinvoiceBtn">
                            <i class="fas fa-ban"></i> Cancel E-Invoice
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


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
        function openCancelEwayBillModal(orderId)
        {
            resetModal();
            $('#ewayBillModalTitle').text('Cancel E-WayBill');
            $('#order_id').val(orderId);
            $('#action_type').val('cancel');
            $('#cancelFields').removeClass('d-none');
            $('#ewayBillModal').modal('show');
        }

        function openUpdateEwayBillModal(orderId)
        {
            resetModal();
            $('#ewayBillModalTitle').text('Update E-WayBill');
            $('#order_id').val(orderId);
            $('#action_type').val('update');
            $('#updateFields').removeClass('d-none');
            $('#ewayBillModal').modal('show');
        }

        function toggleUpdateFields(action)
        {
            $('#extendValidityFields, #vehicleUpdateFields, #transporterUpdateFields')
                .addClass('d-none');

            if (action === 'extend-validity') {
                $('#extendValidityFields').removeClass('d-none');
            }

            if (action === 'update-vehicle') {
                $('#vehicleUpdateFields').removeClass('d-none');
            }

            if (action === 'update-transporter') {
                $('#transporterUpdateFields').removeClass('d-none');
            }
        }

        function resetModal()
        {
            $('#ewayBillForm')[0].reset();
            $('#cancelFields, #updateFields, #extendValidityFields, #vehicleUpdateFields, #transporterUpdateFields').addClass('d-none');
        }

        $('#ewayBillForm').submit(function (e) {
            e.preventDefault();

            $('.is-invalid').removeClass('is-invalid');

            let isValid = true;
            let actionType = $('#action_type').val();

            function markInvalid(selector) {
                $(selector).addClass('is-invalid');
                isValid = false;
            }

            // ---------------- CANCEL VALIDATION ----------------
            if (actionType === 'cancel') {

                if (!$('[name="cancel_reason"]').val()) {
                    markInvalid('[name="cancel_reason"]');
                }

                if (!$('[name="cancel_remark"]').val().trim()) {
                    markInvalid('[name="cancel_remark"]');
                }
            }

            // ---------------- UPDATE VALIDATION ----------------
            if (actionType === 'update') {

                let updateAction = $('[name="action"]').val();

                if (!updateAction) {
                    markInvalid('[name="action"]');
                }

                // ---- Extend Validity ----
                if (updateAction === 'extend-validity') {

                    if (!$('[name="extension_reason"]').val()) {
                        markInvalid('[name="extension_reason"]');
                    }

                    if (!$('[name="extension_remarks"]').val().trim()) {
                        markInvalid('[name="extension_remarks"]');
                    }
                }

                // ---- Update Vehicle ----
                if (updateAction === 'update-vehicle') {

                    if (!$('[name="vehicle_update_reason"]').val()) {
                        markInvalid('[name="vehicle_update_reason"]');
                    }

                    if (!$('[name="vehicle_update_remarks"]').val().trim()) {
                        markInvalid('[name="vehicle_update_remarks"]');
                    }
                }

                // ---- Update Transporter ----
                if (updateAction === 'update-transporter') {

                    if (!$('[name="transporter_id"]').val().trim()) {
                        markInvalid('[name="transporter_id"]');
                    }

                    if (!$('[name="transporter_name"]').val().trim()) {
                        markInvalid('[name="transporter_name"]');
                    }
                }
            }

            // ---------------- STOP IF INVALID ----------------
            if (!isValid) {
                showAjaxResponse({
                    status: 'error',
                    message: 'Please fill all mandatory fields'
                }, 'Validation Error');
                return;
            }
            let orderId = $('#order_id').val();
            // let actionType = $('#action_type').val();
            let url = actionType === 'cancel'
                ? `/api/eway-bill/${orderId}/cancel`
                : `/api/eway-bill/${orderId}/update`;

            $.ajax({
                url: url,
                method: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    showAjaxResponse(response, 'E-Way Bill Update');
                    $('#ewayBillModal').modal('hide');
                },
                error: function (xhr) {
                    showAjaxResponse({
                        status: 'error',
                        message: xhr.responseJSON?.message || 'Validation failed'
                    }, 'Action Failed');
                }
            });
        });
    </script>

    <script>

        $(document).ready(function () {

            $('#supply_type').on('change', function () {

                if ($(this).val() === 'inward') {

                    $('#order_invoice_div').slideDown();

                    $('#order_invoice_number')
                        .prop('readonly', false);
                        //.val(''); // optional: clear auto value

                } else {

                    $('#order_invoice_div').slideUp(); // optional

                    $('#order_invoice_number')
                        .prop('readonly', true);
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
                if ($('#transporter_id').val() != 'NO_DETAIL' ) {
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
                            data-tax_percentage="{{ $it->tax_percentage }}"
                            data-item_code="{{ $it->item_code }}"
                            data-hsn_code="{{ $it->hsn_code }}">
                            {{ $it->item_name }}
            </option>
@endforeach
            </select>
        </td>

        <td>
            <input name="items[${rowIndex}][item_code]"
                       class="form-control item_code" type="text" readonly>
            </td>

            <td>
                <input name="items[${rowIndex}][hsn_code]"
                       class="form-control hsn_code" type="text" readonly>
            </td>

            <td>
                <input name="items[${rowIndex}][total_item_quantity]"
                       class="form-control qty" type="number" step="any">
            </td>

            <td>
                <select name="items[${rowIndex}][item_unit]" class="form-control">
                    <option value="PCS">PIECES</option>
                    <option value="KGS" selected>KILOGRAMS</option>
                </select>
            </td>

            <td>
                <input name="items[${rowIndex}][price_per_unit]"
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

        // CALCULATION + AUTO FILL
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
                let selectedOption = itemSelect?.selectedOptions[0];

                let taxPercent = parseFloat(selectedOption?.dataset.tax_percentage) || 0;
                let itemCode   = selectedOption?.dataset.item_code || '';
                let hsnCode    = selectedOption?.dataset.hsn_code || '';

                let taxableAmount = qty * rate;
                let taxAmount = (taxableAmount * taxPercent) / 100;
                let afterTaxValue = taxableAmount + taxAmount;

                row.querySelector('.taxable_amount').value = taxableAmount.toFixed(2);
                row.querySelector('.after_tax_value').value = afterTaxValue.toFixed(2);
                row.querySelector('.item_code').value = itemCode;
                row.querySelector('.hsn_code').value = hsnCode;
            }
        });
    </script>
    <script>
        flatpickr("#order_invoice_date", {
            dateFormat: "d-M-Y",
            allowInput: true,

            minDate: new Date(
                {{ \Carbon\Carbon::parse($defaultDate)->year }},
                {{ \Carbon\Carbon::parse($defaultDate)->month - 1 }},
                {{ \Carbon\Carbon::parse($defaultDate)->day }}
            ),

            defaultDate: "{{ \Carbon\Carbon::parse($orderInvoiceDate)->format('d-M-Y') }}"
        });


    </script>
    <script>
        flatpickr("#transportation_date", {
            dateFormat: "d-M-Y",   // 10-Jan-2025
            allowInput: true,
            defaultDate:"{{\Carbon\Carbon::parse($order->transportation_date)->format('d-M-Y') }}"
        });


            function createEwayBill(orderId)
            {
                $.ajax({
                    url: "{{ url('api/eway-bill') }}/" + orderId + "/generate",
                    type: "POST",

                    success: function (response) {

                        if (response.status === 'success') {

                            showAjaxResponse(response, 'E-Way Bill Created');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);

                        }else {

                            showAjaxResponse(response, 'E-Way Bill Creation Failed');
                        }
                    },
                    error: function (xhr) {
                        showAjaxResponse({
                            status: 'error',
                            message: xhr.responseJSON?.message || 'Something went wrong'
                        }, 'Action Failed');
                    }
                });
            }


    </script>
    <script>
        // Einvoce Script
        function openCancelEInvoiceModal(orderId) {

            document.getElementById('einvoice_order_id').value = orderId;
            document.getElementById('cancel_reason').value = '';
            document.getElementById('cancel_remarks').value = '';
            document.getElementById('remarks_count').innerText = 0;

            $('#cancelEInvoiceModal').modal('show');
        }

        document.getElementById('cancel_remarks').addEventListener('input', function () {
        document.getElementById('remarks_count').innerText = this.value.length;
        });

        $('#cancelEinvoiceBtn').on('click', function () {

            let orderId = $('#einvoice_order_id').val();

            if (!orderId) {
                showAjaxResponse({
                    status: 'error',
                    message: 'Invalid Order ID'
                }, 'Action Failed');
                return;
            }

            let url = `/api/einvoce/${orderId}/cancel`;
            let btn = $(this);

            // Optional frontend validation
            if (!$('#cancel_reason').val()) {
                showAjaxResponse({
                    status: 'error',
                    message: 'Please select cancel reason'
                }, 'Validation Error');
                return;
            }

            if (!$('#cancel_remarks').val()) {
                showAjaxResponse({
                    status: 'error',
                    message: 'Please enter cancel remarks'
                }, 'Validation Error');
                return;
            }

            btn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Cancelling...');

            $.ajax({
                url: url,
                method: 'POST',
                data: $('#cancelEInvoiceForm').serialize(),
                success: function (response) {
                    showAjaxResponse(response, 'E-Invoice Cancel');

                    if (response.status === 'success') {
                        $('#cancelEInvoiceModal').modal('hide');                        // Optional UI refresh
                        setTimeout(() => location.reload(), 800);
                    }
                },
                error: function (xhr) {
                    showAjaxResponse({
                        status: 'error',
                        message: xhr.responseJSON?.message || 'Validation failed'
                    }, 'Action Failed');
                },
                complete: function () {
                    btn.prop('disabled', false)
                        .html('<i class="fas fa-ban"></i> Cancel E-Invoice');
                }
            });
        });


        function createEInvoice(orderId)
        {
            $.ajax({
                url: "{{ url('api/einvoce') }}/" + orderId + "/generate",
                type: "POST",

                success: function (response) {

                    if (response.status === 'success') {

                        showAjaxResponse(response, 'E-Invoice Action');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);

                    }else {

                        showAjaxResponse(response, 'E-Invoice Action');
                    }
                },
                error: function (xhr) {
                    showAjaxResponse({
                        status: 'error',
                        message: xhr.responseJSON?.message || 'Something went wrong'
                    }, 'Action Failed');
                }
            });
        }


        function createEInvoiceCreditNote(orderId)
        {
            $.ajax({
                url: "{{ url('api/creditnote-data') }}/" + orderId + "/insert",
                type: "POST",

                success: function (response) {

                    if (response.status === 'success') {

                        showAjaxResponse(response, 'CreditNote');
                        setTimeout(() => {
                            window.location.href = "/creditnote/" + response.data.creditnote_id + "/edit";
                        }, 1500);

                    }else {

                        showAjaxResponse(response, 'CreditNote');
                    }
                },
                error: function (xhr) {
                    showAjaxResponse({
                        status: 'error',
                        message: xhr.responseJSON?.message || 'Something went wrong'
                    }, 'Action Failed');
                }
            });
        }
    </script>

@endsection
