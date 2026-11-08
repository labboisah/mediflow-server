<?php

namespace App\Livewire\Patient;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class PatientManagement extends Component
{
    use WithPagination;

    public string $mode = 'clinical';
    public string $search = '';
    public string $gender = '';
    public string $patientType = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'registration_date';
    public string $sortDirection = 'desc';
    public int $perPage = 15;
    protected string $paginationTheme = 'tailwind';

    protected array $queryString = [
        'search' => ['except' => ''],
        'gender' => ['except' => ''],
        'patientType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'registration_date'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 15],
    ];

    public function mount(string $mode = 'clinical'): void
    {
        $this->mode = $mode;
    }

    public function render()
    {
        $query = $this->patientQuery();

        return view('components.patient.patient-management', [
            'patients' => (clone $query)->paginate($this->perPage),
            'totalPatients' => Patient::count(),
            'filteredCount' => (clone $query)->count(),
            'registeredToday' => Patient::whereDate('registration_date', today())->count(),
            'walkInCount' => Patient::where('is_walkIn', true)->count(),
            'canManageRecords' => $this->canManageRecords(),
            'hasActiveFilters' => $this->hasActiveFilters(),
            'feedbackMessage' => $this->feedbackMessage(),
            'pageTitle' => $this->pageTitle(),
            'pageSubtitle' => $this->pageSubtitle(),
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGender(): void
    {
        $this->resetPage();
    }

    public function updatedPatientType(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array($this->perPage, [10, 15, 25, 50], true) ? $this->perPage : 15;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['hospital_number', 'registration_date', 'created_at'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'gender', 'patientType', 'dateFrom', 'dateTo']);
        $this->resetPage();

        $this->dispatch('toast', message: 'Patient filters cleared.', type: 'success');
    }

    private function patientQuery(): Builder
    {
        $search = trim($this->search);

        return Patient::query()
            ->with(['demographic', 'fileType'])
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
            ->when($this->gender !== '', function (Builder $query) {
                $query->whereHas('demographic', fn (Builder $demographic) => $demographic->where('gender', $this->gender));
            })
            ->when($this->patientType === 'walk_in', fn (Builder $query) => $query->where('is_walkIn', true))
            ->when($this->patientType === 'registered', fn (Builder $query) => $query->where('is_walkIn', false))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('registration_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('registration_date', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection);
    }

    private function pageTitle(): string
    {
        return $this->mode === 'record' ? 'Patient List' : 'Patient Management';
    }

    private function pageSubtitle(): string
    {
        return $this->mode === 'record'
            ? 'Search, filter, and open patient records quickly.'
            : 'Search, filter, and open patient clinical profiles quickly.';
    }
    private function canManageRecords(): bool
    {
        $user = auth()->user();

        return $this->mode === 'record'
            && $user
            && ($user->hasRole('record') || $user->hasAnyPermission(['patient.create', 'patient.update']));
    }

    private function hasActiveFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->gender !== ''
            || $this->patientType !== ''
            || $this->dateFrom !== ''
            || $this->dateTo !== '';
    }

    private function feedbackMessage(): ?array
    {
        if (trim($this->search) !== '' && strlen(trim($this->search)) < 2) {
            return [
                'type' => 'warning',
                'message' => 'Type at least 2 characters to search by hospital number, name, phone, or email.',
            ];
        }

        if ($this->dateFrom !== '' && $this->dateTo !== '' && $this->dateFrom > $this->dateTo) {
            return [
                'type' => 'danger',
                'message' => 'The start date cannot be later than the end date.',
            ];
        }

        return null;
    }
}



