@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() dit. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() dit. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('title', 'Edit Service for Bill - ' . $bill->bill_number)
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
                
                <form action="{{ route('admin.bills.services.update', [$billService]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editserviceModalLabel">Edit Service for Bill #{{ $bill->bill_number }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="service_id" class="form-label">Select service <span class="text-danger">*</span></label>
                            <select id="service_id" name="service_id" class="form-control @error('service_id') is-invalid @enderror" required>
                                <option value="{{$billService->id}}">{{$billService->service->name ?? 'Select Service'}}</option>
                                @foreach(App\Models\Service::all() as $service)
                                    <option value="{{ $service->id }}" @selected(old('service_id', $billService->service_id) == $service->id)>
                                        {{ $service->name }} ({{ number_format($service->price, 2) }} {{ config('app.currency') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('service_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" id="quantity" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $billService->quantity) }}" min="1" required>
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
