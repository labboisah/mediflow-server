<?php

namespace App\Livewire\Admin;

use App\Models\Bill;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class BillManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public string $status = '';
    public string $issuedBy = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public ?int $editingBillId = null;
    public string $billNumber = '';
    public string $serviceDescription = '';
    public string $amount = '';
    public string $discount = '';
    public string $issuedDate = '';
    public string $dueDate = '';
    public string $billStatus = 'pending';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingIssuedBy(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function edit(int $billId): void
    {
        abort_unless($this->canManageFinance(), 403);

        $bill = Bill::findOrFail($billId);

        $this->editingBillId = $bill->id;
        $this->billNumber = $bill->bill_number;
        $this->serviceDescription = $bill->service_description ?? '';
        $this->amount = (string) $bill->amount;
        $this->discount = (string) $bill->discount;
        $this->issuedDate = optional($bill->issued_date)->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->dueDate = optional($bill->due_date)->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->billStatus = $bill->status;
    }

    public function save(): void
    {
        abort_unless($this->canManageFinance(), 403);

        $validated = $this->validate([
            'serviceDescription' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0', 'max:100'],
            'issuedDate' => ['required', 'date'],
            'dueDate' => ['required', 'date', 'after_or_equal:issuedDate'],
            'billStatus' => ['required', 'in:pending,paid,partial,cancelled'],
        ]);

        if (!$this->editingBillId) {
            return;
        }

        $amount = (float) $validated['amount'];
        $discount = (float) $validated['discount'];

        $bill = Bill::findOrFail($this->editingBillId);
        $bill->update([
            'service_description' => $validated['serviceDescription'],
            'amount' => $amount,
            'discount' => $discount,
            'due_amount' => round($amount - ($amount * $discount / 100), 2),
            'issued_date' => $validated['issuedDate'],
            'due_date' => $validated['dueDate'],
            'status' => $validated['billStatus'],
        ]);

        $bill->refreshRequestPaymentStatuses();
        $this->resetForm();

        $this->dispatch('toast', message: 'Bill updated successfully.', type: 'success');
    }

    public function delete(int $billId): void
    {
        abort_unless($this->canManageFinance(), 403);

        $bill = Bill::findOrFail($billId);
        $bill->delete();

        if ($this->editingBillId === $billId) {
            $this->resetForm();
        }

        $this->dispatch('toast', message: 'Bill deleted successfully.', type: 'danger');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'issuedBy', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingBillId',
            'billNumber',
            'serviceDescription',
            'amount',
            'discount',
            'issuedDate',
            'dueDate',
            'billStatus',
        ]);

        $this->billStatus = 'pending';
        $this->resetValidation();
    }

    public function render()
    {
        $query = Bill::query()
            ->with(['patientVisit.patient.demographic', 'walkinPatient', 'issuedBy'])
            ->latest('issued_date')
            ->latest('created_at');

        if ($this->search !== '') {
            $search = $this->search;

            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                    ->orWhere('service_description', 'like', "%{$search}%")
                    ->orWhereHas('walkinPatient', fn ($patient) => $patient->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('patientVisit.patient', fn ($patient) => $patient->where('hospital_number', 'like', "%{$search}%"))
                    ->orWhereHas('patientVisit.patient.demographic', function ($patient) use ($search) {
                        $patient->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->issuedBy !== '') {
            $query->where('issued_by', $this->issuedBy);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('issued_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('issued_date', '<=', $this->dateTo);
        }

        $summaryQuery = clone $query;

        return view('components.admin.bill-management', [
            'bills' => $query->paginate(15),
            'users' => User::query()
                ->whereIn('id', Bill::query()->select('issued_by')->whereNotNull('issued_by'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'billCount' => (clone $summaryQuery)->count(),
            'totalAmount' => (clone $summaryQuery)->sum('amount'),
            'totalDue' => (clone $summaryQuery)->sum('due_amount'),
            'pendingCount' => (clone $summaryQuery)->whereIn('status', ['pending', 'partial'])->count(),
            'canManageFinance' => $this->canManageFinance(),
            'routePrefix' => request()->routeIs('finance.*') ? 'finance' : 'admin',
            'pdfUrl' => route('reports.finance.pdf', array_filter([
                'start_date' => $this->dateFrom ?: null,
                'end_date' => $this->dateTo ?: null,
                'search' => $this->search ?: null,
                'status' => $this->status ?: null,
                'issued_by' => $this->issuedBy ?: null,
            ])),
        ]);
    }

    private function canManageFinance(): bool
    {
        return auth()->user()?->hasRole('administrator') ?? false;
    }
}

