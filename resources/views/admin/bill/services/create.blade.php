@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('title', 'Add Service to Bill - ' . $bill->bill_number)
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form action="{{ route('admin.bills.services.store', $bill) }}" method="POST">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="newServiceModalLabel">
                        Add Service to Bill #{{ $bill->bill_number }}
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label for="service_id" class="form-label">
                            Select Service <span class="text-danger">*</span>
                        </label>

                        <select id="service_id"
                                name="service_id"
                                class="form-select @error('service_id') is-invalid @enderror"
                                required>

                            <option value="">-- Select Service --</option>

                            @foreach(App\Models\Service::all() as $service)
                                <option value="{{ $service->id }}"
                                    @selected(old('service_id') == $service->id)>

                                    {{ $service->name }}
                                    ({{ number_format($service->price, 2) }}
                                    {{ config('app.currency') }})
                                </option>
                            @endforeach

                        </select>

                        @error('service_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <!-- quantity -->
                    <div class="mb-3">
                        <label for="quantity" class="form-label">
                            Quantity <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               id="quantity"
                               name="quantity"
                               class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity') }}"
                               min="1"
                               required>
                        @error('quantity')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Close
                    </button>

                    <button type="submit"
                            class="btn btn-primary">
                        Add Service
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection
