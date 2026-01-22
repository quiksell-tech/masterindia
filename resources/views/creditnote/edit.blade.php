@extends('layouts.adminlte')

@section('content')

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3 class="m-0">
                        Credit Note
                        <small class="text-muted">#{{ $creditnote->creditnote_invoice_no }}</small>
                    </h3>
                </div>
                <div class="col-sm-6">
                    <h4 class="m-0">
                       Status:
                        <small class="text-muted">
                            {{
                                match($creditnote->credit_note_status) {
                                    'C' => 'Created',
                                    'X' => 'Cancelled',
                                    'E' => 'Error',
                                    'M' => 'Modified',
                                    default => 'NEW',
                                }
                            }}
                        </small>
                    </h4>
                </div>

                <div class="col-sm-6 text-right">
                <span class="badge badge-info p-2">
                    Order #{{ $creditnote->order_id }}
                </span>
                </div>
            </div>
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible">

                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <div class="container-fluid">

        {{-- Credit Note Details --}}
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice"></i> Credit Note Details
                </h3>
            </div>

            <div class="card-body row">
                <div class="col-md-4">
                    <label class="text-muted">Credit Note No</label>
                    <input class="form-control form-control-sm"
                           value="{{ $creditnote->creditnote_invoice_no }}" readonly>
                </div>


                <div class="col-md-4">
                    <label class="text-muted">Invoice No</label>
                    <input class="form-control form-control-sm"
                           value="{{ $creditnote->order_invoice_number }}" readonly>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('creditnote.store', $creditnote->creditnote_id) }}">
            @csrf

            {{-- Credit Note Info --}}
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt"></i> Credit Note Information
                    </h3>
                </div>

                <div class="card-body row">
                    <div class="col-md-3">
                        <label>Return Date</label>
                        <input type="text"
                               id="return_date"
                               name="return_date"
                               class="form-control form-control-sm"
                               value="{{ old('return_date', optional($creditnote->return_date)->format('d-M-Y')) }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label>Return Type</label>
                        <select name="return_type" class="form-control form-control-sm">
                            <option value="SALES_RETURN" selected>Sales Return</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Credit Note Date</label>
                        <input type="text"
                               id="credit_note_date"
                               name="credit_note_date"
                               class="form-control form-control-sm"
                               value="{{ old('credit_note_date', optional($creditnote->credit_note_date)->format('d-M-Y')) }}"
                               required>
                    </div>
                </div>
            </div>

            {{-- Credit Note Items --}}
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-boxes"></i> Credit Note Items
                    </h3>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="bg-light text-center">
                        <tr>
                            <th>Item</th>
                            <th>Code</th>
                            <th>HSN</th>
                            <th width="80">Qty</th>
                            <th>Unit</th>
                            <th width="120">Price</th>
                            <th width="140">Taxable</th>
                            <th width="140">After Tax</th>
                            <th width="60">❌</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($creditnote->items as $index => $item)
                            <tr>
                                <td class="text-center">
                                    {{ $item->item_name }}

                                    <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->item_id }}">
                                    <input type="hidden" name="items[{{ $index }}][item_name]" value="{{ $item->item_name }}">
                                    <input type="hidden" name="items[{{ $index }}][item_code]" value="{{ $item->item_code }}">
                                    <input type="hidden" name="items[{{ $index }}][hsn_code]" value="{{ $item->hsn_code }}">
                                    <input type="hidden" name="items[{{ $index }}][item_unit]" value="{{ $item->item_unit }}">

                                    <input type="hidden"
                                           name="items[{{ $index }}][tax_percentage]"
                                           class="tax_percentage"
                                           value="{{ $item->tax_percentage }}">

                                    <input type="hidden"
                                           name="items[{{ $index }}][price_per_unit]"
                                           class="price"
                                           value="{{ $item->price_per_unit }}">
                                </td>

                                <td class="text-center">{{ $item->item_code }}</td>
                                <td class="text-center">{{ $item->hsn_code }}</td>

                                <td>
                                    <input type="number"
                                           name="items[{{ $index }}][total_item_quantity]"
                                           class="form-control form-control-sm qty text-right"
                                           value="{{ $item->total_item_quantity }}"
                                           min="1">
                                </td>

                                <td class="text-center">{{ $item->item_unit }}</td>


                                <td class="text-center">{{ $item->price_per_unit }}</td>


                                <td>
                                    <input type="number"
                                           name="items[{{ $index }}][taxable_amount]"
                                           class="form-control form-control-sm taxable_amount text-right"
                                           value="{{ $item->taxable_amount }}"
                                           readonly>
                                </td>

                                <td>
                                    <input type="number"
                                           name="items[{{ $index }}][after_tax_value]"
                                           class="form-control form-control-sm after_tax_value text-right"
                                           value="{{ $item->after_tax_value }}"
                                           readonly>
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-xs removeRow">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>

                    </table>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    @if($creditnote->credit_note_status!='C')
                    <button type="button"
                            class="btn btn-outline-primary"
                            onclick="addNewItems('{{ $creditnote->creditnote_id }}')">
                        <i class="fas fa-plus-circle"></i> Add New Items
                    </button>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Credit Note
                    </button>
                        @endif
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
            // flatpickr("#credit_note_date", {
            //     dateFormat: "d-M-Y",
            //     allowInput: true
            // });
        });
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr("#return_date", {
                dateFormat: "d-M-Y",
                allowInput: true
            });
        });

        function addNewItems($creditnoteId)
        {
            $.ajax({
                url: "<?php echo e(url('creditnote')); ?>/" + $creditnoteId + "/add-new-items",
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {

                    if (response.status === 'success') {

                        showAjaxResponse(response, 'Credit Items');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);

                    }else {

                        showAjaxResponse(response,'Credit Items');
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

