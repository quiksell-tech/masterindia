@extends('layouts.adminlte')

@section('content')
    <div class="container-fluid">

        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-1"></i>
                    Edit Party
                </h3>
            </div>

            <form method="POST" action="{{ route('party.update', $party->party_id) }}">
                @csrf

                <div class="card-body">

                    {{-- Company --}}
                    <div class="form-group">
                        <label>
                            <i class="fas fa-building mr-1"></i> Company
                        </label>
                        <select name="company_id" class="form-control" required>
                            @foreach($companies as $company)
                                <option value="{{ $company->company_id }}"
                                    {{ $party->company_id == $company->company_id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        {{-- Party Name --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-user-tag mr-1"></i> Party Trade Name
                                </label>
                                <input name="party_trade_name"
                                       value="{{ $party->party_trade_name }}"
                                       class="form-control"
                                       placeholder="Enter party name"
                                       required>
                            </div>
                        </div>

                        {{-- Legal Name --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-file-contract mr-1"></i> Legal Name
                                </label>
                                <input name="party_legal_name"
                                       value="{{ $party->party_legal_name }}"
                                       class="form-control"
                                       placeholder="Enter legal name"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-file-contract mr-1"></i> Contact name
                                </label>
                                <input name="contact_name"
                                       value="{{ $party->contact_name }}"
                                       class="form-control"
                                       placeholder="Enter contact name"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-file-contract mr-1"></i> phone
                                </label>
                                <input name="phone"
                                       value="{{ $party->phone }}"
                                       class="form-control"
                                       placeholder="Enter Phone"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-file-contract mr-1"></i> email
                                </label>
                                <input name="email"
                                       value="{{ $party->email }}"
                                       class="form-control"
                                       placeholder="Enter email"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- GSTN --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-receipt mr-1"></i> GSTN
                                </label>
                                <input name="party_gstn"
                                       value="{{ $party->party_gstn }}"
                                       class="form-control"
                                       placeholder="15-character GST number">
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-toggle-on mr-1"></i> Status
                                </label>
                                <select name="is_active" class="form-control">
                                    <option value="Y" {{ $party->is_active == 'Y' ? 'selected' : '' }}>
                                        Active
                                    </option>
                                    <option value="N" {{ $party->is_active == 'N' ? 'selected' : '' }}>
                                        Inactive
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Update
                    </button>
                    <a href="{{ route('party.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>

            </form>
        </div>

    </div>
@endsection
