<?php

namespace App\Livewire\Lab;

use Livewire\Component;
use Livewire\WithPagination;

class InvestigationRequestsTable extends Component
{
    use WithPagination;

    public string $search = '';

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $department = auth()->user()->department;

        $requests = $department->investigationRequests()
            ->with([
                'bill',
                'patientVisit.patient.demographic',
                'requestedBy',
                'performedBy',
                'investigation',
            ])
            ->whereHas('bill')
            ->when($this->search, function ($query) {
                $search = '%' . trim($this->search) . '%';

                $query->where(function ($q) use ($search) {
                    $q->whereHas('investigation', function ($investigationQuery) use ($search) {
                        $investigationQuery->where('name', 'like', $search);
                    })
                    ->orWhereHas('requestedBy', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', $search);
                    })
                    ->orWhereHas('performedBy', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', $search);
                    })
                    ->orWhereHas('bill', function ($billQuery) use ($search) {
                        $billQuery->where('status', 'like', $search)
                            ->orWhere('bill_number', 'like', $search);
                    })
                    ->orWhereHas('patientVisit.patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('hospital_number', 'like', $search);
                    })
                    ->orWhereHas('patientVisit.patient.demographic', function ($demographicQuery) use ($search) {
                        $demographicQuery->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search);
                    });
                });
            })
            ->latest()
            ->paginate(10);

        return view('components.lab.investigation-requests-table', [
            'requests' => $requests,
        ]);
    }
}
