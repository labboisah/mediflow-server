@extends('layouts.modern')

@section('title', 'Index.Blade')
@section('page-title', 'Index.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Manage Consumables')

@section('header')
    <div class="d-flex justify-content-between align-items-center">
    <h1 class="h3 d-flex align-items-center mb-0">
        <i class="bi bi-clipboard2-data me-2 text-primary"></i>
        Manage {{auth()->user()->department->name}} Consumables
    </h1>
    <a href="{{ route('department.consumables.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Add Consumable
    </a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-10 offset-1">
<table class="table table-bordered datatable">
<thead>
    <tr>
        <th>#</th>
        <th>Name</th>
        <th>Unit</th>
        <th>Reorder Level</th>
        <th>Action</th>
    </tr>
</thead>

<tbody>
@foreach($consumables as $item)

<tr>
<td>{{$loop->iteration}}</td>
<td>{{ $item->name }}</td>

<td>{{ $item->unit }}</td>
<td>{{ $item->reorder_level }}</td>

<td>

<a href="{{ route('department.consumables.edit',$item) }}" class="btn btn-warning btn-sm">

<i class="bi bi-pencil"></i>
</a>

<form method="POST"
action="{{ route('department.consumables.destroy',$item) }}"
style="display:inline">

@csrf
@method('DELETE')

<button class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want delete this consumable')">

<i class="bi bi-trash"></i>

</button>

</form>

</td>

</tr>

@endforeach
</tbody>
</table>
    </div>
</div>


@endsection
