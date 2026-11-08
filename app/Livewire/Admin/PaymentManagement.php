<?php

namespace App\Livewire\Admin;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class PaymentManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public string $status = '';
    public string $method = '';
    public string $recordedBy = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public ?int $editingPaymentId = null;
    public ?int $billId = null;
    public string $paymentId = '';
    public string $amount = '';
    public string $paymentMethodId = '';
    public string $paymentDate = '';
    public string $paymentStatus = 'completed';
    public string $referenceNumber = '';
    public string $insuranceProvider = '';
    public string $notes = '';
    public string $billNumber = '';

    public function mount(): void
    {
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentMethodId = (string) (PaymentMethod::where('is_active', true)->orderBy('name')->value('id') ?? '');
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

    public function edit(int $paymentId): void
    {
        abort_unless($this->canManageFinance(), 403);

        $payment = Payment::findOrFail($paymentId);

        $this->editingPaymentId = $payment->id;
        $this->billId = $payment->bill_id;
        $this->paymentId = $payment->payment_id;
        $this->amount = (string) $payment->amount;
        $this->paymentMethodId = (string) $payment->payment_method_id;
        $this->paymentDate = optional($payment->payment_date)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->paymentStatus = $payment->status;
        $this->referenceNumber = $payment->reference_number ?? '';
        $this->insuranceProvider = $payment->insurance_provider ?? '';
        $this->notes = $payment->notes ?? '';
    }

    public function save(): void
    {
        abort_unless($this->canManageFinance(), 403);

        $validated = $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paymentMethodId' => ['required', 'exists:payment_methods,id'],
            'paymentDate' => ['required', 'date'],
            'paymentStatus' => ['required', 'in:pending,completed,failed,reversed'],
            'referenceNumber' => ['nullable', 'string', 'max:255'],
            'insuranceProvider' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'amount' => $validated['amount'],
            'payment_method_id' => $validated['paymentMethodId'],
            'payment_date' => $validated['paymentDate'],
            'status' => $validated['paymentStatus'],
            'reference_number' => $validated['referenceNumber'],
            'insurance_provider' => $validated['insuranceProvider'],
            'notes' => $validated['notes'],
        ];

        if ($this->editingPaymentId) {
            $payment = Payment::findOrFail($this->editingPaymentId);
            $oldBillId = $payment->bill_id;
            $payment->update($payload);

            $this->refreshBillStatus($oldBillId);
            $this->refreshBillStatus($payment->bill_id);
            $message = 'Payment updated successfully.';
        } else {
            $this->validate([
                'billNumber' => ['required', 'string', 'exists:bills,bill_number'],
            ]);

            $bill = Bill::where('bill_number', $this->billNumber)->firstOrFail();

            if ($validated['paymentStatus'] === 'completed' && (float) $validated['amount'] > (float) $bill->balance) {
                $this->addError('amount', 'Payment amount cannot be greater than the bill balance.');
                return;
            }

            $payment = Payment::create(array_merge($payload, [
                'bill_id' => $bill->id,
                'payment_id' => Payment::generatePaymentID(),
                'paid_by' => Auth::id(),
            ]));

            $this->refreshBillStatus($payment->bill_id);
            $message = 'Payment recorded successfully.';
        }

        $this->resetForm();

        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function reverse(int $paymentId): void
    {
        abort_unless($this->canManageFinance(), 403);

        $payment = Payment::findOrFail($paymentId);

        $payment->update([
            'status' => 'reversed',
            'notes' => trim(($payment->notes ? $payment->notes . "\n" : '') . 'Reversed by ' . Auth::user()->name . ' on ' . now()->format('Y-m-d H:i')),
        ]);

        $this->refreshBillStatus($payment->bill_id);

        $this->dispatch('toast', message: 'Payment reversed successfully.', type: 'warning');
    }

    public function delete(int $paymentId): void
    {
        abort_unless($this->canManageFinance(), 403);

        $payment = Payment::findOrFail($paymentId);
        $billId = $payment->bill_id;

        $payment->delete();
        $this->refreshBillStatus($billId);

        if ($this->editingPaymentId === $paymentId) {
            $this->resetForm();
        }

        $this->dispatch('toast', message: 'Payment deleted successfully.', type: 'danger');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'method', 'recordedBy', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingPaymentId',
            'billId',
            'paymentId',
            'amount',
            'paymentMethodId',
            'paymentDate',
            'paymentStatus',
            'referenceNumber',
            'insuranceProvider',
            'notes',
            'billNumber',
        ]);

        $this->paymentStatus = 'completed';
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentMethodId = (string) (PaymentMethod::where('is_active', true)->orderBy('name')->value('id') ?? '');
        $this->resetValidation();
    }

    public function render()
    {
        $query = Payment::query()
            ->with(['bill.patientVisit.patient.demographic', 'bill.walkinPatient', 'paymentMethod', 'recordedBy'])
            ->latest('payment_date');

        if ($this->search !== '') {
            $search = $this->search;

            $query->where(function ($q) use ($search) {
                $q->where('payment_id', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('bill', fn ($bill) => $bill->where('bill_number', 'like', "%{$search}%"))
                    ->orWhereHas('bill.walkinPatient', fn ($patient) => $patient->where('name', 'like', "%{$search}%"))
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

        if ($this->recordedBy !== '') {
            $query->where('paid_by', $this->recordedBy);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('payment_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('payment_date', '<=', $this->dateTo);
        }

        $summaryQuery = clone $query;

        return view('components.admin.payment-management', [
            'payments' => $query->paginate(15),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'users' => User::query()
                ->whereIn('id', Payment::query()->select('paid_by')->whereNotNull('paid_by'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'totalAmount' => (clone $summaryQuery)->where('status', 'completed')->sum('amount'),
            'paymentCount' => (clone $summaryQuery)->count(),
            'reversedCount' => (clone $summaryQuery)->where('status', 'reversed')->count(),
            'canManageFinance' => $this->canManageFinance(),
            'routePrefix' => request()->routeIs('finance.*') ? 'finance' : 'admin',
            'pdfUrl' => route('reports.payments.pdf', array_filter([
                'start_date' => $this->dateFrom ?: null,
                'end_date' => $this->dateTo ?: null,
                'search' => $this->search ?: null,
                'status' => $this->status ?: null,
                'method' => $this->method ?: null,
                'recorded_by' => $this->recordedBy ?: null,
            ])),
        ]);
    }

    private function canManageFinance(): bool
    {
        return auth()->user()?->hasRole('administrator') ?? false;
    }

    private function refreshBillStatus(?int $billId): void
    {
        if (!$billId) {
            return;
        }

        $bill = Bill::find($billId);

        if (!$bill) {
            return;
        }

        $totalPaid = $bill->payments()->where('status', 'completed')->sum('amount');
        $status = 'pending';

        if ($totalPaid >= $bill->due_amount) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        }

        $bill->update(['status' => $status]);
        $bill->refreshRequestPaymentStatuses();
    }
}

