<?php

namespace App\Livewire\Pharmacy;

use App\Models\MedicineBatch;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\StockTransaction;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class TransactionWorkspace extends Component
{
    public string $search = '';
    public string $batchId = '';
    public int|string $quantity = 1;
    public string $paymentMethodId = '';
    public string $referenceNumber = '';
    public string $patientName = '';
    public string $patientPhone = '';
    #[\Livewire\Attributes\Locked]
    public array $cart = [];
    #[\Livewire\Attributes\Locked]
    public ?int $receiptTransactionId = null;

    public function mount(): void
    {
        $this->paymentMethodId = (string) (PaymentMethod::where('is_active', true)->orderBy('name')->value('id') ?? '');
    }

    public function render()
    {
        return view('components.pharmacy.transaction-workspace', [
            'batches' => $this->availableBatches(),
            'catalogServices' => Service::query()->where('is_active', true)->orderBy('name')->get(),
            'services' => \App\Models\PharmacyService::where('is_active', true)->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'total' => $this->total(),
            'receiptTransaction' => $this->receiptTransaction(),
        ]);
    }

    public function addToCart(): void
    {
        $validated = $this->validate([
            'batchId' => ['required', 'integer', 'exists:medicine_batches,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $batch = MedicineBatch::with('medicine')->findOrFail($validated['batchId']);
        $quantity = (int) $validated['quantity'];

        if ($batch->quantity_remaining < $quantity) {
            $this->feedback("Only {$batch->quantity_remaining} available for {$batch->medicine?->name}.", 'warning');
            return;
        }

        $existingIndex = collect($this->cart)->search(fn ($item) => (int) ($item['batch_id'] ?? 0) === $batch->id);

        if ($existingIndex !== false) {
            $newQuantity = (int) $this->cart[$existingIndex]['quantity'] + $quantity;

            if ($newQuantity > $batch->quantity_remaining) {
                $this->feedback("Only {$batch->quantity_remaining} available for {$batch->medicine?->name}.", 'warning');
                return;
            }

            $this->cart[$existingIndex]['quantity'] = $newQuantity;
            $this->cart[$existingIndex]['subtotal'] = round($newQuantity * (float) $batch->selling_price, 2);
        } else {
            $this->cart[] = [
                'batch_id' => $batch->id,
                'medicine' => $batch->medicine?->name ?? 'N/A',
                'batch_number' => $batch->batch_number,
                'available' => (int) $batch->quantity_remaining,
                'price' => (float) $batch->selling_price,
                'quantity' => $quantity,
                'subtotal' => round($quantity * (float) $batch->selling_price, 2),
            ];
        }

        $this->reset(['batchId']);
        $this->quantity = 1;
        $this->feedback('Medicine added to cart.');
    }

    public function addBatchToCart(int $batchId): void
    {
        $this->batchId = (string) $batchId;
        $this->addToCart();
    }

    public function addServiceToCart(int $id): void
    {
        $this->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10000']]);
        $service = \App\Models\PharmacyService::where('is_active', true)->findOrFail($id);
        $index = collect($this->cart)->search(fn ($item) => (int) ($item['service_id'] ?? 0) === $id);
        $quantity = (int) $this->quantity;
        if ($index !== false) {
            $quantity += (int) $this->cart[$index]['quantity'];
            unset($this->cart[$index]);
        }
        $this->cart[] = [
            'service_id' => $id, 'batch_id' => null, 'medicine' => $service->name,
            'batch_number' => 'Service', 'price' => (float) $service->price,
            'quantity' => $quantity, 'subtotal' => round($quantity * (float) $service->price, 2),
        ];
        $this->cart = array_values($this->cart);
        $this->quantity = 1;
    }

    public function addCatalogServiceToCart(int $id): void
    {
        $this->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10000']]);
        $service = Service::query()->where('is_active', true)->findOrFail($id);
        $index = collect($this->cart)->search(fn ($item) => (int) ($item['catalog_service_id'] ?? 0) === $id);
        $quantity = (int) $this->quantity;

        if ($index !== false) {
            $quantity += (int) $this->cart[$index]['quantity'];
            $this->cart[$index]['quantity'] = $quantity;
            $this->cart[$index]['subtotal'] = round($quantity * (float) $service->price, 2);
        } else {
            $this->cart[] = [
                'catalog_service_id' => $service->id,
                'batch_id' => null,
                'medicine' => $service->name,
                'batch_number' => 'General service',
                'price' => (float) $service->price,
                'quantity' => $quantity,
                'subtotal' => round($quantity * (float) $service->price, 2),
            ];
        }

        $this->quantity = 1;
    }

    public function removeFromCart(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->feedback('Item removed from cart.', 'warning');
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->receiptTransactionId = null;
        $this->patientName = '';
        $this->patientPhone = '';
        $this->feedback('Cart cleared.', 'warning');
    }

    public function completeTransaction(): void
    {
        $validated = $this->validate([
            'paymentMethodId' => ['required', 'integer', 'exists:payment_methods,id'],
            'referenceNumber' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($this->cart)) {
            $this->feedback('Add at least one medicine or service to the cart.', 'warning');
            return;
        }

        $transaction = app(\App\Services\PharmacyCheckout::class)->complete(
            $this->cart, $validated, $this->patientName, $this->patientPhone
        );

        $this->cart = [];
        $this->referenceNumber = '';
        $this->patientName = '';
        $this->patientPhone = '';
        $this->receiptTransactionId = $transaction->id;
        $this->feedback('Transaction completed. Receipt is ready.');
        $this->dispatch('print-pharmacy-thermal');
    }

    private function availableBatches()
    {
        return MedicineBatch::with('medicine')
            ->where('quantity_remaining', '>', 0)
            ->whereDate('expiry_date', '>=', today())
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('batch_number', 'like', "%{$search}%")
                        ->orWhereHas('medicine', fn ($medicineQuery) => $medicineQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%")
                            ->orWhere('manufacturer', 'like', "%{$search}%"));
                });
            })
            ->orderBy('expiry_date')
            ->limit(30)
            ->get();
    }

    private function total(): float
    {
        return collect($this->cart)->sum(fn ($item) => (float) $item['subtotal']);
    }

    private function receiptTransaction(): ?StockTransaction
    {
        return $this->receiptTransactionId
            ? StockTransaction::with(['stockTransactionItems.medicineBatch.medicine', 'serviceItems', 'bill', 'payment.paymentMethod', 'createdBy'])->find($this->receiptTransactionId)
            : null;
    }

    private function feedback(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }
}

