@extends('layouts.adminlte')

@section('content')
    <div class="card">
        <div class="card-header"><h3>Add Company</h3></div>

        <form method="POST" action="{{ route('companies.store') }}">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label>Name</label>
                    <input name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Legal Name</label>
                    <input name="legal_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control" required>
                        <option value="Y">Active</option>
                        <option value="N">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="card-footer">
                <button class="btn btn-success">Save</button>
                <a href="{{ route('companies.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
@endsection
