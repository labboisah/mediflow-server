<?php

namespace App\Livewire\Pharmacy;

use App\Models\Bill;
use App\Models\MedicineBatch;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PharmacyDispense;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockTransaction;
use App\Models\StockTransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class PrescriptionDispenseWorkspace extends Component
{
    public Prescription $prescription;
    public array $selected = [];
    public array $quantities = [];
    public string $paymentMethodId = '';
    public string $referenceNumber = '';
    public ?int $receiptTransactionId = null;

    public function mount(Prescription $prescription): void
    {
        $this->prescription = $prescription->load([
            'patientVisit.patient.demographic',
            'prescribedBy.department',
            'prescriptionItems.medicine.batches',
            'prescriptionItems.route',
        ]);

        $this->paymentMethodId = (string) (PaymentMethod::where('is_active', true)->orderBy('name')->value('id') ?? '');

        foreach ($this->prescription->prescriptionItems as $item) {
            $suggested = $this->suggestedQuantity($item);
            $available = $item->medicine?->availableQuantity() ?? 0;
            $canDispense = ! $this->isPaid() && $available > 0 && $item->isStarted();

            $this->selected[$item->id] = $canDispense;
            $this->quantities[$item->id] = $canDispense ? max(1, min($suggested, $available)) : 0;
        }
    }

    public function render()
    {
        return view('components.pharmacy.prescription-dispense-workspace', [
            'rows' => $this->rows(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'selectedTotal' => $this->selectedTotal(),
            'unavailableRows' => $this->unavailableRows(),
            'receiptTransaction' => $this->receiptTransaction(),
            'isPaid' => $this->isPaid(),
        ]);
    }

    public function dispense(): void
    {
        $this->prescription->refresh();

        if ($this->isPaid()) {
            $this->feedback('This prescription has already been paid. Additional payment is blocked.', 'warning');
            return;
        }

        $validated = $this->validate([
            'paymentMethodId' => ['required', 'integer', 'exists:payment_methods,id'],
            'referenceNumber' => ['nullable', 'string', 'max:255'],
        ]);

        $items = collect($this->rows())
            ->filter(fn ($row) => $row['selected'] && (int) $row['quantity'] > 0);

        if ($items->isEmpty()) {
            $this->feedback('Select at least one available medicine to dispense.', 'warning');
            return;
        }

        $transaction = DB::transaction(function () use ($items, $validated) {
            $prescription = Prescription::whereKey($this->prescription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array((string) $prescription->status, ['paid', 'dispensed'], true)) {
                throw ValidationException::withMessages([
                    'selected' => 'This prescription has already been paid. Additional payment is blocked.',
                ]);
            }

            $transaction = StockTransaction::create([
                'total_amount' => 0,
                'type' => 'dispense',
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;
            $medicineNames = [];

            foreach ($items as $row) {
                $remainingQuantity = (int) $row['quantity'];
                $item = PrescriptionItem::with('medicine')->findOrFail($row['item_id']);
                $medicineNames[] = "{$item->medicine?->name} x {$remainingQuantity}";

                $batches = MedicineBatch::with('medicine')
                    ->where('medicine_id', $item->medicine_id)
                    ->where('quantity_remaining', '>', 0)
                    ->whereDate('expiry_date', '>=', today())
                    ->orderBy('expiry_date')
                    ->lockForUpdate()
                    ->get();

                if ($batches->sum('quantity_remaining') < $remainingQuantity) {
                    throw ValidationException::withMessages([
                        'selected' => "Insufficient stock for {$item->medicine?->name}.",
                    ]);
                }

                foreach ($batches as $batch) {
                    if ($remainingQuantity <= 0) {
                        break;
                    }

                    $dispenseQuantity = min($remainingQuantity, (int) $batch->quantity_remaining);
                    $price = (float) $batch->selling_price;
                    $subtotal = round($dispenseQuantity * $price, 2);
                    $totalAmount += $subtotal;

                    StockTransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'medicine_batch_id' => $batch->id,
                        'prescription_item_id' => $item->id,
                        'quantity' => $dispenseQuantity,
                        'price' => $price,
                        'subtotal' => $subtotal,
                    ]);

                    PharmacyDispense::create([
                        'medicine_batch_id' => $batch->id,
                        'prescription_item_id' => $item->id,
                        'type' => 'dispense',
                        'quantity' => $dispenseQuantity,
                        'reference' => $transaction->id,
                        'created_by' => auth()->id(),
                    ]);

                    $batch->decrement('quantity_remaining', $dispenseQuantity);
                    $remainingQuantity -= $dispenseQuantity;
                }
            }

            $bill = Bill::create([
                'patient_visit_id' => $this->prescription->patient_visit_id,
                'department_id' => auth()->user()?->department_id,
                'bill_number' => Bill::generateBillNumber(),
                'service_description' => 'Prescription dispense: ' . implode(', ', $medicineNames),
                'amount' => $totalAmount,
                'due_amount' => $totalAmount,
                'status' => 'pending',
                'issued_by' => auth()->id(),
                'issued_date' => now(),
                'due_date' => now(),
                'notes' => 'Generated from prescription #' . $this->prescription->id,
            ]);

            $payment = Payment::create([
                'payment_id' => Payment::generatePaymentID(),
                'amount' => $totalAmount,
                'payment_method_id' => (int) $validated['paymentMethodId'],
                'reference_number' => $validated['referenceNumber'] ?: null,
                'status' => 'completed',
                'notes' => 'Payment collected for prescription #' . $this->prescription->id,
                'bill_id' => $bill->id,
                'paid_by' => auth()->id(),
                'payment_date' => now(),
            ]);

            $bill->update(['status' => 'paid']);

            $transaction->update([
                'total_amount' => $totalAmount,
                'reference' => $bill->bill_number,
                'bill_id' => $bill->id,
                'payment_id' => $payment->id,
            ]);

            $prescription->update(['status' => 'paid']);

            return $transaction;
        });

        $this->receiptTransactionId = $transaction->id;
        $this->prescription->refresh()->load('prescriptionItems.medicine.batches', 'prescriptionItems.route');
        $this->feedback('Prescription medicines dispensed and payment recorded.');
        $this->dispatch('print-prescription-dispense-receipt');
    }

    private function rows()
    {
        return $this->prescription->prescriptionItems->map(function (PrescriptionItem $item) {
            $isPaid = $this->isPaid();
            $suggested = $this->suggestedQuantity($item);
            $available = $item->medicine?->availableQuantity() ?? 0;
            $quantity = max(0, (int) ($this->quantities[$item->id] ?? 0));
            $pricing = $this->batchPricing((int) $item->medicine_id, $quantity);
            $unitPrice = $pricing['unit_price'];

            return [
                'item_id' => $item->id,
                'medicine' => $item->medicine?->name ?? 'N/A',
                'generic' => $item->medicine?->generic_name,
                'company' => $item->medicine?->manufacturer ?? 'N/A',
                'route' => $item->route?->name ?? 'N/A',
                'dosage' => $item->dosage,
                'period' => $item->period,
                'duration' => $item->duration,
                'status' => $item->isStarted() ? 'Started' : 'Stopped',
                'suggested_quantity' => $suggested,
                'available' => $available,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $pricing['amount'],
                'selected' => ! $isPaid && (bool) ($this->selected[$item->id] ?? false),
                'shortage' => max(0, $suggested - $available),
            ];
        });
    }

    private function batchPricing(int $medicineId, int $quantity): array
    {
        if ($quantity < 1) {
            return ['unit_price' => 0.0, 'amount' => 0.0];
        }

        $remaining = $quantity;
        $amount = 0.0;
        $firstPrice = 0.0;

        $batches = MedicineBatch::where('medicine_id', $medicineId)
            ->where('quantity_remaining', '>', 0)
            ->whereDate('expiry_date', '>=', today())
            ->orderBy('expiry_date')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (int) $batch->quantity_remaining);
            $price = (float) $batch->selling_price;
            $firstPrice = $firstPrice ?: $price;
            $amount += $take * $price;
            $remaining -= $take;
        }

        return [
            'unit_price' => $quantity > 0 ? round($amount / $quantity, 2) : $firstPrice,
            'amount' => round($amount, 2),
        ];
    }

    private function unavailableRows()
    {
        return $this->rows()
            ->filter(fn ($row) => $row['available'] <= 0 || $row['shortage'] > 0 || $row['status'] !== 'Started')
            ->values();
    }

    private function selectedTotal(): float
    {
        return $this->rows()
            ->filter(fn ($row) => $row['selected'])
            ->sum(fn ($row) => (float) $row['amount']);
    }

    private function suggestedQuantity(PrescriptionItem $item): int
    {
        $duration = max(1, (int) preg_replace('/[^0-9]/', '', (string) $item->duration));
        $period = strtolower((string) $item->period);

        if (preg_match('/(\d+)\s*hour/', $period, $match)) {
            $hours = max(1, (int) $match[1]);
            return max(1, (int) ceil((24 / $hours) * $duration));
        }

        if (preg_match('/(\d+)\s*daily/', $period, $match)) {
            return max(1, (int) $match[1] * $duration);
        }

        if (str_contains($period, 'daily')) {
            return $duration;
        }

        if (preg_match('/(\d+)\s*weekly/', $period, $match)) {
            return max(1, (int) ceil(((int) $match[1] * $duration) / 7));
        }

        return $duration;
    }

    private function receiptTransaction(): ?StockTransaction
    {
        return $this->receiptTransactionId
            ? StockTransaction::with(['stockTransactionItems.medicineBatch.medicine', 'stockTransactionItems.prescriptionItem.route', 'bill', 'payment.paymentMethod', 'createdBy'])->find($this->receiptTransactionId)
            : null;
    }

    private function isPaid(): bool
    {
        return in_array((string) $this->prescription->status, ['paid', 'dispensed'], true);
    }

    private function feedback(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }
}

