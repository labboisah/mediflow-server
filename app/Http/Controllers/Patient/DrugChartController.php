<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PrescriptionItem;

class DrugChartController extends Controller
{
    public function record(Patient $patient) {
        return view('patient.drugchart.record', compact('patient'));
    }

    public function register(Request $request, Patient $patient) {
        $prescriptionItem = PrescriptionItem::whereKey($request->prescription_item_id)
            ->where('medication_status', PrescriptionItem::STATUS_STARTED)
            ->whereHas('prescription', fn ($query) => $query->where('patient_visit_id', $patient->currentVisit()?->id))
            ->firstOrFail();

        $prescriptionItem->drugCharts()->create([
            'dosage' => $request->dosage,
            'medicine_id'=>$prescriptionItem->medicine->id,
            'mode_of_administration' => $request->mode_of_administration,
            'comment' => $request->comment,
            'time' => date("h:i:s A"),
            'dispensed_by' => auth()->user()->id,
        ]);
        // Log activity
        $patient->currentVisit()->visitActivities()->create([
            'activity' => "Drug chart updated for medicine: {$prescriptionItem->medicine->name}",
            'recorded_by' => auth()->id(),
        ]);
        
        return redirect()->route('patient.show', $patient)->with('success','Drug Chart Recorded Successfully');
    }
}
