<?php

namespace App\Livewire\Clinical;

use App\Models\Admission;
use App\Models\Continuation;
use App\Models\DrugChart;
use App\Models\FluidBalance;
use App\Models\InvestigationRequest;
use App\Models\Observation;
use App\Models\Prescription;
use App\Models\VitalSign;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class ClinicalRecordIndex extends Component
{
    use WithPagination;

    public string $type;
    public string $search = '';
    public int $perPage = 15;
    protected string $paginationTheme = 'tailwind';

    private array $config = [
        'vital-signs' => [
            'title' => 'Vital Signs',
            'roles' => ['nurse', 'doctor'],
            'route' => 'patient.vitalsign.create',
            'icon' => 'bi-heart-pulse',
        ],
        'observations' => [
            'title' => 'Observations',
            'roles' => ['nurse', 'doctor'],
            'route' => 'patient.observation.record',
            'icon' => 'bi-eye',
        ],
        'drug-charts' => [
            'title' => 'Drug Chart',
            'roles' => ['nurse', 'doctor'],
            'route' => 'patient.drugchart.record',
            'icon' => 'bi-capsule-pill',
        ],
        'fluid-balances' => [
            'title' => 'Fluid Balance',
            'roles' => ['nurse', 'doctor'],
            'route' => 'patient.fluidbalance.record',
            'icon' => 'bi-droplet',
        ],
        'investigations' => [
            'title' => 'Investigation Requests',
            'roles' => ['nurse', 'doctor'],
            'route' => 'patient.investigation.create',
            'icon' => 'bi-clipboard2-pulse',
        ],
        'admissions' => [
            'title' => 'Admissions / Ward-Bed',
            'roles' => ['doctor'],
            'route' => 'patient.admission.create',
            'icon' => 'bi-hospital',
        ],
        'prescriptions' => [
            'title' => 'Prescriptions',
            'roles' => ['doctor'],
            'route' => 'patient.prescription.create',
            'icon' => 'bi-prescription2',
        ],
        'continuations' => [
            'title' => 'Continuation Sheet',
            'roles' => ['doctor'],
            'route' => 'patient.continuation.create',
            'icon' => 'bi-pencil',
        ],
    ];

    public function mount(string $type): void
    {
        abort_unless(isset($this->config[$type]), 404);

        $roles = $this->config[$type]['roles'];
        abort_unless(auth()->user()?->hasAnyRole($roles) || auth()->user()?->hasRole('administrator'), 403);

        $this->type = $type;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('components.clinical.clinical-record-index', [
            'config' => $this->config[$this->type],
            'records' => $this->records(),
            'type' => $this->type,
        ]);
    }

    private function records()
    {
        return match ($this->type) {
            'vital-signs' => VitalSign::query()
                ->with(['patientVisit.patient.demographic', 'recordedBy'])
                ->when($this->search !== '', fn ($query) => $this->searchPatientVisit($query))
                ->latest('recorded_date')
                ->paginate($this->perPage),

            'observations' => Observation::query()
                ->with(['patientVisit.patient.demographic', 'recordedBy'])
                ->when($this->search !== '', fn ($query) => $this->searchPatientVisit($query))
                ->latest()
                ->paginate($this->perPage),

            'drug-charts' => DrugChart::query()
                ->with(['medicine', 'dispensedBy', 'prescriptionItem.prescription.patientVisit.patient.demographic'])
                ->when($this->search !== '', fn ($query) => $query->whereHas('prescriptionItem.prescription.patientVisit.patient', fn ($patientQuery) => $this->searchPatient($patientQuery)))
                ->latest()
                ->paginate($this->perPage),

            'fluid-balances' => FluidBalance::query()
                ->with(['recordedBy', 'admission.patientVisit.patient.demographic'])
                ->when($this->search !== '', fn ($query) => $query->whereHas('admission.patientVisit.patient', fn ($patientQuery) => $this->searchPatient($patientQuery)))
                ->latest()
                ->paginate($this->perPage),

            'investigations' => InvestigationRequest::query()
                ->with(['investigation', 'requestedBy', 'patientVisit.patient.demographic'])
                ->when($this->shouldScopeDoctorOwnedRecords(), fn ($query) => $query->where('requested_by', auth()->id()))
                ->when($this->search !== '', fn ($query) => $this->searchPatientVisit($query))
                ->latest()
                ->paginate($this->perPage),

            'admissions' => Admission::query()
                ->with(['bed.ward', 'admittedBy', 'patientVisit.patient.demographic'])
                ->when($this->search !== '', fn ($query) => $this->searchPatientVisit($query))
                ->latest()
                ->paginate($this->perPage),

            'prescriptions' => Prescription::query()
                ->with(['prescribedBy', 'patientVisit.patient.demographic', 'prescriptionItems.medicine'])
                ->when($this->search !== '', fn ($query) => $this->searchPatientVisit($query))
                ->latest()
                ->paginate($this->perPage),

            'continuations' => Continuation::query()
                ->with(['writtenBy', 'patientVisit.patient.demographic'])
                ->when($this->search !== '', fn ($query) => $this->searchPatientVisit($query))
                ->latest()
                ->paginate($this->perPage),
        };
    }

    private function searchPatientVisit($query)
    {
        return $query->whereHas('patientVisit.patient', fn ($patientQuery) => $this->searchPatient($patientQuery));
    }

    private function searchPatient($query)
    {
        $search = '%' . $this->search . '%';

        return $query
            ->where('hospital_number', 'like', $search)
            ->orWhereHas('demographic', fn ($demographicQuery) => $demographicQuery
                ->where('first_name', 'like', $search)
                ->orWhere('last_name', 'like', $search)
                ->orWhere('middle_name', 'like', $search)
            );
    }

    private function shouldScopeDoctorOwnedRecords(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('doctor')
            && ! $user->hasRole('administrator')
            && ! $user->hasRole('nurse');
    }
}

