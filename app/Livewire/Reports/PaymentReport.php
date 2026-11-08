<?php

namespace App\Livewire\Reports;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Expense;
use App\Models\Revenue;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class PaymentReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public string $status = '';
    public string $method = '';
    public string $recordedBy = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public bool $todayOnly = false;
    public string $reportBy = 'methods';
    public string $chartBreakdown = 'methods';
    public string $chartType = 'bar';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingMethod(): void
    {
        $this->resetPage();
    }

    public function updatingRecordedBy(): void
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

    public function updatedTodayOnly(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'method', 'recordedBy', 'todayOnly']);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->filteredPaymentQuery();

        return view('components.reports.payment-report', [
            'payments' => (clone $query)->paginate(15),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'users' => $this->reportUsers(),
            'canFilterUsers' => auth()->user()->hasAnyRole(['administrator', 'medical_director', 'finance_officer']),
            'summary' => $this->summary($query),
            'breakdownRows' => $this->breakdownRows(),
            'chartPayload' => $this->chartPayload(),
            'exportUrl' => route('reports.payments.export', $this->exportParameters()),
            'pdfUrl' => route('reports.payments.pdf', $this->exportParameters()),
        ]);
    }

    private function filteredPaymentQuery()
    {
        [$startDate, $endDate] = $this->dateRange();

        $query = Payment::query()
            ->with(['bill.patientVisit.patient.demographic', 'bill.walkinPatient', 'paymentMethod', 'recordedBy'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->latest('payment_date')
            ->latest('created_at');

        if ($this->search !== '') {
            $search = $this->search;

            $query->where(function ($q) use ($search) {
                $q->where('payment_id', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('insurance_provider', 'like', "%{$search}%")
                    ->orWhereHas('bill', fn ($bill) => $bill->where('bill_number', 'like', "%{$search}%"))
                    ->orWhereHas('bill.walkinPatient', fn ($patient) => $patient->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('bill.patientVisit.patient', fn ($patient) => $patient->where('hospital_number', 'like', "%{$search}%"))
                    ->orWhereHas('bill.patientVisit.patient.demographic', function ($patient) use ($search) {
                        $patient->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->method !== '') {
            $query->where('payment_method_id', $this->method);
        }

        if ($this->effectiveRecordedBy() !== '') {
            $query->where('paid_by', $this->effectiveRecordedBy());
        }

        return $query;
    }

    private function summary($query): array
    {
        $completedAmount = (float) (clone $query)->where('status', 'completed')->sum('amount');
        $revenueTotal = $this->filteredRevenueQuery()->sum('amount');
        $expenseTotal = $this->filteredExpenseQuery()->sum('amount');

        return [
            'payment_count' => (clone $query)->count(),
            'completed_amount' => $completedAmount,
            'pending_amount' => (float) (clone $query)->where('status', 'pending')->sum('amount'),
            'reversed_amount' => (float) (clone $query)->where('status', 'reversed')->sum('amount'),
            'reversed_count' => (clone $query)->where('status', 'reversed')->count(),
            'total_revenue' => (float) $revenueTotal,
            'total_expense' => (float) $expenseTotal,
            'net_position' => (float) (($completedAmount + $revenueTotal) - $expenseTotal),
        ];
    }

    private function filteredRevenueQuery()
    {
        [$startDate, $endDate] = $this->dateRange();

        return Revenue::query()
            ->whereBetween('revenue_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($this->effectiveRecordedBy() !== '', fn ($query) => $query->where('created_by', $this->effectiveRecordedBy()));
    }

    private function filteredExpenseQuery()
    {
        [$startDate, $endDate] = $this->dateRange();

        return Expense::query()
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($this->effectiveRecordedBy() !== '', fn ($query) => $query->where('created_by', $this->effectiveRecordedBy()));
    }

    private function breakdownRows()
    {
        return match ($this->reportBy) {
            'users' => $this->userBreakdown(),
            'statuses' => $this->statusBreakdown(),
            'daily' => $this->dailyBreakdown(),
            'bills' => $this->billBreakdown(),
            default => $this->methodBreakdown(),
        };
    }

    private function chartPayload(): array
    {
        $rows = match ($this->chartBreakdown) {
            'users' => $this->userBreakdown(),
            'statuses' => $this->statusBreakdown(),
            'daily' => $this->dailyBreakdown(),
            'bills' => $this->billBreakdown(),
            default => $this->methodBreakdown(),
        };

        return [
            'type' => $this->chartType,
            'labels' => $rows->pluck('label')->values(),
            'values' => $rows->pluck('amount')->map(fn ($amount) => round((float) $amount, 2))->values(),
            'title' => ucfirst($this->chartBreakdown) . ' Payment Breakdown',
        ];
    }

    private function methodBreakdown()
    {
        [$startDate, $endDate] = $this->dateRange();

        return Payment::query()
            ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->whereBetween('payments.payment_date', [$startDate, $endDate])
            ->when($this->status !== '', fn ($query) => $query->where('payments.status', $this->status))
            ->when($this->method !== '', fn ($query) => $query->where('payments.payment_method_id', $this->method))
            ->when($this->effectiveRecordedBy() !== '', fn ($query) => $query->where('payments.paid_by', $this->effectiveRecordedBy()))
            ->selectRaw('COALESCE(payment_methods.name, "Unknown") as label, SUM(payments.amount) as amount, COUNT(payments.id) as count')
            ->groupBy('payments.payment_method_id', 'payment_methods.name')
            ->orderByDesc('amount')
            ->get();
    }

    private function userBreakdown()
    {
        [$startDate, $endDate] = $this->dateRange();

        return Payment::query()
            ->leftJoin('users', 'payments.paid_by', '=', 'users.id')
            ->whereBetween('payments.payment_date', [$startDate, $endDate])
            ->when($this->status !== '', fn ($query) => $query->where('payments.status', $this->status))
            ->when($this->method !== '', fn ($query) => $query->where('payments.payment_method_id', $this->method))
            ->when($this->effectiveRecordedBy() !== '', fn ($query) => $query->where('payments.paid_by', $this->effectiveRecordedBy()))
            ->selectRaw('COALESCE(users.name, "Unknown") as label, SUM(payments.amount) as amount, COUNT(payments.id) as count')
            ->groupBy('payments.paid_by', 'users.name')
            ->orderByDesc('amount')
            ->get();
    }

    private function statusBreakdown()
    {
        [$startDate, $endDate] = $this->dateRange();

        return Payment::query()
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->when($this->method !== '', fn ($query) => $query->where('payment_method_id', $this->method))
            ->when($this->effectiveRecordedBy() !== '', fn ($query) => $query->where('paid_by', $this->effectiveRecordedBy()))
            ->selectRaw('status as label, SUM(amount) as amount, COUNT(id) as count')
            ->groupBy('status')
            ->orderByDesc('amount')
            ->get();
    }

    private function dailyBreakdown()
    {
        [$startDate, $endDate] = $this->dateRange();

        return Payment::query()
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->method !== '', fn ($query) => $query->where('payment_method_id', $this->method))
            ->when($this->effectiveRecordedBy() !== '', fn ($query) => $query->where('paid_by', $this->effectiveRecordedBy()))
            ->selectRaw('DATE(payment_date) as label, SUM(amount) as amount, COUNT(id) as count')
            ->groupByRaw('DATE(payment_date)')
            ->orderBy('label')
            ->get();
    }

    private function billBreakdown()
    {
        [$startDate, $endDate] = $this->dateRange();

        return Payment::query()
            ->join('bills', 'payments.bill_id', '=', 'bills.id')
            ->whereBetween('payments.payment_date', [$startDate, $endDate])
            ->when($this->status !== '', fn ($query) => $query->where('payments.status', $this->status))
            ->when($this->method !== '', fn ($query) => $query->where('payments.payment_method_id', $this->method))
            ->when($this->effectiveRecordedBy() !== '', fn ($query) => $query->where('payments.paid_by', $this->effectiveRecordedBy()))
            ->selectRaw('bills.bill_number as label, SUM(payments.amount) as amount, COUNT(payments.id) as count')
            ->groupBy('bills.id', 'bills.bill_number')
            ->orderByDesc('amount')
            ->limit(15)
            ->get();
    }

    private function dateRange(): array
    {
        if ($this->todayOnly) {
            return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
        }

        $start = Carbon::parse($this->dateFrom ?: now()->startOfMonth()->format('Y-m-d'))->startOfDay();
        $end = Carbon::parse($this->dateTo ?: now()->format('Y-m-d'))->endOfDay();

        if ($start->gt($end)) {
            return [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function exportParameters(): array
    {
        return array_filter([
            'start_date' => $this->todayOnly ? null : $this->dateFrom,
            'end_date' => $this->todayOnly ? null : $this->dateTo,
            'today' => $this->todayOnly ? 1 : null,
            'search' => $this->search ?: null,
            'status' => $this->status ?: null,
            'method' => $this->method ?: null,
            'recorded_by' => $this->effectiveRecordedBy() ?: null,
        ]);
    }

    private function effectiveRecordedBy(): string
    {
        if (auth()->user()->hasRole('accountant') && ! auth()->user()->hasRole('administrator')) {
            return (string) auth()->id();
        }

        return $this->recordedBy;
    }

    private function reportUsers()
    {
        if (auth()->user()->hasRole('accountant') && ! auth()->user()->hasRole('administrator')) {
            return User::whereKey(auth()->id())->get(['id', 'name']);
        }

        return User::query()
            ->whereIn('id', Payment::query()->select('paid_by')->whereNotNull('paid_by'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}

