<?php

namespace App\Livewire\Clinical;

use App\Livewire\Clinical\Concerns\ManagesClinicalVisit;
use App\Models\Bill;
use App\Models\BillInvestigation;
use App\Models\Investigation;
use App\Models\InvestigationRequest;
use App\Models\InvestigationType;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class InvestigationRequestWorkspace extends Component
{
    use ManagesClinicalVisit;

    public ?int $editingRequestId = null;
    public string $clinicalDiagnoses = '';
    public string $investigationSearch = '';
    public int $discount = 0;

    public array $rows = [
        ['type_id' => '', 'investigation_id' => '', 'specimen' => ''],
    ];

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;

        if (request()->filled('request')) {
            $this->editRequest((int) request('request'));
        }
    }

    public function render()
    {
        $selectedInvestigationIds = collect($this->rows)
            ->pluck('investigation_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $investigations = Investigation::with('investigationType')
            ->when(trim($this->investigationSearch) !== '', function (Builder $query): void {
                $search = '%' . trim($this->investigationSearch) . '%';

                $query->where(function (Builder $investigationQuery) use ($search): void {
                    $investigationQuery
                        ->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search);
                });
            })
            ->when($selectedInvestigationIds !== [], fn (Builder $query) => $query->orWhereIn('id', $selectedInvestigationIds))
            ->orderBy('name')
            ->limit(50)
            ->get();

        return view('components.clinical.investigation-request-workspace', [
            'types' => InvestigationType::with('investigations')->where('is_active', true)->orderBy('name')->get(),
            'investigations' => $investigations,
            'recentRequests' => $this->currentVisit()->investigationRequests()
                ->with(['investigation.investigationType', 'bill'])
                ->when($this->shouldScopeDoctorOwnedRequests(), fn (Builder $query) => $query->where('requested_by', auth()->id()))
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function toggleInvestigation(int $investigationId): void
    {
        abort_unless(auth()->user()->hasAnyRole(['doctor', 'nurse', 'midwife']), 403);

        $existingIndex = collect($this->rows)->search(
            fn (array $row): bool => (int) ($row['investigation_id'] ?? 0) === $investigationId
        );

        if ($existingIndex !== false) {
            $this->removeRow((int) $existingIndex);
            return;
        }

        $investigation = Investigation::findOrFail($investigationId);
        $this->rows = array_values(array_filter(
            $this->rows,
            fn (array $row): bool => ! empty($row['type_id']) || ! empty($row['investigation_id']) || ! empty($row['specimen'])
        ));
        $this->rows[] = [
            'type_id' => (string) $investigation->investigation_type_id,
            'investigation_id' => (string) $investigation->id,
            'specimen' => '',
        ];
    }

    public function addRow(): void
    {
        $this->rows[] = ['type_id' => '', 'investigation_id' => '', 'specimen' => ''];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function save(): void
{
    $validated = $this->validate([
        'clinicalDiagnoses' => ['required', 'string', 'max:5000'],
        'rows' => ['required', 'array', 'min:1'],
        'rows.*.type_id' => ['required', 'integer', 'exists:investigation_types,id'],
        'rows.*.investigation_id' => ['required', 'integer', 'exists:investigations,id'],
        'rows.*.specimen' => ['nullable', 'string', 'max:255'],
        'discount' => ['nullable', 'integer', 'min:0', 'max:100']
    ]);

    $visit = $this->currentVisit();

    if ($this->editingRequestId) {
        if ($this->updateRequest($validated['rows'][0], $validated['clinicalDiagnoses'])) {
            $this->resetRequestForm();
            $this->feedback('Investigation request and bill updated successfully.');
        }
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | First validate all investigations and calculate total bill amount
    |--------------------------------------------------------------------------
    */
    $investigations = collect();
    $totalAmount = 0;

    foreach ($validated['rows'] as $row) {
        $investigation = Investigation::with('investigationType.department')
            ->findOrFail($row['investigation_id']);

        if ((int) $investigation->investigation_type_id !== (int) $row['type_id']) {
            $this->feedback('One investigation does not belong to the selected investigation type.', 'warning');
            return;
        }

        $amount = (float) ($investigation->price ?? 0);

        $investigations->push([
            'row' => $row,
            'investigation' => $investigation,
            'amount' => $amount,
        ]);

        $totalAmount += $amount;
    }

    /*
    |--------------------------------------------------------------------------
    | Create only one bill for all investigations
    |--------------------------------------------------------------------------
    */
    $discount = (int) ($this->discount ?? 0);
    $dueAmount = round($totalAmount * (1 - ($discount / 100)), 2);

    $bill = $visit->bills()->create([
        'amount' => $totalAmount,
        'discount' => $discount,
        'due_amount' => $dueAmount,
        'service_description' => 'Investigation Bill',
        'status' => 'pending',
        'issued_by' => auth()->id(),
        'issued_date' => now(),
        'bill_number' => Bill::generateBillNumber(),
        'due_date' => now()->addDays(2)->toDateString(),

        // If investigations may belong to different departments, this may need adjustment.
        // For now, we use the first investigation department.
        'department_id' => $investigations->first()['investigation']->investigationType?->department_id,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Create requests normally, but attach them to the same bill
    |--------------------------------------------------------------------------
    */
    foreach ($investigations as $item) {
        $row = $item['row'];
        $investigation = $item['investigation'];
        $amount = $item['amount'];

        $request = $visit->investigationRequests()->create([
            'investigation_id' => $investigation->id,
            'requested_by' => auth()->id(),
            'clinical_diagnoses' => $validated['clinicalDiagnoses'],
            'requested_at' => now(),
            'specimen' => $row['specimen'] ?: null,
            'bill_id' => $bill->id,
        ]);

        InvestigationRequest::updateLabNumber($request->id, $investigation->id);

        $bill->billInvestigations()->create([
            'investigation_id' => $investigation->id,
            'unit_price' => $amount,
            'quantity' => 1,
            'subtotal' => $amount,
        ]);

        $this->logActivity("Investigation request created for {$investigation->name}");
    }

    $this->resetRequestForm();
    $this->feedback('Investigation requests and single accumulated bill created successfully.');
}

    public function editRequest(int $id): void
    {
        $request = $this->editableInvestigationRequests()
            ->with(['investigation', 'bill'])
            ->findOrFail($id);

        if (! $this->billCanBeChanged($request->bill)) {
            $this->feedback('This investigation cannot be edited because its bill already has a completed payment.', 'warning');
            return;
        }

        $this->editingRequestId = $request->id;
        $this->clinicalDiagnoses = (string) $request->clinical_diagnoses;
        $this->discount = (int) ($request->bill?->discount ?? 0);
        $this->rows = [[
            'type_id' => (string) $request->investigation?->investigation_type_id,
            'investigation_id' => (string) $request->investigation_id,
            'specimen' => (string) $request->specimen,

        ]];
    }

    public function deleteRequest(int $id): void
    {
        $request = $this->editableInvestigationRequests()
            ->with(['investigation', 'bill'])
            ->findOrFail($id);

        if (! $this->billCanBeChanged($request->bill)) {
            $this->feedback('This investigation cannot be removed because its bill already has a completed payment.', 'warning');
            return;
        }

        DB::transaction(function () use ($request): void {
            $bill = $request->bill;
            $investigationName = $request->investigation?->name ?? 'investigation';

            if ($bill) {
                $billInvestigation = $bill->billInvestigations()
                    ->where('investigation_id', $request->investigation_id)
                    ->oldest()
                    ->first();

                $billInvestigation?->delete();
            }

            $request->delete();

            if ($bill) {
                $this->syncInvestigationBill($bill);
            }

            $this->logActivity("Investigation request removed for {$investigationName}");
        });

        if ($this->editingRequestId === $id) {
            $this->resetRequestForm();
        }

        $this->feedback('Investigation request removed and bill updated successfully.');
    }

    public function cancelEdit(): void
    {
        $this->resetRequestForm();
    }

    private function updateRequest(array $row, string $clinicalDiagnoses): bool
    {
        $request = $this->editableInvestigationRequests()->with('bill.billInvestigations')->findOrFail($this->editingRequestId);
        $investigation = Investigation::with('investigationType.department')->findOrFail($row['investigation_id']);

        if ((int) $investigation->investigation_type_id !== (int) $row['type_id']) {
            $this->feedback('The investigation does not belong to the selected investigation type.', 'warning');
            return false;
        }

        if (! $this->billCanBeChanged($request->bill)) {
            $this->feedback('This investigation cannot be edited because its bill already has a completed payment.', 'warning');
            return false;
        }

        DB::transaction(function () use ($request, $investigation, $clinicalDiagnoses, $row): void {
            $oldInvestigationId = $request->investigation_id;
            $bill = $request->bill;

            $request->update([
                'investigation_id' => $investigation->id,
                'clinical_diagnoses' => $clinicalDiagnoses,
                'specimen' => $row['specimen'] ?: null,
            ]);

            if ($bill) {
                $amount = (float) ($investigation->price ?? 0);
                $billInvestigation = $bill->billInvestigations()
                    ->where('investigation_id', $oldInvestigationId)
                    ->oldest()
                    ->first();

                if (! $billInvestigation) {
                    $billInvestigation = new BillInvestigation(['bill_id' => $bill->id]);
                }

                $billInvestigation->fill([
                    'investigation_id' => $investigation->id,
                    'unit_price' => $amount,
                    'quantity' => 1,
                    'subtotal' => $amount,
                ])->save();

                $bill->update(['discount' => (int) ($this->discount ?? 0)]);
                $this->syncInvestigationBill($bill);
            }
        });

        $this->logActivity("Investigation request updated for {$investigation->name}");
        return true;
    }

    private function syncInvestigationBill(Bill $bill): void
    {
        $items = $bill->billInvestigations()->with('investigation.investigationType')->get();

        if ($items->isEmpty()) {
            $bill->delete();
            return;
        }

        $amount = round((float) $items->sum('subtotal'), 2);
        $discount = max(0, min(100, (int) ($bill->discount ?? 0)));
        $dueAmount = round($amount * (1 - ($discount / 100)), 2);
        $names = $items->pluck('investigation.name')->filter()->take(3)->implode(', ');
        $description = $items->count() > 3 ? "{$names}..." : $names;

        $bill->update([
            'amount' => $amount,
            'discount' => $discount,
            'due_amount' => $dueAmount,
            'service_description' => $description ? "Investigation Bill: {$description}" : 'Investigation Bill',
            'department_id' => $items->first()?->investigation?->investigationType?->department_id,
        ]);

        $bill->refreshRequestPaymentStatuses();
    }

    private function billCanBeChanged(?Bill $bill): bool
    {
        if (! $bill) {
            return true;
        }

        return ! $bill->payments()->where('status', 'completed')->exists()
            && ! in_array((string) $bill->status, ['paid'], true);
    }

    private function editableInvestigationRequests()
    {
        return InvestigationRequest::query()
            ->whereHas('patientVisit', fn (Builder $query) => $query->where('patient_id', $this->patient->id))
            ->when($this->shouldScopeDoctorOwnedRequests(), fn (Builder $query) => $query->where('requested_by', auth()->id()));
    }

    private function shouldScopeDoctorOwnedRequests(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('doctor')
            && ! $user->hasRole('administrator')
            && ! $user->hasRole('nurse');
    }

    private function resetRequestForm(): void
    {
        $this->editingRequestId = null;
        $this->rows = [['type_id' => '', 'investigation_id' => '', 'specimen' => '']];
        $this->clinicalDiagnoses = '';
        $this->discount = 0;
        $this->resetValidation();
    }
}
