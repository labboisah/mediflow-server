<?php

namespace App\Services;

use App\Models\{Bill, MedicineBatch, Payment, PharmacyDispense, PharmacyService, Service, StockTransaction, StockTransactionItem, WalkinPatient};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PharmacyCheckout
{
    public function complete(array $cart, array $validated, string $patientName = '', string $patientPhone = ''): StockTransaction
    {
        $user = auth()->user();
        abort_unless($user && $user->hasAnyRole(['pharmacist', 'pharmacy_technician']), 403);
        abort_unless(app(LicenseService::class)->moduleEnabled('pharmacy') && app(LicenseService::class)->moduleEnabled('billing'), 403);
        $patientName = trim($patientName);
        $hasServices = collect($cart)->contains(fn ($item) => ! empty($item['service_id']) || ! empty($item['catalog_service_id']));
        Validator::make(compact('cart', 'patientName', 'patientPhone') + $validated, [
            'cart' => ['required', 'array', 'min:1', 'max:100'],
            'cart.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'cart.*.batch_id' => ['nullable', 'integer', 'min:1'],
            'cart.*.service_id' => ['nullable', 'integer', 'min:1'],
            'cart.*.catalog_service_id' => ['nullable', 'integer', 'min:1'],
            'patientName' => [Rule::requiredIf($hasServices), 'nullable', 'string', 'max:255'],
            'patientPhone' => ['nullable', 'string', 'max:30'],
            'paymentMethodId' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'referenceNumber' => ['nullable', 'string', 'max:255'],
        ])->validate();
        foreach ($cart as $item) {
            $selectedTypes = count(array_filter([
                ! empty($item['batch_id']),
                ! empty($item['service_id']),
                ! empty($item['catalog_service_id']),
            ]));
            if ($selectedTypes !== 1) {
                throw ValidationException::withMessages(['cart' => 'Select a medicine or a service for each item.']);
            }
        }
        return DB::transaction(function () use ($cart, $validated, $patientName, $patientPhone) {
            $transaction = StockTransaction::create([
                'total_amount' => 0,
                'patient_name' => $patientName ?: null,
                'type' => 'dispense',
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;
            $medicineNames = [];

            foreach ($cart as $item) {
                if (($item['catalog_service_id'] ?? null) !== null) {
                    $service = Service::whereKey($item['catalog_service_id'])->lockForUpdate()->first();
                    if (! $service || ! $service->is_active) {
                        throw ValidationException::withMessages(['cart' => 'A selected general service is no longer available. Remove it from the cart.']);
                    }
                    $quantity = (int) $item['quantity'];
                    $price = (float) $service->price;
                    if ($price <= 0 || round((float) ($item['price'] ?? -1), 2) !== round($price, 2)) {
                        throw ValidationException::withMessages(['cart' => "The charge for {$service->name} changed. Remove and add it again to confirm the new charge."]);
                    }
                    $subtotal = round($price * $quantity, 2);
                    $transaction->serviceItems()->create([
                        'service_id' => $service->id, 'name' => $service->name,
                        'quantity' => $quantity, 'price' => $price, 'subtotal' => $subtotal,
                    ]);
                    $totalAmount += $subtotal;
                    $medicineNames[] = "{$service->name} x {$quantity}";
                    continue;
                }
                if (($item['service_id'] ?? null) !== null) {
                    $service = PharmacyService::whereKey($item['service_id'])->lockForUpdate()->first();
                    if (! $service || ! $service->is_active) {
                        throw ValidationException::withMessages(['cart' => 'A selected service is no longer available. Remove it from the cart.']);
                    }
                    $quantity = (int) $item['quantity'];
                    $price = (float) $service->price;
                    if ($price <= 0 || round((float) ($item['price'] ?? -1), 2) !== round($price, 2)) {
                        throw ValidationException::withMessages(['cart' => "The charge for {$service->name} changed. Remove and add it again to confirm the new charge."]);
                    }
                    $subtotal = round($price * $quantity, 2);
                    $transaction->serviceItems()->create([
                        'pharmacy_service_id' => $service->id, 'name' => $service->name,
                        'quantity' => $quantity, 'price' => $price, 'subtotal' => $subtotal,
                    ]);
                    $totalAmount += $subtotal;
                    $medicineNames[] = "{$service->name} x {$quantity}";
                    continue;
                }
                $batch = MedicineBatch::with('medicine')->whereKey($item['batch_id'])->lockForUpdate()->firstOrFail();
                $quantity = (int) $item['quantity'];
                $price = (float) $batch->selling_price;
                $subtotal = round($price * $quantity, 2);

                if ($quantity < 1 || $batch->quantity_remaining < $quantity || ! $batch->expiry_date || substr((string) $batch->expiry_date, 0, 10) < today()->toDateString()) {
                    throw ValidationException::withMessages([
                        'cart' => "Insufficient or expired stock for {$batch->medicine?->name}.",
                    ]);
                }

                $totalAmount += $subtotal;
                $medicineNames[] = "{$batch->medicine?->name} x {$quantity}";

                StockTransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'medicine_batch_id' => $batch->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);

                PharmacyDispense::create([
                    'medicine_batch_id' => $batch->id,
                    'type' => 'dispense',
                    'quantity' => $quantity,
                    'reference' => $transaction->id,
                    'created_by' => auth()->id(),
                ]);

                $batch->decrement('quantity_remaining', $quantity);
            }

            if ($totalAmount <= 0 || $totalAmount > 999999999.99) {
                throw ValidationException::withMessages(['cart' => 'The transaction total is outside the supported range.']);
            }
            $walkin = $patientName !== '' ? WalkinPatient::create([
                'name' => $patientName, 'phone_number' => $patientPhone,
            ]) : null;
            $bill = Bill::create([
                'walkin_id' => $walkin?->id,
                'department_id' => auth()->user()?->department_id,
                'bill_number' => Bill::generateBillNumber(),
                'service_description' => \Illuminate\Support\Str::limit('Pharmacy transaction: ' . implode(', ', $medicineNames), 255, ''),
                'amount' => $totalAmount,
                'due_amount' => $totalAmount,
                'status' => 'pending',
                'issued_by' => auth()->id(),
                'issued_date' => now(),
                'due_date' => now(),
                'notes' => 'Generated from pharmacy transaction #' . $transaction->id,
            ]);

            $payment = Payment::create([
                'payment_id' => Payment::generatePaymentID(),
                'amount' => $totalAmount,
                'payment_method_id' => (int) $validated['paymentMethodId'],
                'reference_number' => ($validated['referenceNumber'] ?? '') ?: null,
                'status' => 'completed',
                'notes' => 'Payment collected for pharmacy transaction #' . $transaction->id,
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

            return $transaction;
        });

    }
}
