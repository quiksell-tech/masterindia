@extends('layouts.adminlte')

@section('content')
    <div class="card card-outline card-primary">

        <div class="card-header d-flex justify-content-between">
            <h3 class="card-title">
                <i class="fas fa-truck mr-1"></i> Transporters
            </h3>
            <a href="{{ route('transporters.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Add Transporter
            </a>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>GSTN</th>
                    <th>Status</th>
                    <th width="80">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($transporters as $row)
                    <tr>
                        <td>{{ $row->transporter_id }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->transporter_gstn ?? '-' }}</td>
                        <td>
                        <span class="badge-{{ $row->is_active == 'Y' ? 'success' : 'danger' }}">
                            {{ $row->is_active == 'Y' ? 'Active' : 'Inactive' }}
                        </span>
                        </td>
                        <td>
                            <a href="{{ route('transporters.edit', $row->transporter_id) }}"
                               class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No transporters found
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer clearfix">
            <div class="float-right">
                {{ $transporters->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection
