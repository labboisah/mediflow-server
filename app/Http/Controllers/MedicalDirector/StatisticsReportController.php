<?php

namespace App\Http\Controllers\MedicalDirector;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AntenatalCare;
use App\Models\InvestigationRequest;
use App\Models\Labour;
use App\Models\PatientAdmission;
use App\Models\PatientVisit;
use App\Models\ServiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StatisticsReportController extends Controller
{
    public function index(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $statistics = $this->statistics($request, $startDate, $endDate);

        return view('medical-director.statistics.index', compact('statistics', 'startDate', 'endDate'));
    }

    public function pdf(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $statistics = $this->statistics($request, $startDate, $endDate);
        $hospital = $this->hospitalHeaderData();
        $generatedBy = $request->user();

        $pdf = Pdf::loadView('medical-director.statistics.pdf', compact('statistics', 'startDate', 'endDate', 'hospital', 'generatedBy'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('medical-director-statistics-' . now()->format('Y-m-d') . '.pdf');
    }

    private function dateRange(Request $request): array
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()->format('Y-m-d')))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        if ($startDate->gt($endDate)) {
            return [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    private function statistics(Request $request, Carbon $startDate, Carbon $endDate): array
    {
        $regularAdmissions = $this->admissionQuery($request)
            ->whereBetween('date', [$startDate, $endDate])
            ->count();

        $legacyAdmissions = $this->legacyAdmissionQuery($request)
            ->whereBetween('admission_date', [$startDate, $endDate])
            ->count();

        $regularDischarges = $this->admissionQuery($request)
            ->whereHas('discharge', fn (Builder $query) => $query->whereBetween('date', [$startDate, $endDate]))
            ->count();

        $legacyDischarges = $this->legacyAdmissionQuery($request)
            ->whereBetween('discharge_date', [$startDate, $endDate])
            ->count();

        return [
            ['label' => 'Admissions', 'value' => $regularAdmissions + $legacyAdmissions, 'icon' => 'bi-hospital'],
            ['label' => 'Discharges', 'value' => $regularDischarges + $legacyDischarges, 'icon' => 'bi-box-arrow-right'],
            ['label' => 'Visits', 'value' => $this->visitQuery($request)->whereBetween('visit_date', [$startDate, $endDate])->count(), 'icon' => 'bi-person-lines-fill'],
            ['label' => 'Walk-in Visits', 'value' => $this->visitQuery($request)->whereBetween('visit_date', [$startDate, $endDate])->whereHas('patient', fn (Builder $query) => $query->where('is_walkIn', true))->count(), 'icon' => 'bi-person-walking'],
            ['label' => 'Lab Investigations', 'value' => $this->investigationQuery($request, 'laboratory')->whereBetween('requested_at', [$startDate, $endDate])->count(), 'icon' => 'bi-clipboard2-pulse'],
            ['label' => 'Radiology Investigations', 'value' => $this->investigationQuery($request, 'radiology')->whereBetween('requested_at', [$startDate, $endDate])->count(), 'icon' => 'bi-radioactive'],
            ['label' => 'ANC Visits', 'value' => $this->antenatalQuery($request)->whereBetween('created_at', [$startDate, $endDate])->count(), 'icon' => 'bi-heart-pulse'],
            ['label' => 'Labour', 'value' => $this->labourQuery($request)->whereBetween('labour_onset_time', [$startDate, $endDate])->count(), 'icon' => 'bi-activity'],
            ['label' => 'Labour Surgery', 'value' => $this->labourSurgeryQuery($request)->whereBetween('requested_at', [$startDate, $endDate])->count(), 'icon' => 'bi-scissors'],
            ['label' => 'General Surgery', 'value' => $this->generalSurgeryCount($request, $startDate, $endDate), 'icon' => 'bi-bandaid'],
            ['label' => 'SAMA', 'value' => $this->samaQuery($request)->whereBetween('updated_at', [$startDate, $endDate])->count(), 'icon' => 'bi-exclamation-triangle'],
        ];
    }

    private function visitQuery(Request $request): Builder
    {
        $query = PatientVisit::query()->whereHas('patient');
        $this->applyPatientVisitFilters($query, $request);

        return $query;
    }

    private function admissionQuery(Request $request): Builder
    {
        $query = Admission::query()->whereHas('patientVisit.patient');
        $this->applyPatientVisitFilters($query, $request, 'patientVisit');

        return $query;
    }

    private function legacyAdmissionQuery(Request $request): Builder
    {
        $query = PatientAdmission::query()->whereHas('patient');
        $this->applyPatientFilters($query, $request, 'patient');

        return $query;
    }

    private function investigationQuery(Request $request, string $departmentKeyword): Builder
    {
        $query = InvestigationRequest::query()
            ->whereHas('investigation.investigationType.department', function (Builder $query) use ($departmentKeyword) {
                $query->where('name', 'like', "%{$departmentKeyword}%");
            });

        $this->applyRequestFilters($query, $request, 'walkinPatient');

        return $query;
    }

    private function antenatalQuery(Request $request): Builder
    {
        $query = AntenatalCare::query()->whereHas('patient');
        $this->applyPatientFilters($query, $request, 'patient');

        return $query;
    }

    private function labourQuery(Request $request): Builder
    {
        $query = Labour::query()->whereHas('patient');
        $this->applyPatientFilters($query, $request, 'patient');

        return $query;
    }

    private function labourSurgeryQuery(Request $request): Builder
    {
        $query = ServiceRequest::query()
            ->whereHas('service', function (Builder $query) {
                $query->where(function (Builder $service) {
                    $service->where('category', 'like', '%labour%')
                        ->orWhere('category', 'like', '%labor%')
                        ->orWhere('name', 'like', '%labour%')
                        ->orWhere('name', 'like', '%labor%');
                })->where(function (Builder $service) {
                    $service->where('name', 'like', '%CS%')
                        ->orWhere('name', 'like', '%surgery%')
                        ->orWhere('name', 'like', '%caesarean%')
                        ->orWhere('name', 'like', '%cesarean%')
                        ->orWhere('name', 'like', '%c-section%');
                });
            });

        $this->applyRequestFilters($query, $request, 'walkin');

        return $query;
    }

    private function generalSurgeryCount(Request $request, Carbon $startDate, Carbon $endDate): int
    {
        $serviceRequests = ServiceRequest::query()
            ->whereHas('service', function (Builder $query) {
                $query->where(function (Builder $service) {
                    $service->where('category', 'like', '%surgery%')
                        ->orWhere('name', 'like', '%surgery%')
                        ->orWhereHas('department', function (Builder $department) {
                            $department->where('name', 'like', '%surgery%');
                        });
                })->where('category', 'not like', '%labour%')
                    ->where('category', 'not like', '%labor%')
                    ->where('name', 'not like', '%labour%')
                    ->where('name', 'not like', '%labor%')
                    ->where('name', 'not like', '%CS%');
            })
            ->whereBetween('requested_at', [$startDate, $endDate]);

        $this->applyRequestFilters($serviceRequests, $request, 'walkin');
        $serviceRequests = $serviceRequests->count();

        $referrals = $this->visitQuery($request)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->where('referred_to', 'like', '%General Surgery%')
            ->count();

        return $serviceRequests + $referrals;
    }

    private function samaQuery(Request $request): Builder
    {
        $query = Admission::query()
            ->whereIn('status', ['sama', 'SAMA'])
            ->whereHas('patientVisit.patient');

        $this->applyPatientVisitFilters($query, $request, 'patientVisit');

        return $query;
    }

    private function applyRequestFilters(Builder $query, Request $request, string $walkinRelation): void
    {
        $search = trim((string) $request->input('search', ''));
        if (strlen($search) >= 2) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('patientVisit.patient', fn (Builder $patient) => $patient->where('hospital_number', 'like', "%{$search}%"))
                    ->orWhereHas('patientVisit.patient.demographic', function (Builder $demographic) use ($search) {
                        $demographic->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas($walkinRelation, function (Builder $walkin) use ($search) {
                        $walkin->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('gender')) {
            $query->whereHas('patientVisit.patient.demographic', fn (Builder $demographic) => $demographic->where('gender', $request->input('gender')));
        }
    }

    private function applyPatientVisitFilters(Builder $query, Request $request, string $visitRelation = ''): void
    {
        $patientRelation = $visitRelation === '' ? 'patient' : "{$visitRelation}.patient";
        $this->applyPatientFilters($query, $request, $patientRelation);
    }

    private function applyPatientFilters(Builder $query, Request $request, string $patientRelation): void
    {
        $search = trim((string) $request->input('search', ''));

        if (strlen($search) >= 2) {
            $query->where(function (Builder $builder) use ($patientRelation, $search) {
                $builder->whereHas($patientRelation, fn (Builder $patient) => $patient->where('hospital_number', 'like', "%{$search}%"))
                    ->orWhereHas("{$patientRelation}.demographic", function (Builder $demographic) use ($search) {
                        $demographic->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('gender')) {
            $query->whereHas("{$patientRelation}.demographic", fn (Builder $demographic) => $demographic->where('gender', $request->input('gender')));
        }
    }

    private function hospitalHeaderData(): array
    {
        return [
            'name' => strtoupper(config('app.title', config('app.name', 'FAYHOS'))),
            'address' => strtoupper(config('app.address', '')),
            'logo' => public_path('images/logo.png'),
        ];
    }
}
