@extends('layouts.modern')

@section('title', 'Index.Blade')
@section('page-title', 'Index.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('content')

@section('header')
    <div class="d-flex justify-content-between align-items-center">
    <h1 class="h3 d-flex align-items-center mb-0">
        <i class="bi bi-boxes me-2 text-primary"></i>
        Manage {{auth()->user()->department->name}} Consumable Stocks
    </h1>
    <a href="{{ route('department.stocks.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Add Consumable Stock
    </a>
</div>
@endsection
<div class="row">
    <div class="col-md-10 offset-1">
        <table class="table table-striped">
            <tr>
                <th>Consumable</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Date</th>
                <th></th>
            </tr>

            @foreach($stocks as $stock)

            <tr>
                <td>{{ $stock->consumable->name }}</td>
                <td>{{ $stock->quantity }}</td>
                <td>{{ $stock->unit_price }}</td>
                <td>{{ $stock->purchase_date }}</td>
                <td>
                    <a href="{{ route('department.stocks.edit',$stock) }}" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST"
                    action="{{ route('department.stocks.destroy',$stock) }}"
                    style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want delete this consumable stock')">
                    <i class="bi bi-trash"></i>
                    </button>
                    </form>
                </td>
            </tr>

            @endforeach

        </table>
    </div>
</div>


@endsection
