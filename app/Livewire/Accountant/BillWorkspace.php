<?php

namespace App\Livewire\Accountant;

use App\Models\Bill;
use App\Models\Investigation;
use App\Models\InvestigationRequest;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\WalkinPatient;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.modern')]
class BillWorkspace extends Component
{
    use WithPagination;

    public string $mode = 'index';
    public string $listMode = 'today';
    public ?int $editingBillId = null;
    public string $search = '';
    public string $status = '';
    public int $perPage = 10;

    public string $hospitalNumber = '';
    public string $walkinName = '';
    public string $walkinPhone = '';
    public string $walkinEmail = '';
    public int $discount = 0;
    public string $issuedDate = '';
    public string $dueDate = '';
    public string $billStatus = 'pending';
    public array $services = [];
    public array $investigations = [];

    protected array $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount(?Bill $bill = null): void
    {
        $this->mode = request()->routeIs('accountant.bills.create') ? 'create' : 'index';
        $this->listMode = request()->routeIs('accountant.bills.unpaid') ? 'unpaid' : (request()->routeIs('accountant.bills.deleted') ? 'deleted' : 'today');
        $this->issuedDate = today()->toDateString();
        $this->dueDate = today()->addDays(5)->toDateString();
        $this->services = [$this->blankItem()];
        $this->investigations = [];

        if ($bill && $bill->exists) {
            $this->edit($bill->id);
        }

        if (request()->filled('patient_id') || request()->filled('patient')) {
            $patient = Patient::find(request('patient_id', request('patient')));
            $this->hospitalNumber = $patient?->hospital_number ?? '';
        }
    }

    public function render()
    {
        $summaryBills = $this->summaryBillsQuery();
        $filteredBills = $this->filteredBillsQuery();

        return view('components.accountant.bill-workspace', [
            'bills' => $filteredBills->paginate($this->perPage),
            'summary' => [
                'count' => (clone $summaryBills)->count(),
                'amount' => (float) (clone $summaryBills)->sum('amount'),
                'discount' => (float) round((clone $summaryBills)->sum(DB::raw('(amount * discount / 100)')), 2),
                'due' => (float) (clone $summaryBills)->sum('due_amount'),
            ],
            'serviceGroups' => Service::active()->orderBy('category')->orderBy('name')->get()->groupBy('category'),
            'investigationGroups' => Investigation::with('investigationType')->orderBy('name')->get()->groupBy(fn ($investigation) => $investigation->investigationType?->name ?? 'Other'),
            'selectedPatient' => $this->selectedPatient(),
            'totals' => $this->totals(),
            'isEditing' => $this->editingBillId !== null,
            'listMode' => $this->listMode,
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function addService(): void
    {
        $this->services[] = $this->blankItem();
    }

    public function removeService(int $index): void
    {
        unset($this->services[$index]);
        $this->services = array_values($this->services);

        if (empty($this->services)) {
            $this->services = [$this->blankItem()];
        }
    }

    public function addInvestigation(): void
    {
        $this->investigations[] = $this->blankItem();
    }

    public function removeInvestigation(int $index): void
    {
        unset($this->investigations[$index]);
        $this->investigations = array_values($this->investigations);
    }

    public function createMode(): void
    {
        $this->resetForm();
        $this->mode = 'create';
    }

    public function indexMode(): void
    {
        $this->resetForm();
        $this->mode = 'index';
    }

    public function edit(int $billId): void
    {
        $bill = $this->manageableUnpaidBill($billId);

        if (! $bill) {
            $this->dispatch('toast', message: 'Only unpaid bills can be edited.', type: 'danger');
            return;
        }

        $bill->load(['services', 'investigations', 'patientVisit.patient.demographic', 'walkinPatient']);

        $this->editingBillId = $bill->id;
        $this->mode = 'edit';
        $this->hospitalNumber = $bill->patientVisit?->patient?->hospital_number ?? '';
        $this->walkinName = $bill->walkinPatient?->name ?? '';
        $this->walkinPhone = $bill->walkinPatient?->phone_number ?? '';
        $this->walkinEmail = $bill->walkinPatient?->address ?? '';
        $this->discount = (int) $bill->discount;
        $this->issuedDate = $bill->issued_date?->toDateString() ?? today()->toDateString();
        $this->dueDate = $bill->due_date?->toDateString() ?? today()->addDays(5)->toDateString();
        $this->billStatus = $bill->status;
        $this->services = $bill->services->map(fn ($service) => [
            'id' => (string) $service->id,
            'quantity' => (int) $service->pivot->quantity,
        ])->values()->all() ?: [$this->blankItem()];
        $this->investigations = $bill->investigations->map(fn ($investigation) => [
            'id' => (string) $investigation->id,
            'quantity' => (int) $investigation->pivot->quantity,
        ])->values()->all();
    }

    public function save()
    {
        $issuedDateRules = ['required', 'date'];

        if ($this->editingBillId === null) {
            $issuedDateRules[] = 'date_equals:' . today()->toDateString();
        }

        $validated = $this->validate([
            'hospitalNumber' => ['nullable', 'string', 'max:100'],
            'walkinName' => ['nullable', 'string', 'max:255'],
            'walkinPhone' => ['nullable', 'string', 'max:20'],
            'walkinEmail' => ['nullable', 'email', 'max:255'],
            'discount' => ['required', 'integer', 'min:0', 'max:100'],
            'issuedDate' => $issuedDateRules,
            'dueDate' => ['required', 'date', 'after_or_equal:issuedDate'],
            'billStatus' => ['required', 'in:pending,paid,partial,cancelled'],
            'services' => ['array'],
            'services.*.id' => ['nullable', 'exists:services,id'],
            'services.*.quantity' => ['nullable', 'integer', 'min:1'],
            'investigations' => ['array'],
            'investigations.*.id' => ['nullable', 'exists:investigations,id'],
            'investigations.*.quantity' => ['nullable', 'integer', 'min:1'],
        ], [
            'issuedDate.date_equals' => 'New bills can only be created for today.',
        ]);

        $serviceRows = $this->normalizedItems($this->services);
        $investigationRows = $this->normalizedItems($this->investigations);

        if (empty($serviceRows) && empty($investigationRows)) {
            $this->addError('items', 'Select at least one service or investigation.');
            return null;
        }

        if (trim($this->hospitalNumber) === '' && trim($this->walkinName) === '') {
            $this->addError('hospitalNumber', 'Enter a hospital number or provide walk-in patient details.');
            $this->addError('walkinName', 'Walk-in name is required when no hospital number is supplied.');
            return null;
        }

        try {
            $bill = DB::transaction(function () use ($serviceRows, $investigationRows) {
                return $this->editingBillId
                    ? $this->updateBill($serviceRows, $investigationRows)
                    : $this->createBill($serviceRows, $investigationRows);
            });

            $this->dispatch('toast', message: $this->editingBillId ? 'Bill updated successfully.' : 'Bill created successfully.', type: 'success');

            return redirect()->route('accountant.bills.show', $bill);
        } catch (Throwable $exception) {
            report($exception);

            $message = trim($exception->getMessage()) ?: 'Bill could not be saved. Please review the bill and try again.';
            $this->addError('bill', 'Bill save failed: ' . $message);
            $this->dispatch('toast', message: 'Bill save failed: ' . $message, type: 'danger');

            return null;
        }
    }

    public function delete(int $billId): void
    {
        $bill = $this->manageableUnpaidBill($billId);

        if (! $bill) {
            $this->dispatch('toast', message: 'Only unpaid bills can be deleted.', type: 'danger');
            return;
        }

        if ($reason = $bill->softDeleteBlockReason()) {
            $this->dispatch('toast', message: $reason, type: 'warning');
            return;
        }

        DB::transaction(function () use ($bill) {
            $this->softDeletePendingLinkedRequests($bill);
            $bill->delete();
        });

        $this->dispatch('toast', message: 'Bill deleted successfully.', type: 'success');
    }

    public function restore(int $billId): void
    {
        $bill = $this->accountantBillsQuery()->withTrashed()->find($billId);

        if (! $bill || ! $bill->trashed()) {
            $this->dispatch('toast', message: 'Deleted bill not found.', type: 'danger');
            return;
        }

        DB::transaction(function () use ($bill) {
            $bill->restore();
            $this->restorePendingLinkedRequests($bill);
        });
        $this->dispatch('toast', message: 'Bill restored successfully.', type: 'success');
    }

    private function createBill(array $serviceRows, array $investigationRows): Bill
    {
        [$visit, $walkinId] = $this->resolveBillSubject();
        [$amount, $servicePayload, $investigationPayload] = $this->buildBillPayload($serviceRows, $investigationRows);
        $dueAmount = round($amount * (1 - ($this->discount / 100)), 2);

        $bill = Bill::create([
            'patient_visit_id' => $visit?->id,
            'walkin_id' => $walkinId,
            'bill_number' => Bill::generateBillNumber(),
            'service_description' => $this->billDescription($serviceRows, $investigationRows),
            'amount' => $amount,
            'due_amount' => $dueAmount,
            'discount' => $this->discount,
            'issued_by' => auth()->id(),
            'status' => 'pending',
            'issued_date' => $this->issuedDate,
            'due_date' => $this->dueDate,
        ]);

        $this->syncBillItems($bill, $servicePayload, $investigationPayload);
        $this->syncRequests($bill, $serviceRows, $investigationRows);
        $this->createLabourAdmissionBills($visit, $walkinId, $serviceRows);

        return $bill;
    }

    private function updateBill(array $serviceRows, array $investigationRows): Bill
    {
        $bill = $this->manageableUnpaidBill($this->editingBillId);

        if (! $bill) {
            throw new \RuntimeException('This bill is not available for editing.');
        }

        [$amount, $servicePayload, $investigationPayload] = $this->buildBillPayload($serviceRows, $investigationRows);
        $dueAmount = round($amount * (1 - ($this->discount / 100)), 2);
        $paidAmount = (float) $bill->payments()->where('status', 'completed')->sum('amount');

        if ($paidAmount > $dueAmount) {
            throw new \RuntimeException('Bill due amount cannot be less than completed payments already collected (' . number_format($paidAmount, 2) . ').');
        }

        $bill->update([
            'service_description' => $this->billDescription($serviceRows, $investigationRows),
            'amount' => $amount,
            'due_amount' => $dueAmount,
            'discount' => $this->discount,
            'status' => $this->billStatus,
            'issued_date' => $this->issuedDate,
            'due_date' => $this->dueDate,
        ]);

        $this->syncBillItems($bill, $servicePayload, $investigationPayload);
        $this->syncEditableRequests($bill, $serviceRows, $investigationRows);

        return $bill;
    }

    private function syncBillItems(Bill $bill, array $servicePayload, array $investigationPayload): void
    {
        $bill->services()->sync($servicePayload);
        $bill->investigations()->sync($investigationPayload);
    }

    private function syncRequests(Bill $bill, array $serviceRows, array $investigationRows): void
    {
        foreach ($serviceRows as $row) {
            ServiceRequest::create([
                'service_id' => $row['id'],
                'patient_visit_id' => $bill->patient_visit_id,
                'walkin_id' => $bill->walkin_id,
                'bill_id' => $bill->id,
                'requested_by' => $bill->issued_by,
                'requested_at' => now(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'clinical_diagnoses' => 'Requested via billing system',
            ]);
        }

        foreach ($investigationRows as $row) {
            $request = InvestigationRequest::create([
                'investigation_id' => $row['id'],
                'patient_visit_id' => $bill->patient_visit_id,
                'walkin_id' => $bill->walkin_id,
                'bill_id' => $bill->id,
                'requested_by' => $bill->issued_by,
                'requested_at' => now(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'clinical_diagnoses' => 'Requested via billing system',
            ]);

            if (! $bill->investigation_request_id) {
                $bill->update(['investigation_request_id' => $request->id]);
            }
        }
    }

    private function createLabourAdmissionBills($visit, ?int $walkinId, array $serviceRows): void
    {
        if (! $visit) {
            return;
        }

        foreach ($serviceRows as $row) {
            if (! in_array((int) $row['id'], [13, 14], true)) {
                continue;
            }

            $service = Service::find($row['id']);
            $ward = Ward::find(2);

            if (! $service || ! $ward) {
                continue;
            }

            $bed = $ward->getAvailableBed();

            $visit->admissions()->create([
                'date' => now(),
                'time' => now()->toTimeString(),
                'note' => $service->name,
                'bed_id' => $bed?->id,
                'status' => 'Registered',
                'admitted_by' => auth()->id(),
            ]);

            $bed?->update(['status' => 'occupied']);

            Bill::create([
                'patient_visit_id' => $visit->id,
                'walkin_id' => $walkinId,
                'bill_number' => Bill::generateBillNumber(),
                'service_description' => 'Bed space charge for ' . $service->name,
                'amount' => $ward->price,
                'due_amount' => max(0, round($ward->price * (1 - ($this->discount / 100)), 2)),
                'discount' => $this->discount,
                'issued_by' => auth()->id(),
                'status' => 'pending',
                'issued_date' => now(),
                'due_date' => now()->addDays(7),
            ]);
        }
    }

    private function resolveBillSubject(): array
    {
        $patient = $this->selectedPatient();

        if ($patient) {
            $visit = $patient->currentVisit();

            if (! $visit) {
                $visit = $patient->patientVisits()->create([
                    'visit_date' => now(),
                    'visit_type' => 'Walk-in',
                    'created_by' => auth()->id(),
                    'reason_for_visit' => 'Walk-in bill creation',
                ]);
            }

            return [$visit, null];
        }

        $walkinPatient = WalkinPatient::create([
            'name' => $this->walkinName,
            'phone_number' => $this->walkinPhone ?: 'N/A',
            'address' => $this->walkinEmail ?: null,
        ]);

        return [null, $walkinPatient->id];
    }

    private function selectedPatient(): ?Patient
    {
        $number = trim($this->hospitalNumber);

        if ($number === '') {
            return null;
        }

        return Patient::with('demographic')->where('hospital_number', $number)->first();
    }

    private function buildBillPayload(array $serviceRows, array $investigationRows): array
    {
        $amount = 0.0;
        $servicePayload = [];
        $investigationPayload = [];

        foreach ($serviceRows as $row) {
            $service = Service::findOrFail($row['id']);
            $quantity = max(1, (int) $row['quantity']);
            $subtotal = (float) $service->price * $quantity;
            $amount += $subtotal;

            $servicePayload[$service->id] = [
                'quantity' => ($servicePayload[$service->id]['quantity'] ?? 0) + $quantity,
                'unit_price' => $service->price,
                'subtotal' => ($servicePayload[$service->id]['subtotal'] ?? 0) + $subtotal,
            ];
        }

        foreach ($investigationRows as $row) {
            $investigation = Investigation::findOrFail($row['id']);
            $quantity = max(1, (int) $row['quantity']);
            $subtotal = (float) $investigation->price * $quantity;
            $amount += $subtotal;

            $investigationPayload[$investigation->id] = [
                'quantity' => ($investigationPayload[$investigation->id]['quantity'] ?? 0) + $quantity,
                'unit_price' => $investigation->price,
                'subtotal' => ($investigationPayload[$investigation->id]['subtotal'] ?? 0) + $subtotal,
            ];
        }

        return [$amount, $servicePayload, $investigationPayload];
    }

    private function billDescription(array $serviceRows, array $investigationRows): string
    {
        return collect([
            ! empty($serviceRows) ? 'Services' : null,
            ! empty($investigationRows) ? 'Investigations' : null,
        ])->filter()->implode(' & ') ?: 'Bill items';
    }

    private function normalizedItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && ! empty($item['id']))
            ->map(fn ($item) => ['id' => (int) $item['id'], 'quantity' => max(1, (int) ($item['quantity'] ?? 1))])
            ->values()
            ->all();
    }

    private function totals(): array
    {
        [$amount] = $this->buildBillPayload($this->normalizedItems($this->services), $this->normalizedItems($this->investigations));
        $discount = round($amount * ($this->discount / 100), 2);

        return [
            'amount' => $amount,
            'discount' => $discount,
            'due' => max(0, $amount - $discount),
        ];
    }

    private function todayBillsQuery(): Builder
    {
        return $this->accountantBillsQuery()
            ->whereDate('issued_date', today());
    }

    private function accountantBillsQuery(): Builder
    {
        return Bill::query()->where('issued_by', auth()->id());
    }

    private function filteredBillsQuery(): Builder
    {
        $query = $this->summaryBillsQuery();

        return $query->with(['patientVisit.patient.demographic', 'walkinPatient', 'payments'])
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when(trim($this->search) !== '', function (Builder $query) {
                $search = trim($this->search);

                $query->where(function (Builder $builder) use ($search) {
                    $builder->where('bill_number', 'like', "%{$search}%")
                        ->orWhere('service_description', 'like', "%{$search}%")
                        ->orWhereHas('walkinPatient', fn (Builder $walkin) => $walkin->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('patientVisit.patient.demographic', fn (Builder $demo) => $demo->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]));
                });
            })
            ->latest('issued_date')
            ->latest('id');
    }

    private function summaryBillsQuery(): Builder
    {
        return match ($this->listMode) {
            'unpaid' => $this->accountantBillsQuery()
                ->whereIn('status', ['pending', 'partial'])
                ->whereRaw('(due_amount - COALESCE((select sum(amount) from payments where payments.bill_id = bills.id and payments.status = "completed" and payments.deleted_at is null), 0)) > 0'),
            'deleted' => $this->accountantBillsQuery()->onlyTrashed(),
            default => $this->todayBillsQuery(),
        };
    }

    private function manageableUnpaidBill(?int $billId): ?Bill
    {
        if (! $billId) {
            return null;
        }

        $bill = Bill::with('payments')->find($billId);

        return $bill?->canBeManagedAsUnpaidByAccountant(auth()->user()) ? $bill : null;
    }

    private function syncEditableRequests(Bill $bill, array $serviceRows, array $investigationRows): void
    {
        $serviceIds = collect($serviceRows)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $investigationIds = collect($investigationRows)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $bill->serviceRequests()
            ->whereIn('status', ['pending', 'Pending'])
            ->whereNotIn('service_id', $serviceIds ?: [0])
            ->delete();

        foreach ($serviceRows as $row) {
            $bill->serviceRequests()->firstOrCreate(
                [
                    'service_id' => $row['id'],
                    'status' => 'pending',
                ],
                [
                    'patient_visit_id' => $bill->patient_visit_id,
                    'walkin_id' => $bill->walkin_id,
                    'requested_by' => $bill->issued_by,
                    'requested_at' => now(),
                    'payment_status' => 'pending',
                    'clinical_diagnoses' => 'Requested via billing system',
                ]
            );
        }

        $bill->investigationRequests()
            ->whereIn('status', ['pending', 'Pending'])
            ->whereNotIn('investigation_id', $investigationIds ?: [0])
            ->delete();

        foreach ($investigationRows as $row) {
            $request = $bill->investigationRequests()->firstOrCreate(
                [
                    'investigation_id' => $row['id'],
                    'status' => 'pending',
                ],
                [
                    'patient_visit_id' => $bill->patient_visit_id,
                    'walkin_id' => $bill->walkin_id,
                    'requested_by' => $bill->issued_by,
                    'requested_at' => now(),
                    'payment_status' => 'pending',
                    'clinical_diagnoses' => 'Requested via billing system',
                ]
            );

            if (! $bill->investigation_request_id) {
                $bill->update(['investigation_request_id' => $request->id]);
            }
        }
    }

    private function softDeletePendingLinkedRequests(Bill $bill): void
    {
        $bill->serviceRequests()->whereIn('status', ['pending', 'Pending'])->delete();
        $bill->investigationRequests()->whereIn('status', ['pending', 'Pending'])->delete();
    }

    private function restorePendingLinkedRequests(Bill $bill): void
    {
        ServiceRequest::withTrashed()
            ->where('bill_id', $bill->id)
            ->whereIn('status', ['pending', 'Pending'])
            ->onlyTrashed()
            ->restore();

        InvestigationRequest::withTrashed()
            ->where('bill_id', $bill->id)
            ->whereIn('status', ['pending', 'Pending'])
            ->onlyTrashed()
            ->restore();
    }

    private function blankItem(): array
    {
        return ['id' => '', 'quantity' => 1];
    }

    private function resetForm(): void
    {
        $this->reset(['editingBillId', 'hospitalNumber', 'walkinName', 'walkinPhone', 'walkinEmail', 'discount', 'billStatus', 'services', 'investigations']);
        $this->issuedDate = today()->toDateString();
        $this->dueDate = today()->addDays(5)->toDateString();
        $this->billStatus = 'pending';
        $this->services = [$this->blankItem()];
        $this->investigations = [];
        $this->resetErrorBag();
    }
}

