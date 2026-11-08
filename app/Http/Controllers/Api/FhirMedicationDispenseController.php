<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FhirResourceIdentity;
use App\Models\PharmacyDispense;
use App\Services\FhirResourceIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class FhirMedicationDispenseController extends Controller
{
    private const PAGE_SIZE = 50;

    public function read(string $id, FhirResourceIdentityService $identities): JsonResponse
    {
        $dispense = $identities->medicationDispenseForId($id);
        if (! $dispense) {
            return $this->outcome('not-found', 'The requested MedicationDispense resource was not found.', 404);
        }

        return $this->fhirJson($this->toFhirMedicationDispense($dispense, $id, $identities));
    }

    public function search(Request $request, FhirResourceIdentityService $identities): JsonResponse
    {
        $allowed = ['_id', 'patient', 'subject', 'encounter', 'prescription', 'status', 'whenhandedover', '_count', 'page', '_format'];
        $unsupported = array_diff(array_keys($request->query()), $allowed);
        if ($unsupported !== []) {
            return $this->outcome('not-supported', 'Unsupported search parameter: '.implode(', ', $unsupported), 400);
        }

        $validator = Validator::make($request->query(), [
            '_id' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9.-]+$/'],
            'patient' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'encounter' => ['sometimes', 'string', 'max:255'],
            'prescription' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:preparation,in-progress,cancelled,on-hold,completed,entered-in-error,stopped,declined,unknown'],
            'whenhandedover' => ['sometimes', 'date_format:Y-m-d'],
            '_count' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            '_format' => ['sometimes', 'in:json,application/fhir+json'],
        ]);
        if ($validator->fails()) {
            return $this->outcome('invalid', $validator->errors()->first(), 400);
        }

        $query = $this->patientPrescriptionDispenses()
            ->with([
                'medicineBatch.medicine.medicineType',
                'prescriptionItem.medicine.medicineType',
                'prescriptionItem.prescription.patientVisit.patient.demographic',
            ]);

        $logicalId = $request->query('_id');
        if ($logicalId !== null) {
            $localKey = FhirResourceIdentity::query()
                ->where('resource_type', 'MedicationDispense')
                ->where('logical_id', $logicalId)
                ->value('local_key');
            if ($localKey === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereKey($localKey);
        }

        foreach (['patient', 'subject'] as $parameter) {
            $reference = $request->query($parameter);
            if ($reference === null) {
                continue;
            }
            $patientId = $this->patientIdFromReference($reference);
            if ($patientId === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereHas('prescriptionItem.prescription.patientVisit', fn (Builder $visit) => $visit->where('patient_id', $patientId));
        }

        $encounter = $request->query('encounter');
        if ($encounter !== null) {
            $visitId = $this->encounterIdFromReference($encounter);
            if ($visitId === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereHas('prescriptionItem.prescription', fn (Builder $prescriptions) => $prescriptions->where('patient_visit_id', $visitId));
        }

        $prescription = $request->query('prescription');
        if ($prescription !== null) {
            $itemId = $this->medicationRequestIdFromReference($prescription);
            if ($itemId === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->where('prescription_item_id', $itemId);
        }

        if ($request->query('whenhandedover') !== null) {
            $query->whereDate('created_at', $request->query('whenhandedover'));
        }
        if ($request->query('status') !== null && $request->query('status') !== 'completed') {
            return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
        }

        $pageSize = min((int) $request->query('_count', self::PAGE_SIZE), 100);
        $page = (int) $request->query('page', 1);
        if (($page - 1) * $pageSize > 100000) {
            return $this->outcome('too-costly', 'The requested result page is beyond the supported offset range.', 400);
        }

        $dispenses = $query->orderBy('pharmacy_dispenses.id')->paginate($pageSize, ['pharmacy_dispenses.*'], 'page', $page);
        $entries = $dispenses->getCollection()->map(function (PharmacyDispense $dispense) use ($identities): array {
            $id = $identities->idForMedicationDispense($dispense);

            return [
                'fullUrl' => rtrim((string) config('fhir.base_url'), '/').'/MedicationDispense/'.$id,
                'resource' => $this->toFhirMedicationDispense($dispense, $id, $identities),
                'search' => ['mode' => 'match'],
            ];
        });

        return $this->searchBundle($entries, $dispenses->total(), $request, $page, $dispenses->hasMorePages());
    }

    private function patientPrescriptionDispenses(): Builder
    {
        return PharmacyDispense::query()
            ->where('type', 'dispense')
            ->whereNotNull('prescription_item_id')
            ->whereHas('prescriptionItem.prescription.patientVisit.patient')
            ->whereHas('medicineBatch.medicine');
    }

    private function toFhirMedicationDispense(PharmacyDispense $dispense, string $id, FhirResourceIdentityService $identities): array
    {
        $item = $dispense->prescriptionItem;
        $visit = $item->prescription->patientVisit;
        $medicine = $dispense->medicineBatch->medicine;
        $resource = [
            'resourceType' => 'MedicationDispense',
            'id' => $id,
            'status' => 'completed',
            'medicationReference' => [
                'reference' => 'Medication/'.$identities->idForMedication($medicine),
            ],
            'subject' => [
                'reference' => 'Patient/'.$identities->idForPatient($visit->patient),
            ],
            'authorizingPrescription' => [[
                'reference' => 'MedicationRequest/'.$identities->idForMedicationRequest($item),
            ]],
        ];

        if ($visit) {
            $resource['context'] = [
                'reference' => 'Encounter/'.$identities->idForEncounter($visit),
            ];
        }
        if ($dispense->created_at) {
            $resource['whenHandedOver'] = $dispense->created_at->toIso8601String();
            $resource['meta'] = ['lastUpdated' => $dispense->updated_at?->toIso8601String() ?? $dispense->created_at->toIso8601String()];
        }

        return $resource;
    }

    private function patientIdFromReference(string $reference): ?string
    {
        if (! preg_match('#(?:^|/)Patient/([A-Za-z0-9.-]{1,64})$#', $reference, $matches)) {
            return null;
        }

        return FhirResourceIdentity::query()
            ->where('resource_type', 'Patient')
            ->where('logical_id', $matches[1])
            ->value('local_key');
    }

    private function encounterIdFromReference(string $reference): ?string
    {
        if (! preg_match('#(?:^|/)Encounter/([A-Za-z0-9.-]{1,64})$#', $reference, $matches)) {
            return null;
        }

        return FhirResourceIdentity::query()
            ->where('resource_type', 'Encounter')
            ->where('logical_id', $matches[1])
            ->value('local_key');
    }

    private function medicationRequestIdFromReference(string $reference): ?string
    {
        if (! preg_match('#(?:^|/)MedicationRequest/([A-Za-z0-9.-]{1,64})$#', $reference, $matches)) {
            return null;
        }

        $localKey = FhirResourceIdentity::query()
            ->where('resource_type', 'MedicationRequest')
            ->where('logical_id', $matches[1])
            ->value('local_key');

        return $localKey !== null && ctype_digit((string) $localKey) ? (string) $localKey : null;
    }

    private function searchBundle(iterable $entries, int $total, Request $request, int $page, bool $hasMore = false): JsonResponse
    {
        $base = rtrim((string) config('fhir.base_url'), '/').'/MedicationDispense';
        $query = $request->query();
        $query['page'] = $page;
        $links = [['relation' => 'self', 'url' => $base.'?'.Arr::query($query)]];
        if ($hasMore) {
            $query['page'] = $page + 1;
            $links[] = ['relation' => 'next', 'url' => $base.'?'.Arr::query($query)];
        }

        return $this->fhirJson([
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => $total,
            'link' => $links,
            'entry' => collect($entries)->values()->all(),
        ]);
    }

    private function fhirJson(array $resource, int $status = 200): JsonResponse
    {
        return response()->json($resource, $status, [
            'Content-Type' => 'application/fhir+json; charset=utf-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function outcome(string $code, string $diagnostics, int $status): JsonResponse
    {
        return $this->fhirJson([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code' => $code,
                'diagnostics' => $diagnostics,
            ]],
        ], $status);
    }
}
