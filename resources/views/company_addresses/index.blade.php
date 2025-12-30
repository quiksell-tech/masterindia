@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        <div class="card card-primary card-outline">

            {{-- Header --}}
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    Company Addresses
                </h3>

                <a href="{{ route('company-addresses.create') }}"
                   class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Add Address
                </a>
            </div>

            {{-- Body --}}
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-bordered mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 70px">ID</th>
                        <th>Company</th>
                        <th style="width: 90px">Type</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>State</th>
                        <th style="width: 90px">Pincode</th>
                        <th style="width: 100px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($addresses as $row)
                        <tr>
                            <td>{{ $row->address_id }}</td>
                            <td>{{ $row->company->name ?? '-' }}</td>

                            <td>
                            <span class="">
                                {{ ucfirst($row->address_type) }}
                            </span>
                            </td>

                            <td>{{ $row->address_line }}</td>
                            <td>{{ $row->city }}</td>
                            <td>{{ $row->state }}</td>
                            <td>{{ $row->pincode }}</td>

                            <td>
                                <a href="{{ route('company-addresses.edit', $row->address_id) }}"
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted p-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                No addresses found
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}

            <div class="card-footer clearfix">
                <nav class="float-right">
                    {{ $addresses->links('pagination::bootstrap-4') }}
                </nav>
            </div>
        </div>
    </div>
@endsection
