<?php

namespace App\Livewire\Accountant;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class PaymentWorkspace extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public string $status = '';
    public string $method = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 15;

    public ?int $editingPaymentId = null;
    public string $billNumber = '';
    public ?int $billId = null;
    public string $paymentId = '';
    public string $amount = '';
    public string $paymentMethodId = '';
    public string $paymentDate = '';
    public string $paymentStatus = 'completed';
    public string $referenceNumber = '';
    public string $insuranceProvider = '';
    public string $notes = '';

    public array $availableBills = [];
    public array $selectedBills = [];
    public array $billAmounts = [];
    public string $receiptNumber = '';

    protected array $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'method' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    public function mount(): void
    {
        $this->dateFrom = today()->toDateString();
        $this->dateTo = today()->toDateString();
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentMethodId = (string) (PaymentMethod::where('is_active', true)->orderBy('name')->value('id') ?? '');
    }

    public function render()
    {
        $query = $this->filteredPaymentsQuery();
        $summaryQuery = clone $query;

        return view('components.accountant.payment-workspace', [
            'payments' => $query->paginate($this->perPage),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'selectedBill' => $this->selectedBill(),
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'completedAmount' => (clone $summaryQuery)->where('status', 'completed')->sum('amount'),
                'pendingCount' => (clone $summaryQuery)->where('status', 'pending')->count(),
                'reversedCount' => (clone $summaryQuery)->where('status', 'reversed')->count(),
            ],
        ]);
    }

    public function loadBills(): void
    {
        $this->resetValidation();
        $this->availableBills = [];
        $this->selectedBills = [];
        $this->billAmounts = [];
        $this->receiptNumber = '';

        if (blank($this->billNumber)) {
            $this->addError('billNumber', 'Please enter a bill number.');
            return;
        }

        $bill = Bill::with([
            'patientVisit.patient.demographic',
            'walkinPatient',
            'payments',
        ])
            ->where('bill_number', trim($this->billNumber))
            ->first();

        if (! $bill) {
            $this->addError('billNumber', 'No bill was found with this bill number.');
            return;
        }

        $query = Bill::with(['payments'])
            ->whereIn('status', ['pending', 'partial']);

        if ($bill->patient_visit_id) {
            $query->where('patient_visit_id', $bill->patient_visit_id);
        } elseif ($bill->walkin_id) {
            $query->where('walkin_id', $bill->walkin_id);
        } else {
            $query->where('id', $bill->id);
        }

        $bills = $query->orderBy('issued_date')->get();

        if ($bills->isEmpty()) {
            $this->addError('billNumber', 'No pending or partial bill found for this patient.');
            return;
        }

        $this->availableBills = $bills->map(function (Bill $bill) {
            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'description' => $bill->service_description,
                'amount' => (float) $bill->amount,
                'due_amount' => (float) $bill->due_amount,
                'balance' => (float) $bill->balance,
                'status' => $bill->status,
            ];
        })->toArray();

        foreach ($this->availableBills as $bill) {
            $this->billAmounts[$bill['id']] = $bill['balance'];
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'paymentMethodId' => ['required', 'exists:payment_methods,id'],
            'paymentDate' => ['required', 'date'],
            'paymentStatus' => ['required', 'in:pending,completed,failed,reversed'],
            'referenceNumber' => ['nullable', 'string', 'max:255'],
            'insuranceProvider' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->editingPaymentId) {
            $this->updateExistingPayment($validated);
            return;
        }

        $this->validate([
            'selectedBills' => ['required', 'array', 'min:1'],
        ]);

        DB::transaction(function () use ($validated) {
            $receiptNumber = $this->generateReceiptNumber();

            foreach ($this->selectedBills as $billId) {
                $bill = Bill::with('payments')->findOrFail($billId);

                if (! in_array($bill->status, ['pending', 'partial'], true)) {
                    continue;
                }

                $amount = (float) ($this->billAmounts[$billId] ?? 0);

                if ($amount <= 0) {
                    throw new \Exception("Invalid payment amount for bill {$bill->bill_number}.");
                }

                if ($validated['paymentStatus'] === 'completed' && $amount > (float) $bill->balance) {
                    throw new \Exception("Payment amount cannot exceed balance for bill {$bill->bill_number}.");
                }

                $payment = Payment::create([
                    'bill_id' => $bill->id,
                    'payment_id' => Payment::generatePaymentID(),
                    'receipt_number' => $receiptNumber,
                    'amount' => $amount,
                    'payment_method_id' => $validated['paymentMethodId'],
                    'payment_date' => $validated['paymentDate'],
                    'status' => $validated['paymentStatus'],
                    'reference_number' => $validated['referenceNumber'],
                    'insurance_provider' => $validated['insuranceProvider'],
                    'notes' => $validated['notes'],
                    'paid_by' => Auth::id(),
                ]);

                $this->refreshBillStatus($payment->bill_id);
            }

            $this->receiptNumber = $receiptNumber;
        });

        $this->dispatch('toast', message: 'Payment recorded successfully.', type: 'success');

        $printedReceipt = $this->receiptNumber;

        $this->resetForm();

        $this->receiptNumber = $printedReceipt;
    }

    private function updateExistingPayment(array $validated): void
    {
        $validatedAmount = $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $payment = Payment::where('paid_by', Auth::id())->findOrFail($this->editingPaymentId);
        $bill = $payment->bill;

        $balanceLimit = (float) $bill->balance + (float) $payment->amount;

        if ($validated['paymentStatus'] === 'completed' && (float) $validatedAmount['amount'] > $balanceLimit) {
            $this->addError('amount', 'Payment amount cannot be greater than the bill balance of ' . number_format($balanceLimit, 2) . '.');
            return;
        }

        DB::transaction(function () use ($validated, $validatedAmount, $payment) {
            $payment->update([
                'amount' => $validatedAmount['amount'],
                'payment_method_id' => $validated['paymentMethodId'],
                'payment_date' => $validated['paymentDate'],
                'status' => $validated['paymentStatus'],
                'reference_number' => $validated['referenceNumber'],
                'insurance_provider' => $validated['insuranceProvider'],
                'notes' => $validated['notes'],
            ]);

            $this->refreshBillStatus($payment->bill_id);
        });

        $this->resetForm();
        $this->dispatch('toast', message: 'Payment updated successfully.', type: 'success');
    }

    public function edit(int $paymentId): void
    {
        $payment = Payment::where('paid_by', Auth::id())->findOrFail($paymentId);

        $this->editingPaymentId = $payment->id;
        $this->billId = $payment->bill_id;
        $this->billNumber = $payment->bill?->bill_number ?? '';
        $this->paymentId = $payment->payment_id;
        $this->amount = (string) $payment->amount;
        $this->paymentMethodId = (string) $payment->payment_method_id;
        $this->paymentDate = $payment->payment_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->paymentStatus = $payment->status;
        $this->referenceNumber = $payment->reference_number ?? '';
        $this->insuranceProvider = $payment->insurance_provider ?? '';
        $this->notes = $payment->notes ?? '';
        $this->resetValidation();
    }

    public function reverse(int $paymentId): void
    {
        $payment = Payment::where('paid_by', Auth::id())->findOrFail($paymentId);

        if ($payment->status === 'reversed') {
            $this->dispatch('toast', message: 'This payment is already reversed.', type: 'warning');
            return;
        }

        $payment->update([
            'status' => 'reversed',
            'notes' => trim(($payment->notes ? $payment->notes . "\n" : '') . 'Reversed by ' . Auth::user()->name . ' on ' . now()->format('Y-m-d H:i')),
        ]);

        $this->refreshBillStatus($payment->bill_id);
        $this->dispatch('toast', message: 'Payment reversed successfully.', type: 'warning');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'method']);
        $this->dateFrom = today()->toDateString();
        $this->dateTo = today()->toDateString();
        $this->perPage = 15;
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingPaymentId',
            'billId',
            'billNumber',
            'paymentId',
            'amount',
            'paymentStatus',
            'referenceNumber',
            'insuranceProvider',
            'notes',
            'availableBills',
            'selectedBills',
            'billAmounts',
        ]);

        $this->paymentStatus = 'completed';
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentMethodId = (string) (PaymentMethod::where('is_active', true)->orderBy('name')->value('id') ?? '');
        $this->resetValidation();
    }

    public function refreshList(): void
    {
        //
    }

    private function filteredPaymentsQuery(): Builder
    {
        return Payment::query()
            ->with(['bill.patientVisit.patient.demographic', 'bill.walkinPatient', 'paymentMethod', 'recordedBy'])
            ->where('paid_by', Auth::id())
            ->when(trim($this->search) !== '', function (Builder $query) {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->where('payment_id', 'like', "%{$search}%")
                        ->orWhere('receipt_number', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('bill', fn (Builder $bill) => $bill->where('bill_number', 'like', "%{$search}%"))
                        ->orWhereHas('bill.walkinPatient', fn (Builder $patient) => $patient->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('bill.patientVisit.patient', fn (Builder $patient) => $patient->where('hospital_number', 'like', "%{$search}%"))
                        ->orWhereHas('bill.patientVisit.patient.demographic', function (Builder $patient) use ($search) {
                            $patient
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->method !== '', fn (Builder $query) => $query->where('payment_method_id', $this->method))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('payment_date', '<=', $this->dateTo))
            ->latest('payment_date')
            ->latest('created_at');
    }

    private function selectedBill(): ?Bill
    {
        if (trim($this->billNumber) === '') {
            return null;
        }

        return Bill::with(['patientVisit.patient.demographic', 'walkinPatient', 'payments'])
            ->where('bill_number', trim($this->billNumber))
            ->first();
    }

    private function refreshBillStatus(?int $billId): void
    {
        if (! $billId) {
            return;
        }

        $bill = Bill::find($billId);

        if (! $bill) {
            return;
        }

        $totalPaid = $bill->payments()->where('status', 'completed')->sum('amount');

        $status = 'pending';

        if ($totalPaid >= (float) $bill->due_amount) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        }

        $bill->update(['status' => $status]);
        $bill->refreshRequestPaymentStatuses();
    }

    private function generateReceiptNumber(): string
    {
        return 'RC' . now()->format('ymdHis') . Auth::id();
    }
}
