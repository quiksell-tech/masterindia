@extends('layouts.adminlte')

@section('content')
    <div class="card card-warning">

        <div class="card-header">
            <h3 class="card-title">Edit Transporter</h3>
        </div>

        <form method="POST" action="{{ route('transporters.update', $transporter->transporter_id) }}">
            @csrf

            <div class="card-body">

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name"
                           value="{{ $transporter->name }}"
                           class="form-control" required>
                </div>

                <div class="form-group">
                    <label>GSTN</label>
                    <input type="text" name="transporter_gstn"
                           value="{{ $transporter->transporter_gstn }}"
                           class="form-control">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control">
                        <option value="Y" {{ $transporter->is_active=='Y'?'selected':'' }}>Active</option>
                        <option value="N" {{ $transporter->is_active=='N'?'selected':'' }}>Inactive</option>
                    </select>
                </div>

            </div>

            <div class="card-footer">
                <button class="btn btn-warning">Update</button>
                <a href="{{ route('transporters.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
@endsection
