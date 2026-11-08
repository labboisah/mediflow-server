<?php

namespace App\Http\Controllers\MedicalDirector;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionManagementController extends Controller
{
    public function index(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $wards = Ward::query()->orderBy('name')->get(['id', 'name']);
        $query = $this->admissionQuery($request, $startDate, $endDate);

        $summaryQuery = clone $query;
        $summaryQuery->setEagerLoads([]);
        $summaryStatuses = $summaryQuery->pluck('status');

        $summary = [
            'total' => $summaryStatuses->count(),
            'active' => $summaryStatuses->filter(fn ($status) => ! in_array(strtolower((string) $status), ['discharged', 'closed', 'absconded', 'sama'], true))->count(),
            'discharged' => $summaryStatuses->filter(fn ($status) => strtolower((string) $status) === 'discharged')->count(),
            'sama' => $summaryStatuses->filter(fn ($status) => strtolower((string) $status) === 'sama')->count(),
            'absconded' => $summaryStatuses->filter(fn ($status) => strtolower((string) $status) === 'absconded')->count(),
        ];

        $admissions = (clone $query)
            ->latest('date')
            ->paginate(25)
            ->withQueryString();
        $routePrefix = $request->routeIs('admin.*') ? 'admin' : 'medical-director';

        return view('medical-director.admissions.index', compact('admissions', 'wards', 'summary', 'startDate', 'endDate', 'routePrefix'));
    }

    public function discharge(Admission $admission)
    {
        DB::transaction(function () use ($admission) {
            $admission->loadMissing(['patientVisit', 'discharge', 'bed']);

            $payload = [
                'reason' => 'Discharged from medical director admission management.',
                'date' => now()->toDateString(),
                'time' => now()->format('H:i'),
                'next_appointment_date' => null,
            ];

            if ($admission->discharge) {
                $admission->discharge->update($payload);
            } else {
                $admission->discharge()->create($payload + ['discharge_by' => auth()->id()]);
            }

            $admission->update(['status' => 'discharged']);
            $admission->patientVisit?->update(['status' => 'discharged']);
            $admission->releaseBedIfNoActiveAdmission();

            $admission->patientVisit?->visitActivities()->create([
                'activity' => 'Patient discharged from medical director admission management.',
                'recorded_by' => auth()->id(),
            ]);
        });

        return back()->with('success', 'Admission discharged and bed released successfully.');
    }

    public function recordSama(Admission $admission)
    {
        DB::transaction(function () use ($admission) {
            $admission->loadMissing(['patientVisit', 'bed']);
            $admission->update(['status' => 'sama']);
            $admission->releaseBedIfNoActiveAdmission();

            $admission->patientVisit?->visitActivities()->create([
                'activity' => 'Patient marked as Sign Against Medical Advice from medical director admission management.',
                'recorded_by' => auth()->id(),
            ]);
        });

        return back()->with('success', 'Admission marked as SAMA and bed released successfully.');
    }

    private function dateRange(Request $request): array
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'ward_id' => ['nullable', 'integer', 'exists:wards,id'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : null;
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : null;

        if ($startDate && $endDate && $startDate->gt($endDate)) {
            return [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    private function admissionQuery(Request $request, ?Carbon $startDate, ?Carbon $endDate): Builder
    {
        $search = trim((string) $request->input('search', ''));

        return Admission::query()
            ->with(['patientVisit.patient.demographic', 'bed.ward', 'admittedBy', 'discharge.dischargedBy'])
            ->when($startDate, fn (Builder $query) => $query->where('date', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->where('date', '<=', $endDate))
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = "%{$search}%";

                $query->where(function (Builder $builder) use ($like) {
                    $builder
                        ->where('status', 'like', $like)
                        ->orWhereHas('bed.ward', fn (Builder $ward) => $ward->where('name', 'like', $like))
                        ->orWhereHas('patientVisit.patient', function (Builder $patient) use ($like) {
                            $patient->where('hospital_number', 'like', $like)
                                ->orWhereHas('demographic', function (Builder $demographic) use ($like) {
                                    $demographic->where('first_name', 'like', $like)
                                        ->orWhere('last_name', 'like', $like)
                                        ->orWhere('phone_number', 'like', $like)
                                        ->orWhere('email', 'like', $like);
                                });
                        });
                });
            })
            ->when($request->filled('ward_id'), function (Builder $query) use ($request) {
                $query->whereHas('bed', fn (Builder $bed) => $bed->where('ward_id', $request->integer('ward_id')));
            })
            ->when($request->filled('gender'), function (Builder $query) use ($request) {
                $query->whereHas('patientVisit.patient.demographic', fn (Builder $demographic) => $demographic->where('gender', $request->input('gender')));
            });
    }
}
