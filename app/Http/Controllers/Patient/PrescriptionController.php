<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Patient;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function create(Patient $patient) {
        return view('patient.prescription.create', compact('patient'));
    }

    public function show(Prescription $prescription) {
        $patient = $prescription->patientVisit?->patient;

        abort_unless($patient, 404);

        return redirect()->route('patient.prescription.create', [
            'patient' => $patient,
            'prescription' => $prescription->id,
        ]);
    }

    public function store(Request $request, Patient $patient) {
        $request->validate([
            "medicine_name" => "required|string|max:255",
            "medicine_type_id" => "nullable|exists:medicine_types,id",
            "treatment_diagnosis" => "required|string|max:1000",
            "dosage" => "required",
            "period" => "required",
            "duration" => "required",
            "route_id" => "required"
        ]);

        $medicine = $this->resolveMedicine($request->medicine_name, $request->medicine_type_id);

        if(!$medicine){
            return back()->withWarning('Please select a medicine type when adding a new medicine.');
        }
        
        $prescription = $patient->currentVisit()->prescriptions()->create([
            'prescribe_by'=>auth()->user()->id,
            'treatment_diagnosis' => $request->treatment_diagnosis,
        ]);

        $prescription->prescriptionItems()->firstOrCreate([
            'medicine_id'=>$medicine->id,
            'route_id'=>$request->route_id,
            'dosage'=>$request->dosage,
            'period'=>$request->period,
            'duration'=>$request->duration,
            ]);
            // Log activity
            $patient->currentVisit()->visitActivities()->create([
                'activity' => "Prescription created for medicine: {$medicine->name}",
                'recorded_by' => auth()->id(),
            ]);
        return redirect()->route('patient.prescription.show',$prescription)->with('success', 'Prescription registered but you can prescribe more and submit to pharmacy');    
    }

    public function addMedicine(Request $request, Prescription $prescription) {
        $request->validate([
            "medicine_name" => "required|string|max:255",
            "medicine_type_id" => "nullable|exists:medicine_types,id",
            "treatment_diagnosis" => "required|string|max:1000",
            "dosage" => "required",
            "period" => "required",
            "duration" => "required",
            'route_id'=>'required'
        ]);

        $medicine = $this->resolveMedicine($request->medicine_name, $request->medicine_type_id);

        if(!$medicine){
            return back()->withWarning('Please select a medicine type when adding a new medicine.');
        }

        $prescription->update(['treatment_diagnosis' => $request->treatment_diagnosis]);
       
        $prescription->prescriptionItems()->firstOrCreate([
            'medicine_id'=>$medicine->id,
            'dosage'=>$request->dosage,
            'period'=>$request->period,
            'duration'=>$request->duration,
            'route_id'=>$request->route_id,
            ]);
        return redirect()->route('patient.prescription.show',$prescription)->with('success', 'Medicine added to the prescription registered but you can prescribe more and submit to pharmacy');    
    }

    public function submit(Prescription $prescription)
    {
        if ($prescription->prescriptionItems()->count() === 0) {
            return back()->withWarning('Add at least one medicine before submitting to pharmacy.');
        }

        if (blank($prescription->treatment_diagnosis)) {
            return back()->withWarning('Please indicate the treatment, infection, or disease before submitting.');
        }

        $prescription->update(['status' => 'submitted']);

        return back()->with('success', 'Prescription submitted to pharmacy.');
    }

    public function startMedication(PrescriptionItem $prescriptionItem)
    {
        abort_unless(auth()->user()->hasRole('doctor'), 403);

        $prescriptionItem->load('medicine', 'prescription.patientVisit');
        $prescriptionItem->startMedication(auth()->id());

        $prescriptionItem->prescription?->patientVisit?->visitActivities()->create([
            'activity' => "Medication started: {$prescriptionItem->medicine?->name}",
            'recorded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Medication started.');
    }

    public function stopMedication(PrescriptionItem $prescriptionItem)
    {
        abort_unless(auth()->user()->hasRole('doctor'), 403);

        $prescriptionItem->load('medicine', 'prescription.patientVisit');
        $prescriptionItem->stopMedication(auth()->id());

        $prescriptionItem->prescription?->patientVisit?->visitActivities()->create([
            'activity' => "Medication stopped: {$prescriptionItem->medicine?->name}",
            'recorded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Medication stopped.');
    }

    public function destroy(Prescription $prescription)
    {
        abort_unless(auth()->user()->hasRole('doctor'), 403);

        $prescription->load([
            'patientVisit.patient',
            'prescriptionItems.drugCharts',
            'prescriptionItems.medicine',
        ]);

        $patient = $prescription->patientVisit?->patient;
        $medicineNames = $prescription->prescriptionItems
            ->pluck('medicine.name')
            ->filter()
            ->implode(', ');

        DB::transaction(function () use ($prescription, $medicineNames) {
            $prescription->patientVisit?->visitActivities()->create([
                'activity' => 'Prescription deleted' . ($medicineNames ? ": {$medicineNames}" : ''),
                'recorded_by' => auth()->id(),
            ]);

            foreach ($prescription->prescriptionItems as $item) {
                $item->drugCharts()->delete();
            }

            $prescription->prescriptionItems()->delete();
            $prescription->delete();
        });

        if ($patient) {
            return redirect()->route('patient.show', $patient)->with('success', 'Prescription deleted.');
        }

        return redirect()->route('patient.index')->with('success', 'Prescription deleted.');
    }

    private function resolveMedicine(string $medicineName, ?string $medicineTypeId): ?Medicine
    {
        $medicineName = trim($medicineName);
        $medicines = Medicine::with('batches')->get();

        $medicine = $medicines->first(fn (Medicine $medicine) => $medicine->displayName() === $medicineName)
            ?? $medicines->first(fn (Medicine $medicine) => strcasecmp($medicine->name, $medicineName) === 0);

        if ($medicine) {
            return $medicine;
        }

        if (! $medicineTypeId) {
            return null;
        }

        return Medicine::firstOrCreate([
            'name' => $medicineName,
        ], [
            'medicine_type_id' => (int) $medicineTypeId,
        ]);
    }
}

