<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function receipt(Payment $payment)
    {
        $payment->load(['bill.services', 'bill.serviceRequests.service', 'bill.investigationRequests.investigation', 'patient', 'recordedBy', 'paymentMethod']);

        return view('accountant.payments.receipt', compact('payment'));
    }
}
