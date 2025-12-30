@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        <div class="card card-primary card-outline">

            {{-- Header --}}
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-box mr-1"></i>
                    Items
                </h3>

                <a href="{{ route('items.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Add Item
                </a>
            </div>

            {{-- Body --}}
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-bordered mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 80px">ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>HSN</th>
                        <th style="width: 90px">Tax %</th>
                        <th style="width: 100px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->item_id }}</td>
                            <td>
                                <strong>{{ $item->item_name }}</strong>
                            </td>
                            <td>{{ $item->item_code }}</td>
                            <td>{{ $item->hsn_code }}</td>
                            <td>
                            <span class=" badge-info">
                                {{ $item->tax_percentage }}%
                            </span>
                            </td>
                            <td>
                                <a href="{{ route('items.edit', $item->item_id) }}"
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted p-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                No items found
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <div class="card-footer clearfix">
                <nav class="float-right">
                    {{ $items->links('pagination::bootstrap-4') }}
                </nav>
            </div>

        </div>
    </div>
@endsection
