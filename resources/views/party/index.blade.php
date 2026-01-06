@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        <div class="card card-primary card-outline">

            {{-- Header --}}
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-users mr-1"></i>
                    Parties
                </h3>

                <a href="{{ route('party.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Add Party
                </a>
            </div>

            {{-- Body --}}
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-bordered mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 80px">ID</th>
                        <th>Company</th>
                        <th>Trade Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>GSTN</th>
                        <th style="width: 100px">Status</th>
                        <th style="width: 120px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($parties as $party)
                        <tr>
                            <td>{{ $party->party_id }}</td>
                            <td>{{ $party->company->name ?? '-' }}</td>
                            <td>
                                <strong>{{ $party->party_trade_name }}</strong>
                            </td>
                            <td>
                                <strong>{{ $party->phone }}</strong>
                            </td>
                            <td>
                                <strong>{{ $party->email }}</strong>
                            </td>
                            <td>{{ $party->party_gstn ?? '-' }}</td>
                            <td>
                            <span class="badge-{{ $party->is_active == 'Y' ? 'success' : 'danger' }}">
                                {{ $party->is_active == 'Y' ? 'Active' : 'Inactive' }}
                            </span>
                            </td>
                            <td>
                                <a href="{{ route('party.edit', $party->party_id) }}"
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted p-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                No parties found
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <div class="card-footer text-right">
                <small class="text-muted">
                    Total Parties: {{ $parties->count() }}
                </small>
            </div>

        </div>
    </div>
@endsection
