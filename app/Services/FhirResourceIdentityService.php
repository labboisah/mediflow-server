<?php

namespace App\Services;

use App\Models\DiagnosticReport;
use App\Models\FhirResourceIdentity;
use App\Models\InvestigationRequest;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PharmacyDispense;
use App\Models\PrescriptionItem;
use App\Models\ServiceRequest;
use App\Models\VitalSign;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class FhirResourceIdentityService
{
    public function idForPatient(Patient $patient): string
    {
        return $this->idFor('Patient', (string) $patient->getKey());
    }

    public function idForEncounter(PatientVisit $visit): string
    {
        return $this->idFor('Encounter', (string) $visit->getKey());
    }

    public function idForObservation(VitalSign $vitalSign): string
    {
        return $this->idFor('Observation', (string) $vitalSign->getKey());
    }

    public function idForServiceRequest(InvestigationRequest|ServiceRequest $request): string
    {
        $source = $request instanceof InvestigationRequest ? 'investigation_request' : 'service_request';

        return $this->idFor('ServiceRequest', $source.':'.$request->getKey());
    }

    public function idForDiagnosticReport(DiagnosticReport $report): string
    {
        return $this->idFor('DiagnosticReport', (string) $report->getKey());
    }

    public function idForMedication(Medicine $medicine): string
    {
        return $this->idFor('Medication', (string) $medicine->getKey());
    }

    public function idForMedicationRequest(PrescriptionItem $item): string
    {
        return $this->idFor('MedicationRequest', (string) $item->getKey());
    }

    public function idForMedicationDispense(PharmacyDispense $dispense): string
    {
        return $this->idFor('MedicationDispense', (string) $dispense->getKey());
    }

    public function idFor(string $resourceType, string $localKey): string
    {
        $attributes = ['resource_type' => $resourceType, 'local_key' => $localKey];
        try {
            return FhirResourceIdentity::query()->firstOrCreate(
                $attributes,
                ['logical_id' => (string) Str::uuid()],
            )->logical_id;
        } catch (QueryException $exception) {
            $identity = FhirResourceIdentity::query()->where($attributes)->first();
            if ($identity) {
                return $identity->logical_id;
            }

            throw $exception;
        }
    }

    public function patientForId(string $logicalId): ?Patient
    {
        $identity = FhirResourceIdentity::query()
            ->where('resource_type', 'Patient')
            ->where('logical_id', $logicalId)
            ->first();

        return $identity
            ? Patient::query()->with('demographic')->find($identity->local_key)
            : null;
    }

    public function encounterForId(string $logicalId): ?PatientVisit
    {
        $localKey = $this->localKeyFor('Encounter', $logicalId);

        return $localKey
            ? PatientVisit::query()->whereHas('patient')->with(['patient.demographic'])->withExists('admissions')->find($localKey)
            : null;
    }

    public function observationForId(string $logicalId): ?VitalSign
    {
        $localKey = $this->localKeyFor('Observation', $logicalId);

        return $localKey
            ? VitalSign::query()->whereHas('patientVisit.patient')->with(['patientVisit.patient.demographic'])->find($localKey)
            : null;
    }

    public function serviceRequestForId(string $logicalId): InvestigationRequest|ServiceRequest|null
    {
        $localKey = $this->localKeyFor('ServiceRequest', $logicalId);
        if (! $localKey || ! str_contains($localKey, ':')) {
            return null;
        }

        [$source, $id] = explode(':', $localKey, 2);

        return match ($source) {
            'investigation_request' => InvestigationRequest::query()
                ->whereHas('patientVisit.patient')
                ->with(['investigation', 'patientVisit.patient.demographic'])
                ->find($id),
            'service_request' => ServiceRequest::query()
                ->whereHas('patientVisit.patient')
                ->with(['service', 'patientVisit.patient.demographic'])
                ->find($id),
            default => null,
        };
    }

    public function diagnosticReportForId(string $logicalId): ?DiagnosticReport
    {
        return DiagnosticReport::query()
            ->whereHas('investigationRequest.patientVisit.patient')
            ->with([
                'investigationRequest.investigation',
                'investigationRequest.patientVisit.patient.demographic',
            ])
            ->find($this->localKeyFor('DiagnosticReport', $logicalId));
    }

    public function medicationForId(string $logicalId): ?Medicine
    {
        return Medicine::query()->find($this->localKeyFor('Medication', $logicalId));
    }

    public function medicationRequestForId(string $logicalId): ?PrescriptionItem
    {
        return PrescriptionItem::query()
            ->whereHas('prescription.patientVisit.patient')
            ->whereHas('medicine')
            ->with([
                'medicine.medicineType',
                'route',
                'prescription.patientVisit.patient.demographic',
            ])
            ->find($this->localKeyFor('MedicationRequest', $logicalId));
    }

    public function medicationDispenseForId(string $logicalId): ?PharmacyDispense
    {
        return PharmacyDispense::query()
            ->where('type', 'dispense')
            ->whereNotNull('prescription_item_id')
            ->whereHas('prescriptionItem.prescription.patientVisit.patient')
            ->whereHas('medicineBatch.medicine')
            ->with([
                'medicineBatch.medicine.medicineType',
                'prescriptionItem.medicine.medicineType',
                'prescriptionItem.prescription.patientVisit.patient.demographic',
            ])
            ->find($this->localKeyFor('MedicationDispense', $logicalId));
    }

    private function localKeyFor(string $resourceType, string $logicalId): ?string
    {
        return FhirResourceIdentity::query()
            ->where('resource_type', $resourceType)
            ->where('logical_id', $logicalId)
            ->value('local_key');
    }
}
