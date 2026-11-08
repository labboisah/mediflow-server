<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\StockTransaction;

class FinanceController extends Controller
{
    public function bills()
    {
        $transactions = StockTransaction::with(['bill.payments', 'payment.paymentMethod', 'stockTransactionItems.medicineBatch.medicine', 'serviceItems', 'createdBy'])
            ->whereNotNull('bill_id')
            ->latest()
            ->paginate(25);

        return view('pharmacy.finance.bills', compact('transactions'));
    }

    public function payments()
    {
        $transactions = StockTransaction::with(['bill', 'payment.paymentMethod', 'stockTransactionItems.medicineBatch.medicine', 'serviceItems', 'createdBy'])
            ->whereNotNull('payment_id')
            ->latest()
            ->paginate(25);

        return view('pharmacy.finance.payments', compact('transactions'));
    }

    public function receipt(Payment $payment)
    {
        $transaction = StockTransaction::with(['stockTransactionItems.medicineBatch.medicine', 'serviceItems', 'bill', 'payment.paymentMethod', 'createdBy'])
            ->where('payment_id', $payment->id)
            ->firstOrFail();

        $payment->load(['bill', 'paymentMethod', 'recordedBy']);

        return view('pharmacy.finance.receipt', compact('payment', 'transaction'));
    }

    public function report(Request $request)
    {
        $data = $this->reportData($request);

        return view('pharmacy.finance.report', $data);
    }

    public function downloadReport(Request $request)
    {
        $data = $this->reportData($request);
        $data['hospital'] = [
            'name' => strtoupper(config('app.title', config('app.name', 'FAYHOS'))),
            'address' => strtoupper(config('app.address', '')),
            'logo' => public_path('images/logo.png'),
        ];
        $data['generatedBy'] = $request->user();

        $pdf = Pdf::loadView('pharmacy.finance.report-pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('pharmacy-financial-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function reportData(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? today()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? today()->toDateString();

        $transactions = StockTransaction::with(['bill', 'payment.paymentMethod', 'stockTransactionItems.medicineBatch.medicine', 'serviceItems', 'createdBy'])
            ->whereNotNull('bill_id')
            ->whereNotNull('payment_id')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->latest()
            ->get();

        $transactionRows = $transactions->map(function (StockTransaction $transaction) {
            $revenue = (float) ($transaction->payment?->amount ?? $transaction->total_amount);
            $cost = (float) $transaction->stockTransactionItems->sum(
                fn ($item) => (int) $item->quantity * (float) ($item->medicineBatch?->purchase_price ?? 0)
            );

            return [
                'transaction' => $transaction,
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $revenue - $cost,
            ];
        });

        $summary = [
            'transactions' => $transactions->count(),
            'bills' => (float) $transactions->sum(fn ($transaction) => $transaction->bill?->due_amount ?? 0),
            'payments' => (float) $transactionRows->sum('revenue'),
            'cost' => (float) $transactionRows->sum('cost'),
            'profit' => (float) $transactionRows->sum('profit'),
            'items' => (int) $transactions->flatMap->stockTransactionItems->sum('quantity'),
        ];

        $chartTotals = [
            ['label' => 'Revenue', 'value' => $summary['payments'], 'color' => '#198754'],
            ['label' => 'Cost', 'value' => $summary['cost'], 'color' => '#dc3545'],
            ['label' => 'Profit', 'value' => $summary['profit'], 'color' => '#0d6efd'],
        ];

        $dailyProfit = $transactionRows
            ->groupBy(fn ($row) => $row['transaction']->created_at?->format('Y-m-d') ?? 'Unknown')
            ->map(fn ($rows, $date) => [
                'label' => $date === 'Unknown' ? $date : Carbon::parse($date)->format('M d'),
                'value' => (float) collect($rows)->sum('profit'),
            ])
            ->values();

        $topMedicines = $transactions
            ->flatMap(function ($transaction) {
                return $transaction->stockTransactionItems->map(function ($item) {
                    $revenue = (float) $item->subtotal;
                    $cost = (int) $item->quantity * (float) ($item->medicineBatch?->purchase_price ?? 0);

                    return [
                        'name' => $item->medicineBatch?->medicine?->name ?? 'N/A',
                        'quantity' => (int) $item->quantity,
                        'revenue' => $revenue,
                        'cost' => $cost,
                        'profit' => $revenue - $cost,
                    ];
                });
            })
            ->groupBy('name')
            ->map(fn ($items, $name) => [
                'name' => $name,
                'quantity' => (int) $items->sum('quantity'),
                'revenue' => (float) $items->sum('revenue'),
                'cost' => (float) $items->sum('cost'),
                'profit' => (float) $items->sum('profit'),
            ])
            ->sortByDesc('profit')
            ->take(8)
            ->values();

        return compact('transactions', 'transactionRows', 'summary', 'chartTotals', 'dailyProfit', 'topMedicines', 'from', 'to');
    }
}
