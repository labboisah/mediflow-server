<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FhirResourceIdentity;
use App\Models\VitalSign;
use App\Services\FhirResourceIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class FhirObservationController extends Controller
{
    private const PAGE_SIZE = 50;

    public function read(string $id, FhirResourceIdentityService $identities): JsonResponse
    {
        $vitalSign = $identities->observationForId($id);
        if (! $vitalSign) {
            return $this->outcome('not-found', 'The requested Observation resource was not found.', 404);
        }

        return $this->fhirJson($this->toFhirObservation($vitalSign, $id, $identities));
    }

    public function search(Request $request, FhirResourceIdentityService $identities): JsonResponse
    {
        $allowed = ['_id', 'patient', 'subject', 'date', '_count', 'page', '_format'];
        $unsupported = array_diff(array_keys($request->query()), $allowed);
        if ($unsupported !== []) {
            return $this->outcome('not-supported', 'Unsupported search parameter: '.implode(', ', $unsupported), 400);
        }

        $validator = Validator::make($request->query(), [
            '_id' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9.-]+$/'],
            'patient' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            '_count' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            '_format' => ['sometimes', 'in:json,application/fhir+json'],
        ]);
        if ($validator->fails()) {
            return $this->outcome('invalid', $validator->errors()->first(), 400);
        }

        $query = VitalSign::query()
            ->whereHas('patientVisit.patient')
            ->where(function (Builder $values): void {
                $values->whereNotNull('body_temperature')
                    ->orWhereNotNull('blood_pressure_systolic')
                    ->orWhereNotNull('blood_pressure_diastolic')
                    ->orWhereNotNull('heart_rate')
                    ->orWhereNotNull('respiratory_rate')
                    ->orWhereNotNull('oxygen_saturation')
                    ->orWhereNotNull('weight')
                    ->orWhereNotNull('height');
            })
            ->with(['patientVisit.patient.demographic']);

        $logicalId = $request->query('_id');
        if ($logicalId !== null) {
            $localKey = FhirResourceIdentity::query()
                ->where('resource_type', 'Observation')
                ->where('logical_id', $logicalId)
                ->value('local_key');
            if ($localKey === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereKey($localKey);
        }

        foreach (['patient', 'subject'] as $parameter) {
            $patientReference = $request->query($parameter);
            if ($patientReference === null) {
                continue;
            }
            if (! preg_match('#(?:^|/)Patient/([A-Za-z0-9.-]{1,64})$#', $patientReference, $matches)) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $patientId = FhirResourceIdentity::query()
                ->where('resource_type', 'Patient')
                ->where('logical_id', $matches[1])
                ->value('local_key');
            if ($patientId === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereHas('patientVisit', fn (Builder $visit) => $visit->where('patient_id', $patientId));
        }

        if ($request->query('date') !== null) {
            $query->whereDate('recorded_date', $request->query('date'));
        }

        $pageSize = min((int) $request->query('_count', self::PAGE_SIZE), 100);
        $page = (int) $request->query('page', 1);
        if (($page - 1) * $pageSize > 100000) {
            return $this->outcome('too-costly', 'The requested result page is beyond the supported offset range.', 400);
        }

        $vitalSigns = $query->orderBy('vital_signs.id')->paginate($pageSize, ['vital_signs.*'], 'page', $page);
        $entries = $vitalSigns->getCollection()->map(function (VitalSign $vitalSign) use ($identities): array {
            $id = $identities->idForObservation($vitalSign);

            return [
                'fullUrl' => rtrim((string) config('fhir.base_url'), '/').'/Observation/'.$id,
                'resource' => $this->toFhirObservation($vitalSign, $id, $identities),
                'search' => ['mode' => 'match'],
            ];
        });

        return $this->searchBundle($entries, $vitalSigns->total(), $request, $page, $vitalSigns->hasMorePages());
    }

    private function toFhirObservation(VitalSign $vitalSign, string $id, FhirResourceIdentityService $identities): array
    {
        $visit = $vitalSign->patientVisit;
        $components = [];
        $this->addQuantity($components, '8310-5', 'Body temperature', $vitalSign->body_temperature, 'Cel', '°C');
        $this->addQuantity($components, '8480-6', 'Systolic blood pressure', $vitalSign->blood_pressure_systolic, 'mm[Hg]', 'mmHg');
        $this->addQuantity($components, '8462-4', 'Diastolic blood pressure', $vitalSign->blood_pressure_diastolic, 'mm[Hg]', 'mmHg');
        $this->addQuantity($components, '8867-4', 'Heart rate', $vitalSign->heart_rate, '/min', 'beats/min');
        $this->addQuantity($components, '9279-1', 'Respiratory rate', $vitalSign->respiratory_rate, '/min', 'breaths/min');
        $this->addQuantity($components, '59408-5', 'Oxygen saturation by pulse oximetry', $vitalSign->oxygen_saturation, '%', '%');
        $this->addQuantity($components, '29463-7', 'Body weight', $vitalSign->weight, 'kg', 'kg');
        $this->addQuantity($components, '8302-2', 'Body height', $vitalSign->height, 'cm', 'cm');

        $resource = [
            'resourceType' => 'Observation',
            'id' => $id,
            'status' => 'unknown',
            'category' => [[
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                    'code' => 'vital-signs',
                    'display' => 'Vital Signs',
                ]],
            ]],
            'code' => ['text' => 'Vital signs panel'],
            'subject' => [
                'reference' => 'Patient/'.$identities->idForPatient($visit->patient),
            ],
            'encounter' => [
                'reference' => 'Encounter/'.$identities->idForEncounter($visit),
            ],
            'component' => $components,
        ];

        if ($vitalSign->recorded_date) {
            $resource['effectiveDateTime'] = $vitalSign->recorded_date->toIso8601String();
        }
        if ($vitalSign->updated_at) {
            $resource['meta'] = ['lastUpdated' => $vitalSign->updated_at->toIso8601String()];
        }

        return $resource;
    }

    private function addQuantity(array &$components, string $code, string $display, mixed $value, string $unitCode, string $unitDisplay): void
    {
        if ($value === null || ! is_numeric($value)) {
            return;
        }

        $components[] = [
            'code' => ['coding' => [[
                'system' => 'http://loinc.org',
                'code' => $code,
                'display' => $display,
            ]]],
            'valueQuantity' => [
                'value' => (float) $value,
                'unit' => $unitDisplay,
                'system' => 'http://unitsofmeasure.org',
                'code' => $unitCode,
            ],
        ];
    }

    private function searchBundle(iterable $entries, int $total, Request $request, int $page, bool $hasMore = false): JsonResponse
    {
        $base = rtrim((string) config('fhir.base_url'), '/').'/Observation';
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
            'issue' => [['severity' => 'error', 'code' => $code, 'diagnostics' => $diagnostics]],
        ], $status);
    }
}
