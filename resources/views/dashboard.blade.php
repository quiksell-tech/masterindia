@extends('layouts.adminlte')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid">
        <h1>Welcome, {{ $admin->phone }}</h1>

        <div class="row mt-4">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>10</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>5</h3>
                        <p>Active Sessions</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
