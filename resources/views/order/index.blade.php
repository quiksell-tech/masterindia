@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        <div class="card card-primary card-outline">

            {{-- Header --}}
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice mr-1"></i>
                    Orders
                </h3>

                <a href="{{ route('orders.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Add Order
                </a>
            </div>

            {{-- Body --}}
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-bordered mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 80px">ID</th>
                        <th>Invoice No.</th>
                        <th>total_sale_value</th>
                        <th>total_tax</th>
                        <th>total_after_tax</th>
                        <th style="width: 140px">Order Date</th>
                        <th style="width: 100px">Status</th>
                        <th style="width: 100px">Action</th>
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
                                <strong>{{ $order->total_sale_value }}</strong>
                            </td>

                            <td>
                                <strong>{{ $order->total_tax }}</strong>
                            </td>
                            <td>
                                <strong>{{ $order->total_after_tax }}</strong>
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($order->order_invoice_date)->format('d M Y') }}
                            </td>

                            <td>
                            <span class="badge-{{ $order->is_active === 'Y' ? 'success' : 'danger' }}">
                                {{ $order->is_active === 'Y' ? 'Active' : 'Inactive' }}
                            </span>
                            </td>

                            <td>
                                <a href="{{ route('orders.edit', $order->order_id) }}"
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted p-3">
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
@endsection
