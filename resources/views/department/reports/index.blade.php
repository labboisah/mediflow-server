@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
<div class="row">
    <div class="col-md-4 offset-4">
        <div class="card-body shadow p-4">
            <div class="text-center">
                <img src="{{asset('images/logo.png')}}" alt="" width="100" height="100">
            </div>
            <form method="POST" action="{{ route('department.reports.generate') }}">
                @csrf
                <div class="form-group mb-4">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control">
                </div>

                <div class="form-group mb-4">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control">
                </div>

                <div class="form-group mb-4">
                    <label>&nbsp;</label>
                    <button class="btn btn-danger w-100">
                        <i class="bi bi-bar-chart"></i>
                        Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
