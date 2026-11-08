<?php

namespace App\Livewire\Pharmacy;

use App\Models\PaymentMethod;
use App\Models\StockTransaction;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class TransactionIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $from = '';
    public string $to = '';
    public string $paymentMethod = '';
    public string $createdBy = '';

    protected string $paginationTheme = 'tailwind';

    public function render()
    {
        $summaryQuery = $this->transactionQuery();
        $transactions = $this->transactionQuery()
            ->latest()
            ->paginate(15);

        return view('components.pharmacy.transaction-index', [
            'transactions' => $transactions,
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'amount' => (float) (clone $summaryQuery)->sum('total_amount'),
                'items' => (clone $summaryQuery)->with('stockTransactionItems')->get()->flatMap->stockTransactionItems->sum('quantity'),
                'payments' => (float) (clone $summaryQuery)->whereHas('payment', fn ($query) => $query->where('status', 'completed'))->with('payment')->get()->sum(fn ($transaction) => $transaction->payment?->amount ?? 0),
            ],
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'users' => User::whereHas('roles', fn ($query) => $query->whereIn('name', ['pharmacist', 'pharmacy_technician']))->orderBy('name')->get(),
        ]);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'from', 'to', 'paymentMethod', 'createdBy'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'from', 'to', 'paymentMethod', 'createdBy']);
        $this->resetPage();
    }

    private function transactionQuery()
    {
        return StockTransaction::query()
            ->with(['stockTransactionItems.medicineBatch.medicine', 'serviceItems', 'createdBy', 'bill', 'payment.paymentMethod'])
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('reference', 'like', "%{$search}%")
                        ->orWhere('patient_name', 'like', "%{$search}%")
                        ->orWhereHas('serviceItems', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('bill', fn ($billQuery) => $billQuery->where('bill_number', 'like', "%{$search}%"))
                        ->orWhereHas('payment', fn ($paymentQuery) => $paymentQuery->where('payment_id', 'like', "%{$search}%")
                            ->orWhere('reference_number', 'like', "%{$search}%"))
                        ->orWhereHas('createdBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('stockTransactionItems.medicineBatch.medicine', 'serviceItems', fn ($medicineQuery) => $medicineQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%")
                            ->orWhere('manufacturer', 'like', "%{$search}%"));
                });
            })
            ->when($this->from !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->to))
            ->when($this->paymentMethod !== '', fn ($query) => $query->whereHas('payment', fn ($paymentQuery) => $paymentQuery->where('payment_method_id', $this->paymentMethod)))
            ->when($this->createdBy !== '', fn ($query) => $query->where('created_by', $this->createdBy));
    }
}

