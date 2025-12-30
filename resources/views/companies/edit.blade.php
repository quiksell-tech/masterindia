@extends('layouts.adminlte')

@section('content')
    <div class="card">
        <div class="card-header"><h3>Edit Company</h3></div>

        <form method="POST" action="{{ route('companies.update', $company->company_id) }}">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label>Name</label>
                    <input name="name" value="{{ $company->name }}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Legal Name</label>
                    <input name="legal_name" value="{{ $company->legal_name }}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control">
                        <option value="Y" {{ $company->is_active=='Y'?'selected':'' }}>Active</option>
                        <option value="N" {{ $company->is_active=='N'?'selected':'' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="card-footer">
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('companies.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
@endsection
