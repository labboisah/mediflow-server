<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MedicineBatch;
use App\Models\PaymentMethod;
use App\Models\StockTransaction;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index() {
        $transactions = StockTransaction::with(['stockTransactionItems.medicineBatch.medicine', 'serviceItems', 'createdBy', 'bill', 'payment.paymentMethod'])
            ->latest()
            ->get();

        return view('pharmacy.transaction.index', compact('transactions'));
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? today()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? today()->toDateString();

        $transactions = StockTransaction::with(['stockTransactionItems.medicineBatch.medicine', 'serviceItems', 'createdBy'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->latest()
            ->get();

        return view('pharmacy.transaction.report', compact('transactions', 'from', 'to'));
    }

    public function create() {
        $batches = MedicineBatch::with('medicine')
            ->where('quantity_remaining', '>', 0)
            ->whereDate('expiry_date', '>=', today())
            ->latest()
            ->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();

        return view('pharmacy.transaction.create', compact('batches', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => ['required', 'json'],
            'payment_method_id' => ['required', 'integer'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'patient_name' => ['nullable', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:30'],
        ]);
        $items = json_decode($request->items, true);
        if (! is_array($items)) {
            throw ValidationException::withMessages(['items' => 'Provide transaction items.']);
        }
        $cart = array_map(fn ($item) => is_array($item) ? [
            'batch_id' => $item['batchId'] ?? $item['batch_id'] ?? null,
            'service_id' => $item['service_id'] ?? null,
            'quantity' => $item['quantity'] ?? null,
            'price' => $item['price'] ?? null,
        ] : [], $items);
        $transaction = app(\App\Services\PharmacyCheckout::class)->complete($cart, [
            'paymentMethodId' => $request->payment_method_id,
            'referenceNumber' => $request->reference_number,
        ], $request->patient_name ?? '', $request->patient_phone ?? '');

        return redirect()->route('pharmacy.receipts.show', $transaction->payment_id)
            ->with('success', 'Transaction and payment registered');
    }
}
