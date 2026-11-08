<?php

namespace App\Livewire\Pharmacy;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicineType;
use App\Models\StockTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.modern')]
class StockInventoryManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';
    public string $expiryStatus = '';
    public string $from = '';
    public string $to = '';
    public ?TemporaryUploadedFile $importFile = null;
    public array $importSummary = [];

    protected string $paginationTheme = 'tailwind';

    public function render()
    {
        $stockQuery = $this->stockQuery();
        $financeQuery = $this->financeQuery();

        $stocks = $this->medicineStockQuery()
            ->orderBy('name')
            ->paginate(15);

        return view('components.pharmacy.stock-inventory-manager', [
            'stocks' => $stocks,
            'summary' => [
                'batches' => (clone $stockQuery)->count(),
                'medicines' => (clone $this->medicineStockQuery())->count(),
                'quantity' => (int) (clone $stockQuery)->sum('quantity_remaining'),
                'purchase_value' => (float) (clone $stockQuery)->selectRaw('COALESCE(SUM(quantity_remaining * purchase_price), 0) as value')->value('value'),
                'retail_value' => (float) (clone $stockQuery)->selectRaw('COALESCE(SUM(quantity_remaining * selling_price), 0) as value')->value('value'),
                'low_stock' => MedicineBatch::where('quantity_remaining', '<=', 10)->count(),
                'expiring' => MedicineBatch::whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addDays(60))->count(),
                'expired' => MedicineBatch::whereDate('expiry_date', '<', today())->count(),
                'sales' => (float) (clone $financeQuery)->where('type', 'dispense')->sum('total_amount'),
                'purchases' => (float) (clone $financeQuery)->where('type', 'purchase')->sum('total_amount'),
            ],
        ]);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'expiryStatus', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'expiryStatus', 'from', 'to']);
        $this->resetPage();
    }

    public function importStock(): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $rows = $this->readCsvRows($this->importFile->getRealPath());

        if (empty($rows)) {
            $this->importSummary = ['processed' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => ['No valid rows found.']];
            $this->dispatch('toast', message: 'No valid rows found in the import file.', type: 'warning');
            return;
        }

        $summary = ['processed' => count($rows), 'imported' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($rows, &$summary) {
            $transaction = StockTransaction::create([
                'type' => 'purchase',
                'total_amount' => 0,
                'reference' => 'OLD-SYSTEM-IMPORT-' . now()->format('YmdHis'),
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;

            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $medicineName = trim((string) ($row['medicine_name'] ?? $row['medicine'] ?? $row['name'] ?? ''));
                $batchNumber = trim((string) ($row['batch_number'] ?? $row['batch_no'] ?? $row['batch'] ?? ''));
                $quantity = (int) ($row['quantity_received'] ?? $row['quantity'] ?? $row['quantity_remaining'] ?? 0);
                $purchasePrice = (float) ($row['purchase_price'] ?? $row['cost_price'] ?? $row['unit_cost'] ?? 0);
                $sellingPrice = (float) ($row['selling_price'] ?? $row['sale_price'] ?? $row['retail_price'] ?? $purchasePrice);
                $manufactureDate = $this->normalizeDate($row['manufacture_date'] ?? $row['manufacturing_date'] ?? $row['mfg_date'] ?? null);
                $expiryDate = $this->normalizeDate($row['expiry_date'] ?? $row['expiry'] ?? null) ?? today()->addYears(2)->toDateString();

                if ($medicineName === '' || $quantity < 1) {
                    $summary['skipped']++;
                    $summary['errors'][] = "Line {$line}: medicine and quantity are required.";
                    continue;
                }

                $typeName = trim((string) ($row['medicine_type'] ?? $row['type'] ?? $row['form'] ?? 'Imported'));
                $medicineType = MedicineType::firstOrCreate(['name' => $typeName !== '' ? $typeName : 'Imported']);

                $medicine = Medicine::firstOrCreate(
                    ['name' => $medicineName],
                    [
                        'medicine_type_id' => $medicineType->id,
                        'generic_name' => $row['generic_name'] ?? null,
                        'strength' => $row['strength'] ?? null,
                        'form' => $row['form'] ?? null,
                        'manufacturer' => $row['manufacturer'] ?? null,
                    ]
                );

                if ($batchNumber === '') {
                    $batchNumber = $this->generatedBatchNumber($medicine->id, $line);
                }

                $batch = MedicineBatch::firstOrNew([
                    'medicine_id' => $medicine->id,
                    'batch_number' => $batchNumber,
                ]);

                $batch->supplier_id = $batch->supplier_id ?? null;
                $batch->purchase_price = $purchasePrice;
                $batch->selling_price = $sellingPrice;
                $batch->quantity_received = (int) $batch->quantity_received + $quantity;
                $batch->quantity_remaining = (int) $batch->quantity_remaining + $quantity;
                $batch->manufacture_date = $manufactureDate;
                $batch->expiry_date = $expiryDate;
                $batch->save();

                $subtotal = $quantity * $purchasePrice;
                $transaction->stockTransactionItems()->create([
                    'medicine_batch_id' => $batch->id,
                    'quantity' => $quantity,
                    'price' => $purchasePrice,
                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;
                $summary['imported']++;
            }

            if ($summary['imported'] > 0) {
                $transaction->update(['total_amount' => $totalAmount]);
            } else {
                $transaction->delete();
            }
        });

        $this->importSummary = $summary;
        $this->reset('importFile');
        $this->dispatch('toast', message: "{$summary['imported']} stock rows imported.", type: $summary['imported'] > 0 ? 'success' : 'warning');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['medicine_name', 'medicine_type', 'generic_name', 'strength', 'form', 'manufacturer', 'batch_number', 'quantity_received', 'purchase_price', 'selling_price', 'manufacture_date', 'expiry_date']);
            fputcsv($handle, ['Paracetamol', 'Tablet', 'Acetaminophen', '500mg', 'Tablet', 'Old Supplier', '', 250, 80, 150, '', '']);
            fclose($handle);
        }, 'medicine-stock-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function downloadStockPdf()
    {
        $stocks = $this->medicineStockQuery()
            ->orderBy('name')
            ->get();
        $batches = $this->stockQuery()->get();

        $summary = [
            'batches' => $batches->count(),
            'medicines' => $stocks->count(),
            'quantity' => (int) $batches->sum('quantity_remaining'),
            'purchase_value' => (float) $batches->sum(fn ($batch) => $batch->quantity_remaining * $batch->purchase_price),
            'retail_value' => (float) $batches->sum(fn ($batch) => $batch->quantity_remaining * $batch->selling_price),
        ];

        $pdf = Pdf::loadView('pharmacy.stock.stock-report-pdf', [
            'stocks' => $stocks,
            'summary' => $summary,
            'from' => $this->from,
            'to' => $this->to,
            'expiryStatus' => $this->expiryStatus,
            'search' => $this->search,
            'generatedBy' => auth()->user(),
            'hospital' => [
                'name' => strtoupper(config('app.title', config('app.name', 'FAYHOS'))),
                'address' => strtoupper(config('app.address', '')),
                'logo' => public_path('images/logo.png'),
            ],
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'pharmacy-stock-taking-report-' . now()->format('Y-m-d-His') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function exportStock(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['medicine', 'generic_name', 'type', 'batch_number', 'quantity_received', 'quantity_remaining', 'purchase_price', 'selling_price', 'purchase_value', 'retail_value', 'manufacture_date', 'expiry_date']);

            $this->stockQuery()->with('medicine.medicineType')->orderBy('created_at')->chunk(200, function ($batches) use ($handle) {
                foreach ($batches as $batch) {
                    fputcsv($handle, [
                        $batch->medicine?->name,
                        $batch->medicine?->generic_name,
                        $batch->medicine?->medicineType?->name,
                        $batch->batch_number,
                        $batch->quantity_received,
                        $batch->quantity_remaining,
                        $batch->purchase_price,
                        $batch->selling_price,
                        $batch->quantity_remaining * $batch->purchase_price,
                        $batch->quantity_remaining * $batch->selling_price,
                        $batch->manufacture_date,
                        $batch->expiry_date,
                    ]);
                }
            });

            fclose($handle);
        }, 'medicine-stock-export-' . now()->format('YmdHis') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportFinance(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['date', 'reference', 'type', 'created_by', 'items', 'total_amount', 'bill', 'payment']);

            $this->financeQuery()->with(['createdBy', 'bill', 'payment', 'stockTransactionItems.medicineBatch.medicine'])->orderBy('created_at')->chunk(200, function ($transactions) use ($handle) {
                foreach ($transactions as $transaction) {
                    fputcsv($handle, [
                        $transaction->created_at?->format('Y-m-d H:i:s'),
                        $transaction->reference,
                        $transaction->type,
                        $transaction->createdBy?->name,
                        $transaction->stockTransactionItems->map(fn ($item) => ($item->medicineBatch?->medicine?->name ?? 'Medicine') . ' x ' . $item->quantity)->implode('; '),
                        $transaction->total_amount,
                        $transaction->bill?->bill_number,
                        $transaction->payment?->payment_id,
                    ]);
                }
            });

            fclose($handle);
        }, 'pharmacy-finance-export-' . now()->format('YmdHis') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function stockQuery()
    {
        return MedicineBatch::query()
            ->with('medicine.medicineType')
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->whereHas('medicine', fn ($medicineQuery) => $medicineQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%"));
            })
            ->when($this->expiryStatus === 'expired', fn ($query) => $query->whereDate('expiry_date', '<', today()))
            ->when($this->expiryStatus === 'expiring', fn ($query) => $query->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addDays(60)))
            ->when($this->expiryStatus === 'valid', fn ($query) => $query->whereDate('expiry_date', '>', today()->addDays(60)))
            ->when($this->from !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->to));
    }

    private function medicineStockQuery()
    {
        return Medicine::query()
            ->with('medicineType')
            ->with(['batches' => fn ($query) => $this->applyBatchFilters($query)->orderBy('expiry_date')->orderBy('created_at')])
            ->whereHas('batches', fn ($query) => $this->applyBatchFilters($query))
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
                        ->orWhere('manufacturer', 'like', "%{$search}%");
                });
            });
    }

    private function applyBatchFilters($query)
    {
        return $query
            ->when($this->expiryStatus === 'expired', fn ($query) => $query->whereDate('expiry_date', '<', today()))
            ->when($this->expiryStatus === 'expiring', fn ($query) => $query->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addDays(60)))
            ->when($this->expiryStatus === 'valid', fn ($query) => $query->whereDate('expiry_date', '>', today()->addDays(60)))
            ->when($this->from !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->to));
    }

    private function financeQuery()
    {
        return StockTransaction::query()
            ->when($this->from !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->to));
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $headers = [];
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === []) {
                $headers = array_map(fn ($header) => str($header)->lower()->replace([' ', '-'], '_')->trim()->toString(), $data);
                continue;
            }

            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($headers, array_slice(array_pad($data, count($headers), null), 0, count($headers)));
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
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


