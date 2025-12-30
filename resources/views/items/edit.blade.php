@extends('layouts.adminlte')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST"
                  action="{{ route('items.update', $item->item_id) }}">
                @csrf

                @include('items.form', ['item' => $item])

                <button class="btn btn-success">Update</button>
                <a href="{{ route('items.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
