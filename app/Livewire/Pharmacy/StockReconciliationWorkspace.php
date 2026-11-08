<?php

namespace App\Livewire\Pharmacy;

use App\Models\MedicineBatch;
use App\Models\PharmacyStockReconciliation;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class StockReconciliationWorkspace extends Component
{
    use WithPagination;

    public string $search = '';
    public string $expiryStatus = '';
    public string $notes = '';
    public array $physicalCounts = [];
    public array $itemNotes = [];

    protected string $paginationTheme = 'tailwind';

    public function render()
    {
        $batches = $this->batchQuery()
            ->orderBy('expiry_date')
            ->orderBy('created_at')
            ->paginate(20);

        foreach ($batches as $batch) {
            $this->physicalCounts[$batch->id] ??= '';
            $this->itemNotes[$batch->id] ??= '';
        }

        return view('components.pharmacy.stock-reconciliation-workspace', [
            'batches' => $batches,
            'summary' => [
                'batches' => (clone $this->batchQuery())->count(),
                'quantity' => (int) (clone $this->batchQuery())->sum('quantity_remaining'),
                'recent' => PharmacyStockReconciliation::with('checkedBy')
                    ->withCount('items')
                    ->latest()
                    ->limit(8)
                    ->get(),
            ],
        ]);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'expiryStatus'], true)) {
            $this->resetPage();
        }
    }

    public function clearCounts(): void
    {
        $this->reset(['physicalCounts', 'itemNotes', 'notes']);
    }

    public function fillVisibleWithSystemCounts(): void
    {
        $this->batchQuery()
            ->orderBy('expiry_date')
            ->orderBy('created_at')
            ->forPage($this->getPage(), 20)
            ->get()
            ->each(function (MedicineBatch $batch) {
                $this->physicalCounts[$batch->id] = (string) $batch->quantity_remaining;
            });
    }

    public function saveReconciliation(): void
    {
        $this->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'physicalCounts.*' => ['nullable', 'integer', 'min:0'],
            'itemNotes.*' => ['nullable', 'string', 'max:500'],
        ]);

        $counts = collect($this->physicalCounts)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->map(fn ($value) => (int) $value);

        if ($counts->isEmpty()) {
            $this->addError('physicalCounts', 'Enter at least one physical count before saving reconciliation.');
            return;
        }

        DB::transaction(function () use ($counts) {
            $reference = $this->generateReference();
            $transaction = StockTransaction::create([
                'type' => 'adjustment',
                'total_amount' => 0,
                'reference' => $reference,
                'created_by' => auth()->id(),
            ]);

            $reconciliation = PharmacyStockReconciliation::create([
                'reference' => $reference,
                'checked_date' => today(),
                'checked_by' => auth()->id(),
                'stock_transaction_id' => $transaction->id,
                'notes' => $this->notes !== '' ? $this->notes : null,
            ]);

            $totals = [
                'batches' => 0,
                'system' => 0,
                'physical' => 0,
                'variance' => 0,
                'value' => 0.0,
            ];

            foreach ($counts as $batchId => $physicalQuantity) {
                $batch = MedicineBatch::with('medicine')->whereKey($batchId)->lockForUpdate()->first();

                if (! $batch) {
                    continue;
                }

                $systemQuantity = (int) $batch->quantity_remaining;
                $variance = $physicalQuantity - $systemQuantity;
                $varianceValue = $variance * (float) $batch->purchase_price;

                $reconciliation->items()->create([
                    'medicine_batch_id' => $batch->id,
                    'medicine_id' => $batch->medicine_id,
                    'medicine_name' => $batch->medicine?->name,
                    'batch_number' => $batch->batch_number,
                    'system_quantity' => $systemQuantity,
                    'physical_quantity' => $physicalQuantity,
                    'variance' => $variance,
                    'purchase_price' => $batch->purchase_price,
                    'selling_price' => $batch->selling_price,
                    'variance_value' => $varianceValue,
                    'notes' => trim((string) ($this->itemNotes[$batch->id] ?? '')) ?: null,
                ]);

                if ($variance !== 0) {
                    $transaction->stockTransactionItems()->create([
                        'medicine_batch_id' => $batch->id,
                        'quantity' => $variance,
                        'price' => $batch->purchase_price,
                        'subtotal' => $varianceValue,
                    ]);

                    $batch->update(['quantity_remaining' => $physicalQuantity]);
                }

                $totals['batches']++;
                $totals['system'] += $systemQuantity;
                $totals['physical'] += $physicalQuantity;
                $totals['variance'] += $variance;
                $totals['value'] += $varianceValue;
            }

            $reconciliation->update([
                'total_batches_checked' => $totals['batches'],
                'total_system_quantity' => $totals['system'],
                'total_physical_quantity' => $totals['physical'],
                'total_variance' => $totals['variance'],
                'total_variance_value' => $totals['value'],
            ]);

            $transaction->update(['total_amount' => $totals['value']]);
        });

        $this->clearCounts();
        $this->dispatch('toast', message: 'Stock reconciliation saved and stock records updated.', type: 'success');
    }

    private function batchQuery()
    {
        return MedicineBatch::query()
            ->with('medicine.medicineType')
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('batch_number', 'like', "%{$search}%")
                        ->orWhereHas('medicine', fn ($medicineQuery) => $medicineQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%")
                            ->orWhere('manufacturer', 'like', "%{$search}%"));
                });
            })
            ->when($this->expiryStatus === 'expired', fn ($query) => $query->whereDate('expiry_date', '<', today()))
            ->when($this->expiryStatus === 'expiring', fn ($query) => $query->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addDays(60)))
            ->when($this->expiryStatus === 'valid', fn ($query) => $query->whereDate('expiry_date', '>', today()->addDays(60)));
    }

    private function generateReference(): string
    {
        $base = 'RECON-' . now()->format('YmdHis');
        $reference = $base;
        $suffix = 1;

        while (PharmacyStockReconciliation::where('reference', $reference)->exists()) {
            $reference = $base . '-' . $suffix++;
        }

        return $reference;
    }
}


