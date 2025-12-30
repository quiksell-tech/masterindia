@extends('layouts.adminlte')

@section('content')
    <div class="card card-primary">

        <div class="card-header">
            <h3 class="card-title">Add Transporter</h3>
        </div>

        <form method="POST" action="{{ route('transporters.store') }}">
            @csrf

            <div class="card-body">

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>GSTN</label>
                    <input type="text" name="transporter_gstn" class="form-control">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control">
                        <option value="Y">Active</option>
                        <option value="N">Inactive</option>
                    </select>
                </div>

            </div>

            <div class="card-footer">
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('transporters.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
@endsection
