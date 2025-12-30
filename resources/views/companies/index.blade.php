@extends('layouts.adminlte')

@section('content')
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">
                <i class="fas fa-users mr-1"></i>
                Companies
            </h3>

            <a href="{{ route('companies.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus mr-1"></i> Add Company
            </a>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Legal Name</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($companies as $company)
                    <tr>
                        <td>{{ $company->company_id }}</td>
                        <td>{{ $company->name }}</td>
                        <td>{{ $company->legal_name }}</td>
                        <td>
                        <span class="badge bg-{{ $company->is_active == 'Y' ? 'success' : 'danger' }}">
                            {{ $company->is_active }}
                        </span>
                        </td>
                        <td>
                            <a href="{{ route('companies.edit', $company->company_id) }}" class="btn btn-sm btn-warning">
                                Edit
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
