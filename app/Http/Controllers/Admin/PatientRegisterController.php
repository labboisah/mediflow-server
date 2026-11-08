<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientRegisterController extends Controller
{
    public function index(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $query = $this->registerQuery($startDate, $endDate, $request);
        $summary = $this->registerSummary(clone $query);

        $patients = (clone $query)
            ->withCount('patientVisits')
            ->latest('registration_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.patient.register-report', compact('patients', 'summary', 'startDate', 'endDate'));
    }

    public function csv(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $filename = 'admin-patient-register-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($startDate, $endDate, $request): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Hospital Number',
                'Patient Name',
                'Gender',
                'Age',
                'Phone Number',
                'File Type',
                'Patient Type',
                'Visits',
                'Registration Date',
            ]);

            $this->registerQuery($startDate, $endDate, $request)
                ->withCount('patientVisits')
                ->reorder('registration_date', 'desc')
                ->orderBy('patients.id', 'desc')
                ->chunk(500, function ($patients) use ($handle): void {
                    foreach ($patients as $patient) {
                        fputcsv($handle, [
                            $patient->hospital_number,
                            $patient->demographic?->full_name,
                            $patient->demographic?->gender,
                            $patient->demographic?->age,
                            $patient->demographic?->phone_number,
                            $patient->fileType?->name,
                            $patient->is_walkIn ? 'Walk-in' : 'Registered',
                            $patient->patient_visits_count,
                            $patient->registration_date?->format('Y-m-d H:i:s'),
                        ]);
                    }

                    fflush($handle);
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $query = $this->registerQuery($startDate, $endDate, $request);
        $summary = $this->registerSummary(clone $query);
        $patients = (clone $query)->withCount('patientVisits')->latest('registration_date')->get();
        $hospital = $this->hospitalHeaderData();
        $generatedBy = $request->user();

        $pdf = Pdf::loadView('admin.patient.register-pdf', compact('patients', 'summary', 'startDate', 'endDate', 'hospital', 'generatedBy'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('admin-patient-register-' . now()->format('Y-m-d') . '.pdf');
    }

    public function summary(Patient $patient)
    {
        $patient->load(['demographic', 'nextOfKin', 'fileType']);

        $visits = $patient->patientVisits()
            ->withCount(['bills', 'serviceRequests', 'investigationRequests', 'vitalSigns'])
            ->with([
                'createdBy',
                'serviceRequests.service',
                'investigationRequests.investigation',
                'visitActivities' => fn ($query) => $query->orderBy('id')->limit(5),
            ])
            ->latest('created_at')
            ->paginate(25, ['*'], 'visits_page');

        $bills = Bill::query()
            ->with(['payments.paymentMethod', 'issuedBy'])
            ->whereHas('patientVisit', fn (Builder $query) => $query->where('patient_id', $patient->id))
            ->latest('issued_date')
            ->limit(5)
            ->get();

        $payments = Payment::query()
            ->with(['bill', 'paymentMethod', 'recordedBy'])
            ->whereHas('bill.patientVisit', fn (Builder $query) => $query->where('patient_id', $patient->id))
            ->latest('payment_date')
            ->paginate(25, ['*'], 'payments_page');

        $admissions = Admission::query()
            ->with(['patientVisit', 'bed.ward', 'admittedBy', 'discharge.dischargedBy'])
            ->whereHas('patientVisit', fn (Builder $query) => $query->where('patient_id', $patient->id))
            ->latest('created_at')
            ->paginate(25, ['*'], 'admissions_page');

        $legacyAdmissions = PatientAdmission::query()
            ->with(['admittedBy', 'dischargedBy'])
            ->where('patient_id', $patient->id)
            ->latest('admission_date')
            ->paginate(25, ['*'], 'legacy_admissions_page');

        $visitCount = $patient->patientVisits()->count();
        $admissionCount = Admission::query()
            ->whereHas('patientVisit', fn (Builder $query) => $query->where('patient_id', $patient->id))
            ->count();
        $dischargeCount = Admission::query()
            ->whereHas('patientVisit', fn (Builder $query) => $query->where('patient_id', $patient->id))
            ->where(fn (Builder $query) => $query
                ->whereHas('discharge')
                ->orWhereRaw('LOWER(status) = ?', ['discharged']))
            ->count();
        $legacyAdmissionCount = PatientAdmission::query()->where('patient_id', $patient->id)->count();
        $legacyDischargeCount = PatientAdmission::query()
            ->where('patient_id', $patient->id)
            ->whereNotNull('discharge_date')
            ->count();
        $billMetrics = Bill::query()
            ->whereHas('patientVisit', fn (Builder $query) => $query->where('patient_id', $patient->id))
            ->selectRaw('COUNT(*) as bills, COALESCE(SUM(amount), 0) as billed, COALESCE(SUM(due_amount), 0) as due')
            ->first();
        $paymentMetrics = Payment::query()
            ->whereHas('bill.patientVisit', fn (Builder $query) => $query->where('patient_id', $patient->id))
            ->selectRaw(
                'COUNT(*) as payments,
                COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as paid',
                ['completed']
            )
            ->first();

        $stats = [
            'visits' => (int) $visitCount,
            'admissions' => (int) ($admissionCount + $legacyAdmissionCount),
            'discharges' => (int) ($dischargeCount + $legacyDischargeCount),
            'bills' => (int) $billMetrics->bills,
            'billed' => (float) $billMetrics->billed,
            'due' => (float) $billMetrics->due,
            'paid' => (float) $paymentMetrics->paid,
            'payments' => (int) $paymentMetrics->payments,
        ];

        return view('admin.patient.summary', compact('patient', 'visits', 'bills', 'payments', 'admissions', 'legacyAdmissions', 'stats'));
    }

    private function dateRange(Request $request): array
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'patient_type' => ['nullable', 'in:registered,walk_in'],
        ]);

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()->format('Y-m-d')))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        if ($startDate->gt($endDate)) {
            return [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    private function registerQuery(Carbon $startDate, Carbon $endDate, Request $request): Builder
    {
        $search = trim((string) $request->input('search', ''));

        return Patient::query()
            ->with(['demographic', 'fileType'])
            ->whereBetween('registration_date', [$startDate, $endDate])
            ->when(strlen($search) >= 2, function (Builder $query) use ($search) {
                $query->where(function (Builder $builder) use ($search) {
                    $builder->where('hospital_number', 'like', "%{$search}%")
                        ->orWhereHas('demographic', function (Builder $demographic) use ($search) {
                            $demographic->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('gender'), function (Builder $query) use ($request) {
                $query->whereHas('demographic', fn (Builder $demographic) => $demographic->where('gender', $request->input('gender')));
            })
            ->when($request->input('patient_type') === 'registered', fn (Builder $query) => $query->where('is_walkIn', false))
            ->when($request->input('patient_type') === 'walk_in', fn (Builder $query) => $query->where('is_walkIn', true));
    }

    private function registerSummary(Builder $query): array
    {
        $filteredPatients = (clone $query)
            ->reorder()
            ->select(['patients.id', 'patients.is_walkIn']);
        $summary = DB::query()
            ->fromSub($filteredPatients, 'filtered_patients')
            ->leftJoin('patient_demographics', function (JoinClause $join): void {
                $join->on('patient_demographics.patient_id', '=', 'filtered_patients.id')
                    ->whereNull('patient_demographics.deleted_at');
            })
            ->selectRaw(
                'COUNT(DISTINCT filtered_patients.id) as total,
                COUNT(DISTINCT CASE WHEN filtered_patients.is_walkIn = ? THEN filtered_patients.id END) as registered,
                COUNT(DISTINCT CASE WHEN filtered_patients.is_walkIn = ? THEN filtered_patients.id END) as walk_in,
                COUNT(DISTINCT CASE WHEN patient_demographics.gender = ? THEN filtered_patients.id END) as male,
                COUNT(DISTINCT CASE WHEN patient_demographics.gender = ? THEN filtered_patients.id END) as female,
                COUNT(DISTINCT CASE WHEN patient_demographics.gender = ? THEN filtered_patients.id END) as other',
                [false, true, 'Male', 'Female', 'Other']
            )
            ->first();

        return [
            'total' => (int) $summary->total,
            'registered' => (int) $summary->registered,
            'walk_in' => (int) $summary->walk_in,
            'male' => (int) $summary->male,
            'female' => (int) $summary->female,
            'other' => (int) $summary->other,
        ];
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
