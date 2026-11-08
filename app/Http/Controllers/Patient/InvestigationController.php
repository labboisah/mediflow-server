<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InvestigationRequest;
use App\Models\Investigation;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Bill;

class InvestigationController extends Controller
{
    public function create(Patient $patient)
    {
        return view('patient.investigation.create', compact('patient'));
    }

    public function store(Request $request, $patientId)
    {
        $validated = $request->validate([
            'investigation_rows' => 'required|array|min:1',
            'investigation_rows.*.investigation_type' => 'required|exists:investigation_types,id',
            'investigation_rows.*.investigation' => 'required|exists:investigations,id',
            'investigation_rows.*.specimen' => 'nullable|string|max:255',
            'clinical_diagnoses' => 'required|string',
        ]);

        $patient = Patient::findOrFail($patientId);

        $visit = $patient->currentVisit();
        if (!$visit) {
            $visit = $patient->patientVisits()->create([
                'visit_date' => now(),
                'visit_type' => 'Investigation',
                'created_by' => auth()->id(),
                'reason_for_visit' => 'Investigation request creation',
            ]);
        }

        foreach ($validated['investigation_rows'] as $row) {
            $investigationRequest = $visit->investigationRequests()->create([
                'investigation_id' => $row['investigation'],
                'requested_by' => auth()->id(),
                'clinical_diagnoses' => $validated['clinical_diagnoses'],
                'requested_at' => now(),
                'specimen' => $row['specimen'] ?? null,
            ]);

            $investigationRequest->updateLabNumber($investigationRequest->id, $row['investigation']);

            $bill = $investigationRequest->bill()->create([
                'patient_visit_id' => $visit->id,
                'amount' => $investigationRequest->investigation->price ?? 0,
                'service_description' => 'Investigation: ' . $investigationRequest->investigation->name,
                'status' => 'pending',
                'issued_by' => auth()->id(),
                'issued_date' => now(),
                'bill_number' => Bill::generateBillNumber(),
                'due_date' => now()->addDays(2)->toDateString(),
                'department_id' => $investigationRequest->investigation->investigationType->department->id,
            ]);
            $investigation = Investigation::findOrFail($row['investigation']);

            $bill->billInvestigations()->create([
                        'investigation_id' => $investigation->id,
                        'unit_price' => $investigation->price,
                        'quantity' => 1,
                        'subtotal' => $investigation->price,
                    ]);


            $visit->visitActivities()->create([
                'activity' => "Investigation request created for {$investigationRequest->investigation->name}",
                'recorded_by' => auth()->id(),
            ]);
        }

        return redirect()->route('patient.show', $patient)->with('success', 'Investigation requests created successfully.');
    }

    public function show($investigationRequestId)
    {
        $investigationRequest = \App\Models\InvestigationRequest::with([
            'patientVisit.patient.demographic',
            'investigation.investigationType',
            'investigationResults.parameter',
            'requestedBy',
            'performedBy',
        ])->findOrFail($investigationRequestId);

        return view('patient.investigation.show', compact('investigationRequest'));
    }
}


