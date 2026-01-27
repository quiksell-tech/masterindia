@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        <div class="card card-primary card-outline">

            {{-- Header --}}
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice mr-1"></i>
                   Inward Orders
                </h3>

                <a href="{{ route('inward.orders.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Add Inward Order
                </a>
            </div>

            {{-- Body --}}
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-bordered mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 80px">ID</th>
                        <th>Invoice No.</th>
                        <th>Qty.</th>
                        <th>Total Sale</th>
                        <th>Total Tax</th>
                        <th>Total After Tax</th>
                        <th style="width: 140px">Invoice Date</th>
                        <th style="width: 100px">Status</th>
                        <th style="width: 100px">Edit</th>
                        <th colspan="2" class="text-center" style="width: 100px;">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->order_id }}</td>

                            <td>
                                <strong>{{ $order->order_invoice_number }}</strong>
                            </td>

                            <td>
                                <strong>{{ count($order->items) }}</strong>
                            </td>

                            <td>
                                <strong>{{ $order->total_sale_value }}</strong>
                            </td>

                            <td>
                                <strong>{{ $order->total_tax }}</strong>
                            </td>
                            <td>
                                <strong>{{ $order->total_after_tax }}</strong>
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($order->order_invoice_date)->format('d-M-Y') }}
                            </td>

                            <td>
                            <span class="badge-{{ $order->is_active === 'Y' ? 'success' : 'danger' }}">
                                {{ $order->is_active === 'Y' ? 'Active' : 'Inactive' }}
                            </span>
                            </td>

                            <td>
                                <a href="{{ route('inward.orders.edit', $order->order_id) }}"
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>

                                    <td>
                                        <button type="button"
                                                class="btn btn-sm btn-info"
                                                onclick="getInvoiceData({{ $order->order_id }})">
                                            <i class="fas fa-file-invoice"></i> view
                                        </button>

                                    <a href="{{ route('inward.order.invoice.pdf', $order->order_id) }}"
                                       class="btn btn-sm btn-danger">
                                        <i class="fas fa-file-pdf"></i> Tax PDF
                                    </a>
                                        @if(!empty($order->einvoice_pdf_url))
                                            <a href="{{ $order->einvoice_pdf_url }}" class="btn btn-sm btn-danger" target="_blank">
                                                <i class="fas fa-file-pdf"></i> E-Invoice PDF
                                            </a>
                                        @endif

                                    @if(!empty($order->eway_bill_pdf_url))
                                            <a href="https://{{ $order->eway_bill_pdf_url }}" class="btn btn-sm btn-danger" target="_blank">
                                                <i class="fas fa-file-pdf"></i> E-Way Bill PDF
                                            </a>
                                        @endif

                                    </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted p-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                No orders found
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <div class="card-footer clearfix">
                <nav class="float-right">
                    {{ $orders->onEachSide(1)->links('pagination::bootstrap-4') }}
                </nav>
            </div>

        </div>
    </div>
    <!-- Invoice Modal -->
    <div class="modal fade" id="invoiceModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content shadow">

                <!-- Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-file-invoice mr-1"></i> Invoice Preview
                    </h5>
                </div>

                <!-- Body -->
                <div class="modal-body">

                    <!-- Invoice Summary -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>Invoice Information</strong>
                        </div>
                        <div class="card-body">
                            <div class="row small">
                                <div class="col-md-4"><strong>Invoice No:</strong> <span id="inv_invoice_no"></span></div>
                                <div class="col-md-4"><strong>Date:</strong> <span id="inv_invoice_date"></span></div>
                                <div class="col-md-4">
                                    <strong>Supply Type:</strong>
                                    <span class="badge badge-info" id="inv_supply_type"></span>
                                </div>

                                <div class="col-md-4 mt-2"><strong>Sub Supply Type:</strong> <span id="inv_sub_supply_type"></span></div>
                                <div class="col-md-4 mt-2"><strong>Document Type:</strong> <span id="inv_document_type"></span></div>
                                <div class="col-md-4 mt-2"><strong>Transport Mode:</strong> <span id="inv_transport_mode"></span></div>

                                <div class="col-md-4 mt-2"><strong>Vehicle No:</strong> <span id="inv_vehicle_no"></span></div>
                                <div class="col-md-4 mt-2"><strong>Transporter ID:</strong> <span id="inv_transporter_id"></span></div>
                                <div class="col-md-4 mt-2"><strong>Transporter Name:</strong> <span id="inv_transporter_name"></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Amounts -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>Amount Summary</strong>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <h6>Total Sale Value</h6>
                                    <h5 class="text-primary">₹ <span id="inv_total_sale"></span></h5>
                                </div>
                                <div class="col-md-4">
                                    <h6>Total Tax</h6>
                                    <h5 class="text-warning">₹ <span id="inv_total_tax"></span></h5>
                                </div>
                                <div class="col-md-4">
                                    <h6>Total After Tax</h6>
                                    <h5 class="text-success font-weight-bold">
                                        ₹ <span id="inv_total_after_tax"></span>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Addresses -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <strong>Party & Address Details</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Bill From</h6>
                                    <div id="inv_bill_from" class="border rounded p-3 bg-light small"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Bill To</h6>
                                    <div id="inv_bill_to" class="border rounded p-3 bg-light small"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Ship To</h6>
                                    <div id="inv_ship_to" class="border rounded p-3 bg-light small"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Dispatch From</h6>
                                    <div id="inv_dispatch_from" class="border rounded p-3 bg-light small"></div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer bg-light">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            onclick="$('#invoiceModal').modal('hide')">
                        Cancel
                    </button>

                </div>

            </div>
        </div>
    </div>


@endsection
@section('scripts')
    <script>

        function getInvoiceData(orderId)
        {
            // Show modal first
            $('#invoiceModal').modal('show');

            // AJAX call to fetch invoice data
            fetch(`/orders/${orderId}/invoice-data`)
                .then(res => res.json())
                .then(data => {

                    document.getElementById('inv_invoice_no').innerText = data.order_invoice_number;
                    document.getElementById('inv_invoice_date').innerText = data.order_invoice_date;
                    document.getElementById('inv_supply_type').innerText = data.supply_type;
                    document.getElementById('inv_sub_supply_type').innerText = data.sub_supply_type;
                    document.getElementById('inv_document_type').innerText = data.document_type;

                    document.getElementById('inv_transport_mode').innerText = data.transportation_mode;
                    document.getElementById('inv_vehicle_no').innerText = data.vehicle_no;
                    document.getElementById('inv_transporter_id').innerText = data.transporter_id;
                    document.getElementById('inv_transporter_name').innerText = data.transporter_name;

                    document.getElementById('inv_total_sale').innerText = data.total_sale_value;
                    document.getElementById('inv_total_tax').innerText = data.total_tax;
                    document.getElementById('inv_total_after_tax').innerText = data.total_after_tax;

                    document.getElementById('inv_bill_from').innerHTML = data.bill_from;
                    document.getElementById('inv_bill_to').innerHTML = data.bill_to;
                    document.getElementById('inv_ship_to').innerHTML = data.ship_to;
                    document.getElementById('inv_dispatch_from').innerHTML = data.dispatch_from;

                    // Generate invoice URL
                    document.getElementById('generateInvoiceBtn').href =
                        `/orders/${orderId}/generate-invoice`;
                });
        }
    </script>

@endsection
