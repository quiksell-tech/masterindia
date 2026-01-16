@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        {{-- Order / Credit Note Details --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Credit Note Details <span>#{{ $creditnote->order_id }}</span></h3>
            </div>

            <div class="card-body row">
                <div class="col-md-4">
                    <label>Credit Note No.</label>
                    <input type="text" class="form-control" value="{{ $creditnote->creditnote_invoice_no }}" readonly>
                </div>

                <div class="col-md-4">
                    <label>E-Invoice No</label>
                    <input type="text" class="form-control" value="{{ $creditnote->einvoice_no }}" readonly>
                </div>

                <div class="col-md-4">
                    <label>Invoice No</label>
                    <input type="text" class="form-control" value="{{ $creditnote->order_invoice_number }}" readonly>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <form method="POST" action="{{ route('creditnote.store', $creditnote->creditnote_id) }}">
            @csrf
            {{-- Credit Note Form Fields --}}
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Credit Note Information</h3>
                </div>

                <div class="card-body row">

                    {{-- Credit Note Date --}}
{{--                    <div class="col-md-3">--}}
{{--                        <div class="form-group">--}}
{{--                            <label>Return Date</label>--}}

{{--                            <input type="text"--}}
{{--                                   name="creditnote_invoice_no"--}}
{{--                                   id="creditnote_invoice_no"--}}
{{--                                   class="form-control"--}}
{{--                                   value="{{ old('return_date', optional($creditnote->credit_note_date)->format('d-M-Y')) }}"--}}
{{--                                   placeholder="DD-MMM-YYYY"--}}
{{--                                   required>--}}
{{--                        </div>--}}
{{--                    </div>--}}

                    {{-- Credit Note Date --}}
                    <div class="col-md-3">

                        <div class="form-group">
                            <label>Return Date</label>

                            <input type="text"
                                   name="return_date"
                                   id="return_date"
                                   class="form-control"
                                   value="{{ old('return_date', optional($creditnote->return_date)->format('d-M-Y')) }}"
                                   placeholder="DD-MMM-YYYY"
                                   required>
                        </div>
                    </div>

                    {{-- GST Invoice No --}}
{{--                    <div class="col-md-3">--}}
{{--                        <div class="form-group">--}}
{{--                            <label>GST Invoice No</label>--}}
{{--                            <input type="text"--}}
{{--                                   name="gst_invoice_no"--}}
{{--                                   class="form-control"--}}
{{--                                   value="{{ old('gst_invoice_no', $creditnote->gst_invoice_no) }}"--}}
{{--                                   placeholder="GST Invoice Number">--}}
{{--                        </div>--}}
{{--                    </div>--}}

                    {{-- Return Type --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Return Type</label>
                            <select name="return_type" class="form-control" required>

                                <option value="SALES_RETURN" {{ $creditnote->return_type === 'SALES_RETURN' ? 'selected' : '' }}>
                                    Sales Return
                                </option>
{{--                                <option value="PARTIAL" {{ $creditnote->return_type === 'PARTIAL' ? 'selected' : '' }}>--}}
{{--                                    Partial Return--}}
{{--                                </option>--}}
                            </select>
                        </div>
                    </div>

                    {{-- Return Date --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Credit Note Date</label>
                            <input type="text"
                                   name="credit_note_date"
                                   id="credit_note_date"
                                   class="form-control"
                                   value="{{ old('credit_note_date', optional($creditnote->credit_note_date)->format('d-M-Y')) }}"
                                   placeholder="DD-MMM-YYYY"
                                   required>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Credit Note Items</h3>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-bordered" id="itemsTable">
                        <thead class="thead-light">
                        <tr>
                            <th>Item</th>
                            <th>Item Code</th>
                            <th>HSN</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Price / Unit</th>
                            <th>Taxable Amount</th>
                            <th>After Tax Value</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($creditnote->items as $index => $item)
                            <tr>
                                <td>
                                    {{ $item->item_name }}
                                    <input type="hidden" name="items[{{ $index }}][item_name]" value="{{ $item->item_name }}">
                                    <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->item_id }}">
                                    <input type="hidden" class="tax_percentage" value="{{ $item->tax_percentage }}">
                                </td>

                                <td>
                                    {{ $item->item_code }}
                                    <input type="hidden" name="items[{{ $index }}][item_code]" value="{{ $item->item_code }}">
                                </td>

                                <td>
                                    {{ $item->hsn_code }}
                                    <input type="hidden" name="items[{{ $index }}][hsn_code]" value="{{ $item->hsn_code }}">
                                </td>

                                <td>
                                    <input type="number"
                                           name="items[{{ $index }}][total_item_quantity]"
                                           class="form-control qty"
                                           value="{{ $item->total_item_quantity }}"
                                           min="1">
                                </td>

                                <td>
                                    {{ $item->item_unit }}
                                    <input type="hidden" name="items[{{ $index }}][item_unit]" value="{{ $item->item_unit }}">
                                </td>

                                <td>
                                    <input type="number"
                                           name="items[{{ $index }}][price_per_unit]"
                                           class="form-control price"
                                           value="{{ $item->price_per_unit }}"
                                           readonly>
                                </td>

                                <td>
                                    <input type="number"
                                           name="items[{{ $index }}][taxable_amount]"
                                           class="form-control taxable_amount"
                                           value="{{ $item->taxable_amount }}"
                                           readonly>
                                </td>

                                <td>
                                    <input type="number"
                                           name="items[{{ $index }}][after_tax_value]"
                                           class="form-control after_tax_value"
                                           value="{{ $item->after_tax_value }}"
                                           readonly>
                                </td>

                                <td>
                                    <button type="button" class="btn btn-danger btn-sm removeRow">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Credit Note
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).on('input', '.qty', function () {

            let row = $(this).closest('tr');

            let qty   = parseFloat(row.find('.qty').val()) || 0;
            let price = parseFloat(row.find('.price').val()) || 0;
            let tax   = parseFloat(row.find('.tax_percentage').val()) || 0;

            let taxable = qty * price;
            let afterTax = taxable + (taxable * tax / 100);

            row.find('.taxable_amount').val(taxable.toFixed(2));
            row.find('.after_tax_value').val(afterTax.toFixed(2));
        });

        // Remove row
        $(document).on('click', '.removeRow', function () {
            $(this).closest('tr').remove();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr("#credit_note_date", {
                dateFormat: "d-M-Y",
                allowInput: true
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr("#return_date", {
                dateFormat: "d-M-Y",
                allowInput: true
            });
        });
    </script>

@endsection

