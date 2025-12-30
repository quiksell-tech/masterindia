@extends('layouts.adminlte')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('items.store') }}">
                @csrf

                @include('items.form')

                <button class="btn btn-success">Save</button>
                <a href="{{ route('items.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
