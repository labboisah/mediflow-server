<?php

namespace App\Livewire\Doctor;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Service;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class PatientManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $requestStatus = '';
    public string $visitStatus = 'Active';
    public string $serviceId = '';
    public int $perPage = 10;

    protected string $paginationTheme = 'tailwind';

    protected array $queryString = [
        'search' => ['except' => ''],
        'requestStatus' => ['except' => ''],
        'visitStatus' => ['except' => 'Active'],
        'serviceId' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function render()
    {
        $baseQuery = $this->requestsQuery();

        return view('components.doctor.patient-management', [
            'requests' => (clone $baseQuery)->paginate($this->perPage),
            'allPatients' => $this->allPatientsSearch(),
            'services' => $this->departmentServices(),
            'pageTitle' => 'Doctor Patient Management',
            'pageSubtitle' => 'Review doctor service requests and open patient clinical profiles quickly.',
            'profileRoute' => 'doctor.patient.show',
            'emptyMessage' => 'No doctor patient requests match your current filters.',
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'activeVisits' => $this->requestsQuery(false)
                    ->whereHas('patientVisit', fn (Builder $query) => $query->where('status', 'Active'))
                    ->distinct('patient_visit_id')
                    ->count('patient_visit_id'),
            ],
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRequestStatus(): void
    {
        $this->resetPage();
    }

    public function updatedVisitStatus(): void
    {
        $this->resetPage();
    }

    public function updatedServiceId(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function refreshList(): void
    {
        // Polling refreshes the table while this page is visible.
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'requestStatus', 'serviceId']);
        $this->visitStatus = 'Active';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function completeRequest(int $requestId): void
    {
        $serviceRequest = $this->departmentRequest($requestId);

        if (! $serviceRequest) {
            $this->dispatch('toast', message: 'This service request could not be found for your department.', type: 'danger');
            return;
        }

        if ($serviceRequest->status === 'completed') {
            $this->dispatch('toast', message: 'This service request is already completed.', type: 'warning');
            return;
        }

        $serviceRequest->update([
            'status' => 'completed',
            'performed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        $serviceRequest->patientVisit?->visitActivities()->create([
            'recorded_by' => auth()->id(),
            'activity' => 'Service "' . ($serviceRequest->service?->name ?? 'Doctor service') . '" marked as completed by ' . auth()->user()->name,
        ]);

        $this->dispatch('toast', message: 'Service request marked as completed.', type: 'success');
    }

    public function closeVisit(int $visitId): void
    {
        $visit = PatientVisit::whereKey($visitId)
            ->whereHas('patient')
            ->whereHas('serviceRequests.service', function (Builder $query) {
                $query->where('department_id', auth()->user()->department_id);
            })
            ->first();

        if (! $visit) {
            $this->dispatch('toast', message: 'This patient visit could not be found for your department.', type: 'danger');
            return;
        }

        if ($visit->status === 'Closed') {
            $this->dispatch('toast', message: 'This patient visit is already closed.', type: 'warning');
            return;
        }

        $visit->update(['status' => 'Closed']);

        $visit->visitActivities()->create([
            'recorded_by' => auth()->id(),
            'activity' => 'Patient visit marked as closed by ' . auth()->user()->name,
        ]);

        $this->dispatch('toast', message: 'Patient visit marked as closed.', type: 'success');
    }

    private function requestsQuery(bool $applyFilters = true): Builder
    {
        $query = ServiceRequest::query()
            ->with([
                'service',
                'patientVisit.patient.demographic',
                'patientVisit.patient.nextOfKin',
            ])
            ->whereHas('service', function (Builder $query) {
                $query->where('department_id', auth()->user()->department_id);
            })
            ->whereHas('patientVisit.patient')
            ->addSelect([
                'latest_visit_at' => PatientVisit::select('created_at')
                    ->whereColumn('patient_visits.id', 'service_requests.patient_visit_id')
                    ->latest('created_at')
                    ->limit(1),
            ])
            ->orderByDesc('latest_visit_at')
            ->latest('requested_at')
            ->latest('created_at');

        if (! $applyFilters) {
            return $query;
        }

        return $query
            ->when($this->requestStatus !== '', fn (Builder $query) => $query->where('status', $this->requestStatus))
            ->when($this->visitStatus !== '', function (Builder $query) {
                $query->whereHas('patientVisit', fn (Builder $visitQuery) => $visitQuery->where('status', $this->visitStatus));
            })
            ->when($this->serviceId !== '', fn (Builder $query) => $query->where('service_id', $this->serviceId))
            ->when(trim($this->search) !== '', function (Builder $query) {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->whereHas('service', fn (Builder $serviceQuery) => $serviceQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('patientVisit.patient', fn (Builder $patientQuery) => $patientQuery->where('hospital_number', 'like', "%{$search}%"))
                        ->orWhereHas('patientVisit.patient.demographic', function (Builder $demographicQuery) use ($search) {
                            $demographicQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('patientVisit.patient.nextOfKin', function (Builder $nextOfKinQuery) use ($search) {
                            $nextOfKinQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('telephone', 'like', "%{$search}%");
                        });
                });
            });
    }

    private function departmentServices()
    {
        if (! auth()->user()->department_id) {
            return collect();
        }

        return Service::where('department_id', auth()->user()->department_id)
            ->orderBy('name')
            ->get();
    }

    private function allPatientsSearch()
    {
        $search = trim($this->search);

        if ($search === '') {
            return collect();
        }

        return Patient::with(['demographic', 'nextOfKin', 'patientVisits' => fn ($query) => $query->latest('created_at')])
            ->where(function (Builder $query) use ($search) {
                $query
                    ->where('hospital_number', 'like', "%{$search}%")
                    ->orWhereHas('demographic', function (Builder $demographicQuery) use ($search) {
                        $demographicQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('nextOfKin', function (Builder $nextOfKinQuery) use ($search) {
                        $nextOfKinQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('telephone', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->limit(25)
            ->get();
    }

    private function departmentRequest(int $requestId): ?ServiceRequest
    {
        return ServiceRequest::with(['service', 'patientVisit.visitActivities'])
            ->whereKey($requestId)
            ->whereHas('service', function (Builder $query) {
                $query->where('department_id', auth()->user()->department_id);
            })
            ->first();
    }
}

