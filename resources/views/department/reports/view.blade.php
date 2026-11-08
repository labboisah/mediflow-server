@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() iew. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() iew. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')

@section('header')
<div class="mb-3">

    <button onclick="window.print()" class="btn btn-secondary">
    <i class="bi bi-printer"></i> Print
    </button>
    <form action="{{route('department.reports.pdf')}}" method="post">
        @csrf
        @method('PUT')
        <input type="hidden" value="{{$revenue}}" name="revenue">
        <input type="hidden" value="{{$profits}}" name="profits">
        <input type="hidden" value="{{$consumables}}" name="consumables">
        <input type="hidden" value="{{$expenses}}" name="expenses">
        <button class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
        </button>
    </form>
</div>
@endsection
<div class="container">
    <div class="row">
        <div class="col-md-12 text-center">
            <img src="{{asset('images/logo.png')}}" width="100" height="100" alt="">
        </div>
        <div class="col-md-12">
            <h2 class="text text-center text-success" style="transform: scaley(1.5);">FATIMA YAHAYA HOSPITAL, SIFAWA</h2>
            <h4 class="text text-center" style="transform: scaley(1);">DEPARTMENT OF {{strtoupper(auth()->user()->department->name)}}</h4>
            <h5 class="text text-center text-muted" style="transform: scaleX(1);">FINANCIAL REPORT FROM {{date('d M, Y', strtotime($start))}} TO {{date('d M, Y', strtotime($end))}}</h5>
            <h6 class="text text-center text-danger"><em>No 5, Birnin Kebbi Road Sifawa, Bodinga LG, Sokoto state</em></h6>
        </div>
    </div>
    <hr>
<div class="row">

    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Total Revenue</h6>
                <h3>₦ {{ number_format($revenue) }}</h3>
                <i class="bi bi-graph-up fs-2"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6>Total Expenses</h6>
                <h3>₦ {{ number_format($expenses) }}</h3>
                <i class="bi bi-cash-stack fs-2"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6>Consumable Cost</h6>
                <h3>₦ {{ number_format($consumables) }}</h3>
                <i class="bi bi-box-seam fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card {{$profits > 0 ? 'bg-success' : 'bg-danger'}} text-white">
            <div class="card-body">
                <h6>Profit</h6>
                <h3>₦ {{ number_format($profits) }}</h3>
                <i class="bi bi-bar-chart-line fs-2"></i>
            </div>
        </div>
    </div>
</div>
<div class="row">
<div class="col-md-6">
<canvas id="financeChart"></canvas>
</div>
<div class="col-md-6">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Descrioption</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Revenue</td>
                <td>{{number_format($revenue)}}</td>
            </tr>
            <tr>
                <td>2</td>
                <td>Expenses</td>
                <td>{{number_format($expenses)}}</td>
            </tr>
            <tr>
                <td>3</td>
                <td>Consumables</td>
                <td>{{number_format($consumables)}}</td>
            </tr>
            <tr>
                <td>4</td>
                <td>Profit</td>
                <td>{{number_format($profits)}}</td>
            </tr>
        </tbody>
    </table>
</div>
</div>

</div>
<script src="{{ asset('vendor/chart.js/chart.min.js') }}"></script>
<script>
new Chart(document.getElementById('financeChart'),{

type:'line',

data:{
    labels:['Revenue','Expenses','Consumables','Profits'],

    datasets:[{
        data:[
            {{ $revenue }},
            {{ $expenses }},
            {{ $consumables }},
            {{ $profits }}
        ],
        backgroundColor:[
            '#198754',
            '#dc3545',
            '#0d6efd',
            '#198754'
        ]
    }]
}

});
</script>
@endsection
