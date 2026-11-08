@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')
@section('title', 'Add Investigation to Bill - ' . $bill->bill_number)
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form action="{{ route('admin.bills.investigations.store', $bill) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addInvestigationModalLabel">Add Investigation to Bill #{{ $bill->bill_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="investigation_id" class="form-label">Select Investigation <span class="text-danger">*</span></label>
                        <select id="investigation_id" name="investigation_id" class="form-control @error('investigation_id') is-invalid @enderror" required>
                            <option value="">-- Select Investigation --</option>
                            @foreach(App\Models\InvestigationType::all() as $investigationType)
                                <!-- select group -->
                                <optgroup label="{{ $investigationType->name }}">
                                    @foreach($investigationType->investigations as $investigation)
                                        <option value="{{ $investigation->id }}" @selected(old('investigation_id') == $investigation->id)>
                                            {{ $investigation->name }} ({{ number_format($investigation->price, 2) }} {{ config('app.currency') }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('investigation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- quantity -->
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" id="quantity" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" min="1" required>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="">
                    <button type="submit" class="btn btn-primary">Add Investigation</button>
                        </select>
                        @error('investigation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
            </form>
        </div>
    </div>
</div>
@endsection
