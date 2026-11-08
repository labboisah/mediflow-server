<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\MedicineBatch;
use App\Models\Payment;
use App\Models\PharmacyDispense;
use App\Models\PharmacyStockReconciliation;
use App\Models\PharmacyStockReconciliationItem;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockTransaction;
use App\Models\StockTransactionItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CleanupPharmacyTrialData extends Command
{
    protected $signature = 'pharmacy:cleanup-trial-data
        {--execute : Actually delete the records. Without this option the command only previews counts.}
        {--yes : Skip the interactive confirmation prompt when used with --execute.}';

    protected $description = 'Preview or delete pharmacy trial stock, transactions, linked pharmacy bills, and linked payments.';

    public function handle(): int
    {
        $scope = $this->scope();

        $this->line('Pharmacy trial cleanup scope');
        $this->table(['Data', 'Count'], [
            ['Medicine batches', $scope['counts']['medicine_batches']],
            ['Pharmacy dispenses', $scope['counts']['pharmacy_dispenses']],
            ['Stock transactions', $scope['counts']['stock_transactions']],
            ['Stock transaction items', $scope['counts']['stock_transaction_items']],
            ['Pharmacy bills linked to stock transactions', $scope['counts']['bills']],
            ['Pharmacy payments linked to stock transactions/bills', $scope['counts']['payments']],
            ['Stock reconciliations', $scope['counts']['reconciliations']],
            ['Stock reconciliation items', $scope['counts']['reconciliation_items']],
            ['Prescriptions to reset from paid/dispensed to submitted', $scope['counts']['prescriptions_to_reset']],
        ]);

        if (! $this->option('execute')) {
            $this->warn('Dry run only. No records were deleted.');
            $this->line('Run with --execute after reviewing the counts.');
            return self::SUCCESS;
        }

        if (! $this->option('yes') && ! $this->confirm('This will permanently delete pharmacy trial stock, transactions, linked bills, and linked payments. Continue?')) {
            $this->warn('Cleanup cancelled.');
            return self::SUCCESS;
        }

        $snapshotPath = $this->writeSnapshot($scope);

        DB::transaction(function () use ($scope) {
            if ($scope['prescription_ids']->isNotEmpty()) {
                Prescription::whereIn('id', $scope['prescription_ids'])->update(['status' => 'submitted']);
            }

            if ($scope['payment_ids']->isNotEmpty()) {
                Payment::withTrashed()->whereIn('id', $scope['payment_ids'])->forceDelete();
            }

            if ($scope['bill_ids']->isNotEmpty()) {
                Bill::withTrashed()->whereIn('id', $scope['bill_ids'])->forceDelete();
            }

            PharmacyStockReconciliationItem::query()->delete();
            PharmacyStockReconciliation::query()->delete();
            PharmacyDispense::query()->delete();
            StockTransactionItem::query()->delete();
            StockTransaction::query()->delete();
            MedicineBatch::query()->delete();
        });

        $this->info('Pharmacy trial data cleanup completed.');
        $this->line("Cleanup snapshot written to: {$snapshotPath}");

        return self::SUCCESS;
    }

    private function scope(): array
    {
        $transactionIds = StockTransaction::query()->pluck('id');
        $billIds = StockTransaction::query()->whereNotNull('bill_id')->pluck('bill_id')->unique()->values();
        $paymentIds = StockTransaction::query()
            ->whereNotNull('payment_id')
            ->pluck('payment_id')
            ->merge(Payment::withTrashed()->whereIn('bill_id', $billIds)->pluck('id'))
            ->unique()
            ->values();

        $prescriptionIds = PrescriptionItem::query()
            ->whereIn('id', StockTransactionItem::query()->whereNotNull('prescription_item_id')->pluck('prescription_item_id'))
            ->pluck('prescription_id')
            ->unique()
            ->values();

        return [
            'transaction_ids' => $transactionIds,
            'bill_ids' => $billIds,
            'payment_ids' => $paymentIds,
            'prescription_ids' => $prescriptionIds,
            'counts' => [
                'medicine_batches' => MedicineBatch::query()->count(),
                'pharmacy_dispenses' => PharmacyDispense::query()->count(),
                'stock_transactions' => $transactionIds->count(),
                'stock_transaction_items' => StockTransactionItem::query()->count(),
                'bills' => $billIds->count(),
                'payments' => $paymentIds->count(),
                'reconciliations' => PharmacyStockReconciliation::query()->count(),
                'reconciliation_items' => PharmacyStockReconciliationItem::query()->count(),
                'prescriptions_to_reset' => Prescription::whereIn('id', $prescriptionIds)
                    ->whereIn('status', ['paid', 'dispensed'])
                    ->count(),
            ],
        ];
    }

    private function writeSnapshot(array $scope): string
    {
        $directory = storage_path('app/pharmacy-cleanup');
        File::ensureDirectoryExists($directory);

        $path = $directory . '/pharmacy-trial-cleanup-' . now()->format('Ymd-His') . '.json';

        File::put($path, json_encode([
            'created_at' => now()->toDateTimeString(),
            'counts' => $scope['counts'],
            'ids' => [
                'stock_transactions' => $scope['transaction_ids']->all(),
                'bills' => $scope['bill_ids']->all(),
                'payments' => $scope['payment_ids']->all(),
                'prescriptions_reset' => $scope['prescription_ids']->all(),
            ],
        ], JSON_PRETTY_PRINT));

        return $path;
    }
}
