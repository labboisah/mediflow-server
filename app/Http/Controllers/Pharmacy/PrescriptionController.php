<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Prescription;

class PrescriptionController extends Controller
{
    public function index()
    {
        $prescriptions = Prescription::with([
            'patientVisit.patient.demographic',
            'prescribedBy.department',
            'prescriptionItems.medicine.batches',
        ])
            ->where('status', 'submitted')
            ->latest()
            ->paginate(25);

        return view('pharmacy.prescription.index', compact('prescriptions'));
    }
}
