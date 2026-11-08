<?php

namespace App\Livewire\Pharmacy;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class BatchManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $expiryStatus = '';
    public string $from = '';
    public string $to = '';
    public string $medicineId = '';
    public ?int $editingBatchId = null;
    public array $batchForm = [
        'medicine_id' => '',
        'batch_number' => '',
        'quantity_received' => '',
        'quantity_remaining' => '',
        'purchase_price' => '',
        'selling_price' => '',
        'manufacture_date' => '',
        'expiry_date' => '',
    ];

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->medicineId = (string) request()->query('medicine', '');
    }

    public function render()
    {
        return view('components.pharmacy.batch-manager', [
            'batches' => $this->batchQuery()->latest()->paginate(15),
            'medicines' => Medicine::orderBy('name')->get(),
            'summary' => [
                'batches' => (clone $this->batchQuery())->count(),
                'quantity' => (int) (clone $this->batchQuery())->sum('quantity_remaining'),
                'purchase_value' => (float) (clone $this->batchQuery())->selectRaw('COALESCE(SUM(quantity_remaining * purchase_price), 0) as value')->value('value'),
                'retail_value' => (float) (clone $this->batchQuery())->selectRaw('COALESCE(SUM(quantity_remaining * selling_price), 0) as value')->value('value'),
            ],
        ]);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'expiryStatus', 'from', 'to', 'medicineId'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'expiryStatus', 'from', 'to', 'medicineId']);
        $this->resetPage();
    }

    public function editBatch(int $batchId): void
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $batch = MedicineBatch::findOrFail($batchId);

        $this->editingBatchId = $batch->id;
        $this->batchForm = [
            'medicine_id' => (string) $batch->medicine_id,
            'batch_number' => (string) $batch->batch_number,
            'quantity_received' => (string) $batch->quantity_received,
            'quantity_remaining' => (string) $batch->quantity_remaining,
            'purchase_price' => (string) $batch->purchase_price,
            'selling_price' => (string) $batch->selling_price,
            'manufacture_date' => (string) $batch->manufacture_date,
            'expiry_date' => (string) $batch->expiry_date,
        ];
    }

    public function cancelEdit(): void
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->editingBatchId = null;
        $this->batchForm = [
            'medicine_id' => '',
            'batch_number' => '',
            'quantity_received' => '',
            'quantity_remaining' => '',
            'purchase_price' => '',
            'selling_price' => '',
            'manufacture_date' => '',
            'expiry_date' => '',
        ];
    }

    public function updateBatch(): void
    {
        if ($this->editingBatchId === null) {
            return;
        }

        $validated = $this->validate([
            'batchForm.medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'batchForm.batch_number' => ['nullable', 'string', 'max:255'],
            'batchForm.quantity_received' => ['required', 'integer', 'min:0'],
            'batchForm.quantity_remaining' => ['required', 'integer', 'min:0'],
            'batchForm.purchase_price' => ['required', 'numeric', 'min:0'],
            'batchForm.selling_price' => ['required', 'numeric', 'min:0'],
            'batchForm.manufacture_date' => ['nullable', 'date'],
            'batchForm.expiry_date' => ['nullable', 'date'],
        ]);

        $batch = MedicineBatch::findOrFail($this->editingBatchId);
        $data = $validated['batchForm'];

        $batch->update([
            'medicine_id' => (int) $data['medicine_id'],
            'batch_number' => trim((string) $data['batch_number']) !== ''
                ? trim((string) $data['batch_number'])
                : $this->generatedBatchNumber((int) $data['medicine_id'], $batch->id),
            'quantity_received' => (int) $data['quantity_received'],
            'quantity_remaining' => (int) $data['quantity_remaining'],
            'purchase_price' => (float) $data['purchase_price'],
            'selling_price' => (float) $data['selling_price'],
            'manufacture_date' => $data['manufacture_date'] ?: null,
            'expiry_date' => $data['expiry_date'] ?: today()->addYears(2)->toDateString(),
        ]);

        $this->cancelEdit();
        $this->dispatch('toast', message: 'Batch updated. Stock inventory has been refreshed.', type: 'success');
    }

    private function batchQuery()
    {
        return MedicineBatch::query()
            ->with('medicine.medicineType')
            ->when($this->medicineId !== '', fn ($query) => $query->where('medicine_id', $this->medicineId))
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('batch_number', 'like', "%{$search}%")
                        ->orWhereHas('medicine', fn ($medicineQuery) => $medicineQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%")
                            ->orWhere('manufacturer', 'like', "%{$search}%"));
                });
            })
            ->when($this->expiryStatus === 'expired', fn ($query) => $query->whereDate('expiry_date', '<', today()))
            ->when($this->expiryStatus === 'expiring', fn ($query) => $query->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addDays(60)))
            ->when($this->expiryStatus === 'valid', fn ($query) => $query->whereDate('expiry_date', '>', today()->addDays(60)))
            ->when($this->from !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->to));
    }

    private function generatedBatchNumber(int $medicineId, int|string $seed): string
    {
        $base = 'AUTO-' . $medicineId . '-' . now()->format('YmdHis') . '-' . $seed;
        $batchNumber = $base;
        $suffix = 1;

        while (MedicineBatch::where('medicine_id', $medicineId)->where('batch_number', $batchNumber)->exists()) {
            $batchNumber = $base . '-' . $suffix++;
        }

        return $batchNumber;
    }
}


