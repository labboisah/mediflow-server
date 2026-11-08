<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = \App\Models\Payment::with('bill','user')
                        ->latest()
                        ->paginate(15);

        return view('payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Bill $bill)
    {
        $bill->load('payments');

        return view('payments.create', compact('bill'));
    }

   

    /**
     * Display the specified resource.
     */
    

public function store(Request $request)
{
    $request->validate([
        'bill_id' => 'required|exists:bills,id',
        'amount' => 'required|numeric|min:1',
        'payment_method' => 'nullable|string|max:100',
        'note' => 'nullable|string'
    ]);

    $bill = Bill::findOrFail($request->bill_id);

    // Prevent overpayment
    if ($request->amount > $bill->balance) {
        return back()->with('error', 'Payment exceeds remaining balance.');
    }

    DB::transaction(function () use ($request, $bill) {

        // Create payment
        Payment::create([
            'bill_id' => $bill->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method ?? 'Cash',
            'note' => $request->note,
            'user_id' => auth()->id(),
        ]);

        // Update bill totals
        $bill->total_paid += $request->amount;
        $bill->balance -= $request->amount;

        // Update payment status
        if ($bill->balance == 0) {
            $bill->payment_status = 'paid';
        } elseif ($bill->total_paid > 0) {
            $bill->payment_status = 'partial';
        } else {
            $bill->payment_status = 'unpaid';
        }

        $bill->save();
    });

    AuditLog::create([
        'action' => 'Payment Created',
        'description' => 'Payment of ₦'.$request->amount.' recorded for Bill '.$bill->bill_no,
        'user_id' => auth()->id(),
    ]);

    if($bill->balance == 0){
        return redirect()
        ->route('payments.receipt', $bill)
        ->with('success', 'Payment recorded successfully. Pls go ahead to print the receipt.');
    }

    return redirect()
        ->route('payments.create', $bill)
        ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\Payment $payment)
    {
        $payment->load('bill');

        return view('payments.edit', compact('payment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Payment $payment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $bill = $payment->bill;

        // Step 1: Remove old payment from bill
        $bill->total_paid -= $payment->amount;
        $bill->balance += $payment->amount;

        // Step 2: Prevent overpayment
        if ($request->amount > $bill->balance) {
            return back()->with('error', 'Updated payment exceeds remaining balance.');
        }

        // Step 3: Update payment
        $payment->update([
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'note' => $request->note,
        ]);

        // Step 4: Re-apply new payment
        $bill->total_paid += $request->amount;
        $bill->balance -= $request->amount;

        // Step 5: Update status
        if ($bill->balance == 0) {
            $bill->payment_status = 'paid';
        } elseif ($bill->total_paid > 0) {
            $bill->payment_status = 'partial';
        } else {
            $bill->payment_status = 'unpaid';
        }

        $bill->save();

        AuditLog::create([
            'action' => 'Payment Updated',
            'description' => 'Payment of ₦'.$request->amount.' updated for Bill '.$bill->bill_no,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Payment $payment)
    {
        $bill = $payment->bill;

        DB::transaction(function () use ($payment, $bill) {

            // Reverse payment from bill
            $bill->total_paid -= $payment->amount;
            $bill->balance += $payment->amount;

            // Update payment status
            if ($bill->total_paid == 0) {
                $bill->payment_status = 'unpaid';
            } elseif ($bill->balance > 0) {
                $bill->payment_status = 'partial';
            } else {
                $bill->payment_status = 'paid';
            }

            $bill->save();

            // Delete payment
            $payment->delete();
        });

        AuditLog::create([
            'action' => 'Payment Deleted',
            'description' => 'Payment of ₦'.$request->amount.' deleted for Bill '.$bill->bill_no,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment deleted successfully.');
    }
    

    public function receipt(Request $request, Bill $bill)
    {
        // Load bill relationships
        $bill->load([
            'items.service',
            'items.revenueDistribution',
            'user',
            'payments.user' // assuming bill has payments
        ]);

        // Get the latest payment (or adjust as needed)
        $payment = $bill->payments()->latest()->first();

        if (!$payment) {
            abort(404, 'Payment not found');
        }
        if ($request->type === 'thermal') {
            return view('payments.thermalReceipt', compact(
                'bill', 
            ));
        }
        // For in-browser printable version
        if ($request->query('format') !== 'pdf') {
            return view('payments.receipt', compact('payment', 'bill'));
        }

        // PDF download version
        $pdf = Pdf::loadView('payments.receipt', compact('payment', 'bill'));

        return $pdf->download('receipt-' . $payment->id . '.pdf');
    }
}
