@extends('layouts.modern')

@section('title', 'Show.Blade')
@section('page-title', 'Show.Blade')
@section('page-subtitle', 'Mediflow workspace.')
@php 
$patient = $prescription->patientVisit->patient;
@endphp
@section('header')
<div class="d-flex align-items-center gap-3">
    <i class="bi bi-person-vcard text-success" style="font-size: 2rem;"></i>
    <div>
        <h1 class="h3 mb-1">{{ $patient->demographic->full_name ?? 'Patient Details' }}</h1>
        <p class="mb-0 text-muted">
            Hospital Number:
            <strong class="text-success">{{ $patient->hospital_number }} <a class="btn btn-outline-secondary" href="{{route('patient.show',$patient)}}"><i class="bi bi-arrow-left me-2"></i>Back to Patient Profile</a></strong>
        </p>
    </div>
</div>
@endsection
@section('content')
<style>
    .prescription-print {
        background: #fff;
        color: #111;
    }

    .prescription-header {
        display: grid;
        grid-template-columns: 90px 1fr;
        gap: 1rem;
        align-items: center;
    }

    .prescription-logo {
        width: 80px;
        height: 80px;
        object-fit: contain;
    }

    .print-only {
        display: none;
    }

    @media print {
        body.print-a4 .no-print,
        body.print-thermal .no-print,
        body.print-a4 nav,
        body.print-thermal nav,
        body.print-a4 footer,
        body.print-thermal footer {
            display: none !important;
        }

        .print-only {
            display: block;
        }

        body.print-a4 #print,
        body.print-thermal #print {
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        body.print-a4 {
            background: #fff;
        }

        body.print-a4 #print {
            width: 190mm;
            min-height: 277mm;
            font-size: 12px;
        }

        body.print-thermal #print {
            width: 76mm;
            font-size: 10px;
        }

        body.print-thermal .prescription-header {
            display: block;
            text-align: center;
        }

        body.print-thermal .prescription-logo {
            width: 56px;
            height: 56px;
            margin-bottom: .35rem;
        }

        body.print-thermal table {
            font-size: 9px;
        }
    }
</style>
    <div class="container">
        <div class="row">
            <div class="col-md-5">
                <div class="card-body shadow p-4">
                    <h2>Prescribe Medication</h2>
                    <form action="{{ route('patient.prescription.add', $prescription) }}" method="POST">
                        @csrf
                        
                        <div class="form-group mb-2">
                            <label for="ward_id">Medicine Type</label>
                            <select name="medicine_type_id" id="medicine_type_id" class="form-control" required>
                                <option value="">Select Type of Medicine</option>
                                @foreach(App\Models\MedicineType::all() as $type)
                                <option value="{{$type->id}}">{{$type->name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label for="medicine_name">Medicine</label>
                            <input type="text" name="medicine_name" id="medicine_name" class="form-control" list="medicine_options" required placeholder="Type or select medicine">
                            <datalist id="medicine_options"></datalist>
                            <small class="text-muted">Existing medicines show generic name, company, stock, and price.</small>
                        </div>
                        <div class="form-group mb-2">
                            <label for="treatment_diagnosis">Treatment / Infection / Disease</label>
                            <textarea name="treatment_diagnosis" id="treatment_diagnosis" class="form-control" rows="2" required>{{ old('treatment_diagnosis', $prescription->treatment_diagnosis) }}</textarea>
                        </div>

                        <div class="form-group mb-2">
                            <label for="ward_id">Route</label>
                            <select name="route_id" id="route_id" class="form-control" required>
                                <option value="">Select Route of Medication</option>
                                @foreach(App\Models\Route::all() as $route)
                                <option value="{{$route->id}}">{{$route->name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label for="date">Dosage</label>
                            <input type="text" class="form-control" name="dosage" required placeholder="Pls, specify value in g, mm, or ml">
                        </div>
                        <div class="form-group mb-2">
                            <label for="period">Period</label>
                            <select name="period" id="period" class="form-control">
                                @foreach([1,2,3,4,5,6,8,12] as $hour)
                                <option value="{{$hour}} hourly">{{$hour}} Hourly</option>
                                @endforeach

                                @foreach([1,2,3,4,5,6,7] as $day)
                                <option value="{{$day}} daily">{{$day}} Daily</option>
                                @endforeach

                                @foreach([1,2,3,4] as $week)
                                <option value="{{$week}} weekly">{{$week}} Weekly</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label for="time">Duration (Days)</label>
                            <input type="number" class="form-control" name="duration">
                        </div>

                        <button type="submit" class="btn btn-primary">Save Prescription</button>
                    </form>
                </div>
            </div>
            <div class="col-md-7">
                <div id="print" class="prescription-print p-4">
                    <div class="prescription-header">
                        <div>
                            <img src="{{asset('images/logo.png')}}" class="prescription-logo" alt="Hospital logo">
                        </div>
                        <div>
                            <h3 class="text-success mb-1">FATIMA YAHAYA HOSPITAL, SIFAWA</h3>
                            <div class="text-danger"><em>No 5, Birnin Kebbi Road Sifawa, Bodinga LG, Sokoto state</em></div>
                            <div class="small text-muted">Prescription Form</div>
                        </div>
                    </div>
                    <hr>
                    <div class="p-4">
                        <p class="mb-0 text-muted">
                            Patient Name:
                            <strong class="">{{ $prescription->patientVisit->patient->demographic->full_name ?? 'Patient Details'}}</strong>
                        </p>

                        <p class="mb-0 text-muted">
                            Hospital Number:
                            <strong class="">{{ $prescription->patientVisit->patient->hospital_number }}</strong>
                        </p>
                        
                        <p class="mb-0 text-muted">
                        Prescribe At:
                        <strong class="">{{ date('M d, Y',strtotime($prescription->created_at))}} @ {{ date('h:s A',strtotime($prescription->created_at))}}</strong>
                        </p>
                        <p class="mb-0 text-muted">
                        Prescribed By:
                        <strong class="">{{ $prescription->prescribedBy->name}}</strong>
                        </p>
                        <p class="mb-0 text-muted">
                        Doctor Department:
                        <strong class="">{{ $prescription->prescribedBy->department?->name ?? 'N/A' }}</strong>
                        </p>
                        <p class="mb-0 text-muted">
                        Treatment / Infection / Disease:
                        <strong class="">{{ $prescription->treatment_diagnosis ?? 'N/A' }}</strong>
                        </p>
                        
                    </div>
                    <hr>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Company</th>
                                <th>Stock</th>
                                <th>Amount</th>
                                <th>Route</th>
                                <th>Dosage</th>
                                <th>Period</th>
                                <th>Duration (Days)</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prescription->prescriptionItems as $pit)
                            <tr>
                                <td>
                                    {{$pit->medicine->name}}
                                    @if($pit->medicine->generic_name)
                                        <br><small>{{ $pit->medicine->generic_name }}</small>
                                    @endif
                                </td>
                                <td>{{$pit->medicine->manufacturer ?? 'N/A'}}</td>
                                <td>{{$pit->medicine->availabilityLabel()}}</td>
                                <td>&#8358;{{ number_format($pit->medicine->latestSellingPrice(), 2) }}</td>
                                <td>{{$pit->route->name}}</td>
                                <td>{{$pit->dosage}}</td>
                                <td>{{$pit->period}}</td>
                                <td>{{$pit->duration}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Prescription Amount</th>
                                <th>&#8358;{{ number_format($prescription->prescriptionItems->sum(fn($item) => $item->medicine?->latestSellingPrice() ?? 0), 2) }}</th>
                                <th colspan="4"></th>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="row mt-4 print-only">
                        <div class="col-6">Doctor Signature: __________________</div>
                        <div class="col-6 text-end">Date: {{ now()->format('d M, Y') }}</div>
                    </div>
                
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3 no-print">
                    <a href="{{route('patient.prescription.submit', $prescription)}}" class="btn btn-success">Submit to Pharmacy</a>
                    <button type="button" onclick="printPrescription('a4');" class="btn btn-primary">Print A4</button>
                    <button type="button" onclick="printPrescription('thermal');" class="btn btn-outline-primary">Print Thermal</button>
                </div>
            </div>
        </div>
        
    </div>
{{-- AJAX SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const medicineTypeSelect = document.getElementById('medicine_type_id');
    const medicineOptions = document.getElementById('medicine_options');

    const ajaxBaseUrl = "{{ url('/ajax/medicines') }}";

    // Load medicines when type changes
    medicineTypeSelect.addEventListener('change', function () {

        const medicineTypeId = this.value;

        medicineOptions.innerHTML = '';

        if (!medicineTypeId) return;

        fetch(`${ajaxBaseUrl}/${medicineTypeId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {

            if (!data.length) {
                return;
            }

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.name;
                option.label = `${item.name} - ${item.available ? 'Available' : 'Not available'} - ₦${Number(item.selling_price || 0).toFixed(2)}`;
                medicineOptions.appendChild(option);
            });

        })
        .catch(error => {

            console.error(error);

        });
    });

});
</script>

<script>
    function printPrescription(mode) {
        document.body.classList.remove('print-a4', 'print-thermal');
        document.body.classList.add(mode === 'thermal' ? 'print-thermal' : 'print-a4');
        window.print();
        setTimeout(() => document.body.classList.remove('print-a4', 'print-thermal'), 500);
    }
</script>
@endsection

